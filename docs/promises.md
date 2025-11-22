# Promises

Promise/A+ compliant promise implementation for handling asynchronous operations.

## Overview

Promises provide a clean way to handle asynchronous operations without callback hell. They represent the eventual result of an asynchronous operation and can be in one of three states: pending, fulfilled, or rejected.

## Getting Started

### Basic Usage

```php
use Sockeon\EventLoop\Promise\Promise;
use Sockeon\EventLoop\Loop\Loop;

$loop = Loop::getInstance();

$promise = new Promise(function (callable $resolve, callable $reject) {
    // Simulate async operation
    $loop->delay(1.0, function () use ($resolve) {
        $resolve('Operation completed');
    });
});

$promise->then(function ($value) {
    echo $value; // "Operation completed"
});

$loop->run();
```

## API Reference

### PromiseInterface

The main interface for promises.

#### Methods

##### `then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface`

Appends fulfillment and rejection handlers to the promise. Returns a new promise.

**Parameters:**
- `$onFulfilled` (callable|null): Called when the promise is fulfilled
- `$onRejected` (callable|null): Called when the promise is rejected

**Returns:**
- `PromiseInterface`: A new promise

**Example:**
```php
$promise->then(
    function ($value) {
        echo "Fulfilled: $value\n";
    },
    function ($reason) {
        echo "Rejected: " . $reason->getMessage() . "\n";
    }
);
```

##### `catch(callable $onRejected): PromiseInterface`

Appends a rejection handler to the promise. This is a convenience method equivalent to `then(null, $onRejected)`.

**Parameters:**
- `$onRejected` (callable): Called when the promise is rejected

**Returns:**
- `PromiseInterface`: A new promise

**Example:**
```php
$promise->catch(function ($reason) {
    echo "Error: " . $reason->getMessage() . "\n";
});
```

##### `finally(callable $onFinally): PromiseInterface`

Appends a handler that is always called when the promise is settled (fulfilled or rejected).

**Parameters:**
- `$onFinally` (callable): Called when the promise is settled

**Returns:**
- `PromiseInterface`: A new promise

**Example:**
```php
$promise->finally(function () {
    echo "Promise settled\n";
});
```

### Promise Class

#### Static Methods

##### `Promise::resolve($value): PromiseInterface`

Creates a promise that is immediately resolved with the given value.

**Parameters:**
- `$value` (mixed): The value to resolve with

**Returns:**
- `PromiseInterface`: A resolved promise

**Example:**
```php
$promise = Promise::resolve('Hello');
$promise->then(function ($value) {
    echo $value; // "Hello"
});
```

##### `Promise::reject(Throwable $reason): PromiseInterface`

Creates a promise that is immediately rejected with the given reason.

**Parameters:**
- `$reason` (Throwable): The reason for rejection

**Returns:**
- `PromiseInterface`: A rejected promise

**Example:**
```php
$promise = Promise::reject(new Exception('Error'));
$promise->catch(function ($reason) {
    echo $reason->getMessage(); // "Error"
});
```

##### `Promise::all(array $promises): PromiseInterface`

Returns a promise that resolves when all promises in the array have resolved, or rejects if any promise rejects.

**Parameters:**
- `$promises` (array): Array of promises

**Returns:**
- `PromiseInterface`: A promise that resolves with an array of all resolved values

**Example:**
```php
$promise1 = Promise::resolve(1);
$promise2 = Promise::resolve(2);
$promise3 = Promise::resolve(3);

Promise::all([$promise1, $promise2, $promise3])
    ->then(function ($values) {
        print_r($values); // [1, 2, 3]
    });
```

##### `Promise::any(array $promises): PromiseInterface`

Returns a promise that resolves when any promise in the array resolves, or rejects if all promises reject.

**Parameters:**
- `$promises` (array): Array of promises

**Returns:**
- `PromiseInterface`: A promise that resolves with the first resolved value

**Example:**
```php
$promise1 = Promise::reject(new Exception('Error 1'));
$promise2 = Promise::resolve('Success');

Promise::any([$promise1, $promise2])
    ->then(function ($value) {
        echo $value; // "Success"
    });
```

##### `Promise::race(array $promises): PromiseInterface`

Returns a promise that resolves or rejects as soon as the first promise in the array settles.

**Parameters:**
- `$promises` (array): Array of promises

**Returns:**
- `PromiseInterface`: A promise that settles with the first promise to settle

**Example:**
```php
$promise1 = new Promise(function ($resolve) {
    // Resolves after 2 seconds
});
$promise2 = new Promise(function ($resolve) {
    // Resolves after 1 second
});

Promise::race([$promise1, $promise2])
    ->then(function ($value) {
        // $promise2 wins
    });
```

### Deferred Class

The `Deferred` class provides a way to create a promise and resolve/reject it from outside the executor function.

#### Methods

##### `promise(): PromiseInterface`

Returns the promise associated with this deferred.

**Returns:**
- `PromiseInterface`: The promise

**Example:**
```php
$deferred = new Deferred();
$promise = $deferred->promise();
```

##### `resolve($value): void`

Resolves the promise with a value.

**Parameters:**
- `$value` (mixed): The value to resolve with

**Example:**
```php
$deferred = new Deferred();
$deferred->resolve('Success');
```

##### `reject(Throwable $reason): void`

Rejects the promise with a reason.

**Parameters:**
- `$reason` (Throwable): The reason for rejection

**Example:**
```php
$deferred = new Deferred();
$deferred->reject(new Exception('Error'));
```

## Promise Chaining

Promises can be chained together:

```php
Promise::resolve(10)
    ->then(function ($value) {
        return $value * 2; // 20
    })
    ->then(function ($value) {
        return $value + 5; // 25
    })
    ->then(function ($value) {
        echo $value; // 25
    });
```

## Error Handling

Errors in promise chains are automatically caught and can be handled:

```php
Promise::resolve(10)
    ->then(function ($value) {
        throw new Exception('Error');
    })
    ->catch(function ($reason) {
        echo $reason->getMessage(); // "Error"
    });
```

## Best Practices

1. **Always handle errors**: Use `catch()` to handle promise rejections.

2. **Return values**: Return values from `then()` handlers to pass them to the next handler.

3. **Use static methods**: Use `Promise::resolve()` and `Promise::reject()` for immediate values.

4. **Use Deferred**: Use `Deferred` when you need to resolve/reject from outside the executor.

## Examples

See the [examples](../examples/promise/) directory for more examples.

