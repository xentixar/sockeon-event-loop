# API Reference

Complete API reference for all classes and interfaces.

## Namespaces

- `Sockeon\EventLoop\Loop` - Event loop classes
- `Sockeon\EventLoop\Driver` - Driver implementations
- `Sockeon\EventLoop\Promise` - Promise classes
- `Sockeon\EventLoop\Coroutine` - Coroutine classes

## Loop Classes

### LoopInterface

Main interface for the event loop.

**Location:** `Sockeon\EventLoop\Loop\LoopInterface`

**Methods:**
- `run(): void` - Start the event loop
- `stop(): void` - Stop the event loop
- `defer(callable $callback): string` - Schedule callback for next tick
- `delay(float $delay, callable $callback): string` - Schedule delayed callback
- `repeat(float $interval, callable $callback): string` - Schedule repeating callback
- `onReadable($stream, callable $callback): string` - Watch stream for readable events
- `onWritable($stream, callable $callback): string` - Watch stream for writable events
- `cancel(string $watcherId): void` - Cancel a watcher

### Loop

Singleton event loop instance.

**Location:** `Sockeon\EventLoop\Loop\Loop`

**Methods:**
- `static getInstance(): self` - Get the singleton instance
- Implements all methods from `LoopInterface`

## Driver Classes

### DriverInterface

Interface for event loop drivers.

**Location:** `Sockeon\EventLoop\Driver\DriverInterface`

**Methods:**
- Same as `LoopInterface`

### NativeDriver

Native PHP driver using `stream_select`.

**Location:** `Sockeon\EventLoop\Driver\NativeDriver`

**Methods:**
- Implements all methods from `DriverInterface`

## Promise Classes

### PromiseInterface

Promise/A+ compliant promise interface.

**Location:** `Sockeon\EventLoop\Promise\PromiseInterface`

**Methods:**
- `then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface`
- `catch(callable $onRejected): PromiseInterface`
- `finally(callable $onFinally): PromiseInterface`

### Promise

Promise/A+ compliant promise implementation.

**Location:** `Sockeon\EventLoop\Promise\Promise`

**Methods:**
- `__construct(callable $executor)` - Create a new promise
- `static resolve($value): PromiseInterface` - Create resolved promise
- `static reject(Throwable $reason): PromiseInterface` - Create rejected promise
- `static all(array $promises): PromiseInterface` - Wait for all promises
- `static any(array $promises): PromiseInterface` - Wait for any promise
- `static race(array $promises): PromiseInterface` - Race promises
- Implements all methods from `PromiseInterface`

### Deferred

Deferred promise resolver.

**Location:** `Sockeon\EventLoop\Promise\Deferred`

**Methods:**
- `__construct()` - Create a new deferred
- `promise(): PromiseInterface` - Get the promise
- `resolve($value): void` - Resolve the promise
- `reject(Throwable $reason): void` - Reject the promise

## Coroutine Classes

### CoroutineInterface

Interface for coroutines.

**Location:** `Sockeon\EventLoop\Coroutine\CoroutineInterface`

**Methods:**
- `promise(): PromiseInterface` - Get the promise
- `isRunning(): bool` - Check if running
- `isCompleted(): bool` - Check if completed

### Coroutine

Generator-based coroutine implementation.

**Location:** `Sockeon\EventLoop\Coroutine\Coroutine`

**Methods:**
- `__construct(Generator $generator)` - Create from generator
- `static create(callable $callable): self` - Create from callable
- Implements all methods from `CoroutineInterface`

## Type Hints

### Watcher ID

All methods that return a watcher ID return a `string`:

```php
$id = $loop->defer(function () {});
// $id is a string
```

### Stream Resource

Stream watchers accept a `resource` type:

```php
$stream = stream_socket_client('tcp://example.com:80');
$loop->onReadable($stream, function ($stream) {});
```

### Callbacks

All callbacks are of type `callable`:

```php
$loop->defer(function () {
    // Callback
});
```

## Error Handling

### Exceptions

All classes may throw:
- `RuntimeException` - For runtime errors (e.g., loop already running)
- `InvalidArgumentException` - For invalid arguments
- `Throwable` - For promise rejections

### Promise Rejections

Promise rejections are handled through the `catch()` method:

```php
$promise->catch(function (Throwable $reason) {
    // Handle rejection
});
```

## Constants

None currently defined.

## See Also

- [Event Loop Documentation](./event-loop.md)
- [Promises Documentation](./promises.md)
- [Coroutines Documentation](./coroutines.md)

