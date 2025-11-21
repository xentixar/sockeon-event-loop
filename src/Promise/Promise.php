<?php

declare(strict_types=1);

namespace Sockeon\EventLoop\Promise;

use RuntimeException;
use Sockeon\EventLoop\Loop\Loop;
use Throwable;

/**
 * Promise/A+ compliant promise implementation.
 *
 * A promise represents the eventual result of an asynchronous operation.
 * This implementation follows the Promise/A+ specification.
 */
final class Promise implements PromiseInterface
{
    private const STATE_PENDING = 'pending';
    private const STATE_FULFILLED = 'fulfilled';
    private const STATE_REJECTED = 'rejected';

    private string $state = self::STATE_PENDING;

    /** @var mixed */
    private $value = null;

    /** @var Throwable|null */
    private ?Throwable $reason = null;

    /** @var array<int, array{onFulfilled: ?callable, onRejected: ?callable, promise: Promise}> */
    private array $handlers = [];

    /**
     * Create a new promise.
     *
     * @param callable $executor Function that receives resolve and reject callbacks
     */
    public function __construct(callable $executor)
    {
        try {
            $executor(
                function ($value): void {
                    $this->fulfillInternal($value);
                },
                function (Throwable $reason): void {
                    $this->rejectInternal($reason);
                }
            );
        } catch (Throwable $e) {
            $this->rejectInternal($e);
        }
    }

    /**
     * Appends fulfillment and rejection handlers to the promise.
     *
     * @param callable|null $onFulfilled Called when the promise is fulfilled
     * @param callable|null $onRejected Called when the promise is rejected
     * @return PromiseInterface A new promise
     */
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
    {
        $promise = new self(function (): void {
            // Empty executor - this promise will be resolved/rejected by handlers
        });

        $this->handlers[] = [
            'onFulfilled' => $onFulfilled,
            'onRejected' => $onRejected,
            'promise' => $promise,
        ];

        if ($this->state !== self::STATE_PENDING) {
            $this->processHandlers();
        }

        return $promise;
    }

    /**
     * Appends a rejection handler to the promise.
     *
     * @param callable $onRejected Called when the promise is rejected
     * @return PromiseInterface A new promise
     */
    public function catch(callable $onRejected): PromiseInterface
    {
        return $this->then(null, $onRejected);
    }

    /**
     * Appends a handler that is always called when the promise is settled.
     *
     * @param callable $onFinally Called when the promise is settled
     * @return PromiseInterface A new promise
     */
    public function finally(callable $onFinally): PromiseInterface
    {
        return $this->then(
            function ($value) use ($onFinally) {
                $onFinally();

                return $value;
            },
            function (Throwable $reason) use ($onFinally) {
                $onFinally();

                throw $reason;
            }
        );
    }

    /**
     * Fulfill the promise with a value internally.
     *
     * @param mixed $value The value to fulfill with
     */
    private function fulfillInternal($value): void
    {
        if ($this->state !== self::STATE_PENDING) {
            return;
        }

        // Handle promise chaining
        if ($value instanceof PromiseInterface) {
            $value->then(
                function ($val): void {
                    $this->fulfillInternal($val);
                },
                function (Throwable $reason): void {
                    $this->rejectInternal($reason);
                }
            );

            return;
        }

        $this->state = self::STATE_FULFILLED;
        $this->value = $value;
        $this->processHandlers();
    }

    /**
     * Reject the promise with a reason internally.
     *
     * @param Throwable $reason The reason for rejection
     */
    private function rejectInternal(Throwable $reason): void
    {
        if ($this->state !== self::STATE_PENDING) {
            return;
        }

        $this->state = self::STATE_REJECTED;
        $this->reason = $reason;
        $this->processHandlers();
    }

    /**
     * Process all queued handlers.
     */
    private function processHandlers(): void
    {
        if (empty($this->handlers)) {
            return;
        }

        $loop = Loop::getInstance();
        $handlers = $this->handlers;
        $this->handlers = [];

        $loop->defer(function () use ($handlers): void {
            foreach ($handlers as $handler) {
                $this->processHandler($handler);
            }
        });
    }

    /**
     * Process a single handler.
     *
     * @param array{onFulfilled: ?callable, onRejected: ?callable, promise: Promise} $handler
     */
    private function processHandler(array $handler): void
    {
        $promise = $handler['promise'];

        try {
            if ($this->state === self::STATE_FULFILLED) {
                if ($handler['onFulfilled'] === null) {
                    $promise->fulfillInternal($this->value);
                } else {
                    $result = ($handler['onFulfilled'])($this->value);
                    // Handle promise chaining - if result is a promise, wait for it
                    if ($result instanceof PromiseInterface) {
                        $result->then(
                            function ($val) use ($promise): void {
                                $promise->fulfillInternal($val);
                            },
                            function (Throwable $reason) use ($promise): void {
                                $promise->rejectInternal($reason);
                            }
                        );
                    } else {
                        $promise->fulfillInternal($result);
                    }
                }
            } elseif ($this->state === self::STATE_REJECTED) {
                if ($handler['onRejected'] === null) {
                    if ($this->reason !== null) {
                        $promise->rejectInternal($this->reason);
                    }
                } else {
                    $result = ($handler['onRejected'])($this->reason);
                    // Handle promise chaining - if result is a promise, wait for it
                    if ($result instanceof PromiseInterface) {
                        $result->then(
                            function ($val) use ($promise): void {
                                $promise->fulfillInternal($val);
                            },
                            function (Throwable $reason) use ($promise): void {
                                $promise->rejectInternal($reason);
                            }
                        );
                    } else {
                        $promise->fulfillInternal($result);
                    }
                }
            }
        } catch (Throwable $e) {
            $promise->rejectInternal($e);
        }
    }

