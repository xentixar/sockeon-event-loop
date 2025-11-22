# Event Loop

The Event Loop is the core component that manages asynchronous operations and I/O events.

## Overview

The Event Loop provides a way to handle non-blocking I/O operations, timers, and callbacks in PHP. It uses PHP's native `stream_select` function to efficiently manage multiple streams and timers.

## Getting Started

### Basic Usage

```php
use Sockeon\EventLoop\Loop\Loop;

$loop = Loop::getInstance();

// Schedule a callback for the next tick
$loop->defer(function () {
    echo "This runs on the next tick\n";
});

// Schedule a delayed callback
$loop->delay(1.0, function () {
    echo "This runs after 1 second\n";
});

// Schedule a repeating callback
$loop->repeat(0.5, function () {
    echo "This runs every 0.5 seconds\n";
});

$loop->run();
```

## API Reference

### LoopInterface

The main interface for the event loop.

#### Methods

##### `run(): void`

Starts the event loop. This method will block until `stop()` is called.

```php
$loop->run();
```

##### `stop(): void`

Stops the event loop. The loop will exit on the next iteration.

```php
$loop->stop();
```

##### `defer(callable $callback): string`

Schedules a callback to be executed on the next tick of the event loop.

**Parameters:**
- `$callback` (callable): The callback to execute

**Returns:**
- `string`: Watcher ID that can be used to cancel the callback

**Example:**
```php
$id = $loop->defer(function () {
    echo "Next tick\n";
});
```

##### `delay(float $delay, callable $callback): string`

Schedules a callback to be executed after a specified delay.

**Parameters:**
- `$delay` (float): Delay in seconds
- `$callback` (callable): The callback to execute

**Returns:**
- `string`: Watcher ID that can be used to cancel the callback

**Example:**
```php
$id = $loop->delay(2.5, function () {
    echo "After 2.5 seconds\n";
});
```

##### `repeat(float $interval, callable $callback): string`

Schedules a callback to be executed repeatedly at a specified interval.

**Parameters:**
- `$interval` (float): Interval in seconds between executions
- `$callback` (callable): The callback to execute

**Returns:**
- `string`: Watcher ID that can be used to cancel the callback

**Example:**
```php
$count = 0;
$id = $loop->repeat(1.0, function () use (&$count, $loop) {
    $count++;
    echo "Tick #$count\n";
    
    if ($count >= 5) {
        $loop->stop();
    }
});
```

##### `onReadable($stream, callable $callback): string`

Watches a stream for readable events.

**Parameters:**
- `$stream` (resource): The stream resource to watch
- `$callback` (callable): The callback to execute when the stream is readable

**Returns:**
- `string`: Watcher ID that can be used to cancel the watcher

**Example:**
```php
$socket = stream_socket_client('tcp://example.com:80');
$id = $loop->onReadable($socket, function ($stream) {
    $data = fread($stream, 1024);
    echo "Received: $data\n";
});
```

##### `onWritable($stream, callable $callback): string`

Watches a stream for writable events.

**Parameters:**
- `$stream` (resource): The stream resource to watch
- `$callback` (callable): The callback to execute when the stream is writable

**Returns:**
- `string`: Watcher ID that can be used to cancel the watcher

**Example:**
```php
$socket = stream_socket_client('tcp://example.com:80');
$id = $loop->onWritable($socket, function ($stream) {
    fwrite($stream, "GET / HTTP/1.1\r\n\r\n");
});
```

##### `cancel(string $watcherId): void`

Cancels a watcher by its ID.

**Parameters:**
- `$watcherId` (string): The watcher ID returned by `defer()`, `delay()`, `repeat()`, `onReadable()`, or `onWritable()`

**Example:**
```php
$id = $loop->delay(10.0, function () {
    echo "This won't run\n";
});

$loop->cancel($id);
```

## Singleton Pattern

The `Loop` class implements the singleton pattern, ensuring only one event loop instance exists per application:

```php
$loop1 = Loop::getInstance();
$loop2 = Loop::getInstance();

// $loop1 and $loop2 are the same instance
var_dump($loop1 === $loop2); // bool(true)
```

## Driver Architecture

The event loop uses a driver-based architecture, allowing different implementations:

- **NativeDriver**: Uses PHP's native `stream_select` function (default)
- Future drivers: libev, libuv, etc.

The driver is automatically selected based on availability.

## Best Practices

1. **Always stop the loop**: Make sure to call `stop()` when you're done, or the loop will run indefinitely.

2. **Use watcher IDs**: Store watcher IDs if you need to cancel them later.

3. **Handle errors**: Wrap callbacks in try-catch blocks to handle exceptions gracefully.

4. **Resource cleanup**: Close streams and cancel watchers when no longer needed.

## Examples

See the [examples](../examples/event-loop/) directory for more examples.

