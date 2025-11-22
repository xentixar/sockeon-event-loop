# Coroutines

Generator-based coroutines for async/await-like syntax in PHP.

## Overview

Coroutines allow you to write asynchronous code that looks like synchronous code. They use PHP generators (`yield` keyword) to pause execution, wait for async operations to complete, and then resume.

## Getting Started

### Basic Usage

```php
use Sockeon\EventLoop\Coroutine\Coroutine;
use Sockeon\EventLoop\Promise\Promise;
use Sockeon\EventLoop\Loop\Loop;

$loop = Loop::getInstance();

$coroutine = Coroutine::create(function (): Generator {
    // This looks synchronous, but it's actually async!
    $value1 = yield Promise::resolve('Hello');
    $value2 = yield Promise::resolve('World');
    return $value1 . ' ' . $value2;
});

$coroutine->promise()->then(function ($result) {
    echo $result; // "Hello World"
});

$loop->run();
```

## How It Works

1. When you `yield` a Promise, the coroutine pauses
2. The coroutine waits for the Promise to resolve
3. Once resolved, the coroutine resumes with the resolved value
4. The process continues until the generator returns

## API Reference

### CoroutineInterface

The main interface for coroutines.

#### Methods

##### `promise(): PromiseInterface`

Returns the promise associated with this coroutine. The promise resolves when the coroutine completes.

**Returns:**
- `PromiseInterface`: The promise

**Example:**
```php
$coroutine = Coroutine::create(function (): Generator {
    yield Promise::resolve('Done');
    return 'Complete';
});

$coroutine->promise()->then(function ($value) {
    echo $value; // "Complete"
});
```

##### `isRunning(): bool`

Checks if the coroutine is currently running.

**Returns:**
- `bool`: True if the coroutine is running

**Example:**
```php
$coroutine = Coroutine::create(function (): Generator {
    yield Promise::resolve('Test');
    return 'Done';
});

echo $coroutine->isRunning() ? 'Running' : 'Not running';
```

##### `isCompleted(): bool`

Checks if the coroutine has completed.

**Returns:**
- `bool`: True if the coroutine has completed

**Example:**
```php
$coroutine = Coroutine::create(function (): Generator {
    return 'Done';
});

$loop->run();
echo $coroutine->isCompleted() ? 'Completed' : 'Not completed';
```

### Coroutine Class

#### Static Methods

##### `Coroutine::create(callable $callable): self`

Creates a new coroutine from a callable that returns a generator.

**Parameters:**
- `$callable` (callable): The callable that returns a generator

**Returns:**
- `self`: The coroutine instance

**Example:**
```php
$coroutine = Coroutine::create(function (): Generator {
    yield Promise::resolve('Hello');
    return 'World';
});
```

#### Constructor

##### `__construct(Generator $generator)`

Creates a new coroutine from a generator.

**Parameters:**
- `$generator` (Generator): The generator to execute

**Example:**
```php
$generator = (function (): Generator {
    yield Promise::resolve('Test');
    return 'Done';
})();

$coroutine = new Coroutine($generator);
```

## Promise Unwrapping

When you yield a Promise, the coroutine automatically waits for it to resolve and passes the resolved value back:

```php
$coroutine = Coroutine::create(function (): Generator {
    $promise = Promise::resolve('Hello');
    $value = yield $promise; // $value is now 'Hello'
    return $value;
});
```

## Error Handling

Coroutines support try/catch for error handling:

```php
$coroutine = Coroutine::create(function (): Generator {
    try {
        $result = yield Promise::reject(new Exception('Error'));
    } catch (Exception $e) {
        echo $e->getMessage(); // "Error"
        return 'Recovered';
    }
});
```

## Chaining Coroutines

You can chain coroutines together:

```php
$coroutine1 = Coroutine::create(function (): Generator {
    return yield Promise::resolve('Step 1');
});

$coroutine2 = Coroutine::create(function (): Generator {
    $step1 = yield $coroutine1->promise();
    $step2 = yield Promise::resolve('Step 2');
    return $step1 . ' -> ' . $step2;
});
```

## Non-Promise Yields

You can also yield non-promise values:

```php
$coroutine = Coroutine::create(function (): Generator {
    $value1 = yield 1;
    $value2 = yield 2;
    return $value1 + $value2; // 3
});
```

## Best Practices

1. **Always return a value**: Make sure your generator returns a value, not just yields.

2. **Handle errors**: Use try/catch blocks to handle promise rejections.

3. **Use meaningful variable names**: Name your yielded values clearly.

4. **Keep coroutines focused**: Each coroutine should have a single, clear purpose.

## Real-World Examples

### Sequential API Calls

```php
$coroutine = Coroutine::create(function (): Generator {
    $user = yield fetchUser(123);
    $posts = yield fetchPosts($user->id);
    $comments = yield fetchComments($posts[0]->id);
    return ['user' => $user, 'posts' => $posts, 'comments' => $comments];
});
```

### Error Handling with Fallback

```php
$coroutine = Coroutine::create(function (): Generator {
    try {
        $data = yield fetchFromPrimaryAPI();
    } catch (Exception $e) {
        $data = yield fetchFromFallbackAPI();
    }
    return $data;
});
```

## Examples

See the [examples](../examples/coroutine/) directory for more examples.