    /**
     * Create a resolved promise.
     *
     * @param mixed $value The value to resolve with
     * @return PromiseInterface A resolved promise
     */
    public static function resolve($value): PromiseInterface
    {
        if ($value instanceof PromiseInterface) {
            return $value;
        }

        return new self(function (callable $resolve) use ($value): void {
            $resolve($value);
        });
    }

    /**
     * Create a rejected promise.
     *
     * @param Throwable $reason The reason for rejection
     * @return PromiseInterface A rejected promise
     */
    public static function reject(Throwable $reason): PromiseInterface
    {
        return new self(function (callable $resolve, callable $reject) use ($reason): void {
            $reject($reason);
        });
    }

    /**
     * Wait for all promises to resolve.
     *
     * Returns a promise that resolves with an array of all resolved values
     * in the same order as the input promises. If any promise rejects,
     * the returned promise rejects with the first rejection reason.
     *
     * @param array<PromiseInterface|mixed> $promises Array of promises or values
     * @return PromiseInterface A promise that resolves with an array of values
     */
    public static function all(array $promises): PromiseInterface
    {
        if (empty($promises)) {
            return self::resolve([]);
        }

        return new self(function (callable $resolve, callable $reject) use ($promises): void {
            $results = [];
            $remaining = count($promises);
            $rejected = false;

            foreach ($promises as $index => $promise) {
                $promise = self::resolve($promise);

                $promise->then(
                    function ($value) use (&$results, &$remaining, $index, &$rejected, $resolve): void {
                        if ($rejected) {
                            return;
                        }

                        $results[$index] = $value;
                        $remaining--;

                        if ($remaining === 0) {
                            // Sort by index to maintain order
                            ksort($results);
                            $resolve(array_values($results));
                        }
                    },
                    function (Throwable $reason) use (&$rejected, $reject): void {
                        if ($rejected) {
                            return;
                        }

                        $rejected = true;
                        $reject($reason);
                    }
                );
            }
        });
    }

    /**
     * Wait for any promise to resolve.
     *
     * Returns a promise that resolves with the value of the first promise
     * that resolves. If all promises reject, the returned promise rejects
     * with an AggregateException containing all rejection reasons.
     *
     * @param array<PromiseInterface|mixed> $promises Array of promises or values
     * @return PromiseInterface A promise that resolves with the first resolved value
     */
    public static function any(array $promises): PromiseInterface
    {
        if (empty($promises)) {
            return self::reject(new RuntimeException('No promises provided'));
        }

        return new self(function (callable $resolve, callable $reject) use ($promises): void {
            $reasons = [];
            $remaining = count($promises);
            $resolved = false;

            foreach ($promises as $promise) {
                $promise = self::resolve($promise);

                $promise->then(
                    function ($value) use (&$resolved, $resolve): void {
                        if ($resolved) {
                            return;
                        }

                        $resolved = true;
                        $resolve($value);
                    },
                    function (Throwable $reason) use (&$reasons, &$remaining, &$resolved, $reject): void {
                        if ($resolved) {
                            return;
                        }

                        $reasons[] = $reason;
                        $remaining--;

                        if ($remaining === 0) {
                            $reject(new RuntimeException('All promises rejected', 0, $reasons[0]));
                        }
                    }
                );
            }
        });
    }

    /**
     * Race promises - return the first promise that settles.
     *
     * Returns a promise that resolves or rejects with the value or reason
     * of the first promise that settles (either resolves or rejects).
     *
     * @param array<PromiseInterface|mixed> $promises Array of promises or values
     * @return PromiseInterface A promise that settles with the first settled promise
     */
    public static function race(array $promises): PromiseInterface
    {
        if (empty($promises)) {
            return self::reject(new RuntimeException('No promises provided'));
        }

        return new self(function (callable $resolve, callable $reject) use ($promises): void {
            $settled = false;

            foreach ($promises as $promise) {
                $promise = self::resolve($promise);

                $promise->then(
                    function ($value) use (&$settled, $resolve): void {
                        if ($settled) {
                            return;
                        }

                        $settled = true;
                        $resolve($value);
                    },
                    function (Throwable $reason) use (&$settled, $reject): void {
                        if ($settled) {
                            return;
                        }

                        $settled = true;
                        $reject($reason);
                    }
                );
            }
        });
    }
}
