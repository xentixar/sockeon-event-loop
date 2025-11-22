# Documentation

Welcome to the Event Loop documentation. This directory contains comprehensive documentation for all features of the library.

## Table of Contents

- [Event Loop](./event-loop.md) - Core event loop functionality
- [Promises](./promises.md) - Promise/A+ compliant promises
- [Coroutines](./coroutines.md) - Generator-based coroutines
- [API Reference](./api-reference.md) - Complete API documentation

## Quick Start

```php
use Sockeon\EventLoop\Loop\Loop;
use Sockeon\EventLoop\Promise\Promise;
use Sockeon\EventLoop\Coroutine\Coroutine;

$loop = Loop::getInstance();

// Use promises
$promise = Promise::resolve('Hello');
$promise->then(function ($value) {
    echo $value;
});

// Use coroutines
$coroutine = Coroutine::create(function (): Generator {
    $result = yield Promise::resolve('World');
    return $result;
});

$loop->run();
```

## Examples

For practical examples, see the [examples](../examples/) directory.

