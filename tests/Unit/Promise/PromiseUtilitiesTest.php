<?php

declare(strict_types=1);

use Sockeon\EventLoop\Loop\Loop;
use Sockeon\EventLoop\Promise\Promise;

test('Promise::all resolves with array of all values', function () {
    $loop = Loop::getInstance();
    $result = null;

    $promise1 = Promise::resolve(1);
    $promise2 = Promise::resolve(2);
    $promise3 = Promise::resolve(3);

    Promise::all([$promise1, $promise2, $promise3])
        ->then(function ($values) use (&$result, $loop): void {
            $result = $values;
            $loop->stop();
        });

    $loop->run();

    expect($result)->toBe([1, 2, 3]);
});

test('Promise::all maintains order of promises', function () {
    $loop = Loop::getInstance();
    $result = null;

    $promise1 = new Promise(function (callable $resolve): void {
        $resolve(1);
    });
    $promise2 = new Promise(function (callable $resolve): void {
        $resolve(2);
    });
    $promise3 = new Promise(function (callable $resolve): void {
        $resolve(3);
    });

    // Resolve in different order
    $promise3->then(function () use ($promise2, $promise1): void {
        $promise2->then(function () use ($promise1): void {
            $promise1->then(function (): void {
            });
        });
    });

    Promise::all([$promise1, $promise2, $promise3])
        ->then(function ($values) use (&$result, $loop): void {
            $result = $values;
            $loop->stop();
        });

    $loop->run();

    expect($result)->toBe([1, 2, 3]);
});

test('Promise::all rejects if any promise rejects', function () {
    $loop = Loop::getInstance();
    $reason = null;

    $promise1 = Promise::resolve(1);
    $promise2 = Promise::reject(new RuntimeException('error'));
    $promise3 = Promise::resolve(3);

    Promise::all([$promise1, $promise2, $promise3])
        ->catch(function ($e) use (&$reason, $loop): void {
            $reason = $e;
            $loop->stop();
        });

    $loop->run();

    expect($reason)->toBeInstanceOf(RuntimeException::class);
    expect($reason->getMessage())->toBe('error');
});

test('Promise::all resolves with empty array for empty input', function () {
    $loop = Loop::getInstance();
    $result = null;

    Promise::all([])
        ->then(function ($values) use (&$result, $loop): void {
            $result = $values;
            $loop->stop();
        });

    $loop->run();

    expect($result)->toBe([]);
});

test('Promise::all works with mixed promises and values', function () {
    $loop = Loop::getInstance();
    $result = null;

    $promise1 = Promise::resolve(1);
    $value2 = 2;
    $promise3 = Promise::resolve(3);

    Promise::all([$promise1, $value2, $promise3])
        ->then(function ($values) use (&$result, $loop): void {
            $result = $values;
            $loop->stop();
        });

    $loop->run();

    expect($result)->toBe([1, 2, 3]);
});

test('Promise::any resolves with first resolved value', function () {
    $loop = Loop::getInstance();
    $result = null;

    $promise1 = new Promise(function (callable $resolve): void {
        $resolve(1);
    });
    $promise2 = new Promise(function (callable $resolve): void {
        $resolve(2);
    });
    $promise3 = new Promise(function (callable $resolve): void {
        $resolve(3);
    });

    Promise::any([$promise1, $promise2, $promise3])
        ->then(function ($value) use (&$result, $loop): void {
            $result = $value;
            $loop->stop();
        });

    $loop->run();

    expect($result)->toBeIn([1, 2, 3]);
});

test('Promise::any rejects if all promises reject', function () {
    $loop = Loop::getInstance();
    $reason = null;

    $promise1 = Promise::reject(new RuntimeException('error1'));
    $promise2 = Promise::reject(new RuntimeException('error2'));
    $promise3 = Promise::reject(new RuntimeException('error3'));

    Promise::any([$promise1, $promise2, $promise3])
        ->catch(function ($e) use (&$reason, $loop): void {
            $reason = $e;
            $loop->stop();
        });

    $loop->run();

    expect($reason)->toBeInstanceOf(RuntimeException::class);
    expect($reason->getMessage())->toBe('All promises rejected');
});

test('Promise::any rejects with error for empty input', function () {
    $loop = Loop::getInstance();
    $reason = null;

    Promise::any([])
        ->catch(function ($e) use (&$reason, $loop): void {
            $reason = $e;
            $loop->stop();
        });

    $loop->run();

    expect($reason)->toBeInstanceOf(RuntimeException::class);
    expect($reason->getMessage())->toBe('No promises provided');
});

test('Promise::race resolves with first resolved promise', function () {
    $loop = Loop::getInstance();
    $result = null;

    $promise1 = new Promise(function (callable $resolve): void {
        $resolve(1);
    });
    $promise2 = new Promise(function (callable $resolve): void {
        $resolve(2);
    });

    Promise::race([$promise1, $promise2])
        ->then(function ($value) use (&$result, $loop): void {
            $result = $value;
            $loop->stop();
        });

    $loop->run();

    expect($result)->toBeIn([1, 2]);
});

test('Promise::race rejects with first rejected promise', function () {
    $loop = Loop::getInstance();
    $reason = null;

    $promise1 = Promise::reject(new RuntimeException('error1'));
    $promise2 = Promise::reject(new RuntimeException('error2'));

    Promise::race([$promise1, $promise2])
        ->catch(function ($e) use (&$reason, $loop): void {
            $reason = $e;
            $loop->stop();
        });

    $loop->run();

    expect($reason)->toBeInstanceOf(RuntimeException::class);
    expect($reason->getMessage())->toBeIn(['error1', 'error2']);
});

test('Promise::race rejects with error for empty input', function () {
    $loop = Loop::getInstance();
    $reason = null;

    Promise::race([])
        ->catch(function ($e) use (&$reason, $loop): void {
            $reason = $e;
            $loop->stop();
        });

    $loop->run();

    expect($reason)->toBeInstanceOf(RuntimeException::class);
    expect($reason->getMessage())->toBe('No promises provided');
});

test('Promise::race settles with first settled promise', function () {
    $loop = Loop::getInstance();
    $result = null;
    $settled = false;

    $promise1 = new Promise(function (callable $resolve): void {
        $resolve('success');
    });
    $promise2 = new Promise(function (callable $resolve, callable $reject): void {
        $reject(new RuntimeException('error'));
    });

    Promise::race([$promise1, $promise2])
        ->then(function ($value) use (&$result, &$settled, $loop): void {
            if (! $settled) {
                $settled = true;
                $result = $value;
                $loop->stop();
            }
        })
        ->catch(function ($e) use (&$result, &$settled, $loop): void {
            if (! $settled) {
                $settled = true;
                $result = $e;
                $loop->stop();
            }
        });

    $loop->run();

    // Either resolve or reject can win, but one should settle
    expect($settled)->toBeTrue();
    expect($result !== null)->toBeTrue();
});

test('Promise::race works with mixed promises and values', function () {
    $loop = Loop::getInstance();
    $result = null;

    $promise1 = Promise::resolve(1);
    $value2 = 2;

    Promise::race([$promise1, $value2])
        ->then(function ($value) use (&$result, $loop): void {
            $result = $value;
            $loop->stop();
        });

    $loop->run();

    expect($result)->toBeIn([1, 2]);
});
