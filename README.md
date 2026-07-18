# Cadence

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kodefarmers/laravel-cadence.svg?style=for-the-badge)](https://packagist.org/packages/kodefarmers/laravel-cadence)
[![PHP Version](https://img.shields.io/packagist/php-v/kodefarmers/laravel-cadence?style=for-the-badge)](https://packagist.org/packages/kodefarmers/laravel-cadence)
[![Tests](https://img.shields.io/github/actions/workflow/status/kodefarmers/laravel-cadence/tests.yml?branch=main&label=tests&style=for-the-badge)](https://github.com/kodefarmers/laravel-cadence/actions/workflows/tests.yml)
[![Static Analysis](https://img.shields.io/github/actions/workflow/status/kodefarmers/laravel-cadence/phpstan.yml?branch=main&label=phpstan&style=for-the-badge)](https://github.com/kodefarmers/laravel-cadence/actions/workflows/phpstan.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/kodefarmers/laravel-cadence?style=for-the-badge)](https://packagist.org/packages/kodefarmers/laravel-cadence)

![Cadence Banner](https://raw.githubusercontent.com/kodefarmers/laravel-cadence/refs/heads/main/assets/cadence-github-preview.png)

Cadence is a Laravel package for applying progressive backoff based on consecutive failures.

Unlike traditional rate limiting, Cadence only introduces delays after repeated failed attempts.
A successful operation immediately resets the backoff state, allowing normal traffic to continue uninterrupted while slowing down repeated failures.

Cadence stores its state using Laravel's cache abstraction and provides a clean, Laravel-native API through a manager, facade, and dependency injection.

---

## Why Cadence?

Laravel's rate limiter controls **how often** an action can be performed.

Cadence controls **how long the next attempt should wait** after repeated failures.

Instead of limiting every request, Cadence progressively increases the delay between failed attempts and immediately resets the backoff state after a successful operation.

---

# Features

- Track repeated failures on a per-key basis
- Apply progressive backoff after configurable free attempts
- Reset backoff immediately after successful operations
- Store state using Laravel's cache abstraction
- Configure free attempts and idle timeout
- Switch between configurable backoff strategies
- Resolve engines via the facade or dependency injection

---

# When should I use Cadence?

Cadence is designed for operations where repeated **failures** should temporarily slow down future attempts.

Common use cases include:

- Login and authentication endpoints
- One-time password (OTP) verification
- Password reset attempts
- API authentication
- Third-party API integrations
- Webhook delivery retries
- Expensive or sensitive operations that should back off after consecutive failures

Unlike Laravel's rate limiter, Cadence does **not** limit every request.
Instead, it only introduces delays after repeated failures, allowing successful operations to proceed normally while discouraging abusive or repeated failed attempts.

---

# Installation

Install the package via Composer:

```bash
composer require kodefarmers/laravel-cadence
```

Laravel will automatically discover and register the package.

Publish the configuration file if you wish to customize the default settings:

```bash
php artisan vendor:publish --tag=cadence-config
```

---

# Configuration

The published configuration file is located at `config/cadence.php`.

```php
return [

    'default' => env('CADENCE_DEFAULT_DRIVER', 'exponential'),

    'free_attempts' => 3,

    'idle_timeout' => 3600,

    'cache' => [
        'store' => env('CADENCE_CACHE_STORE'),
    ],

    'drivers' => [

        'exponential' => [
            'base_delay' => 2,
        ],

        'fibonacci' => [
            'base_delay' => 1,
        ],

        'linear' => [
            'base_delay' => 2,
        ],

    ],

];
```

## Configuration Options

| Option                           | Default       | Description                                                                    |
| -------------------------------- | ------------- | ------------------------------------------------------------------------------ |
| `default`                        | `exponential` | The default backoff driver.                                                    |
| `free_attempts`                  | `3`           | Number of failures allowed before backoff is applied.                          |
| `idle_timeout`                   | `3600`        | Number of seconds to retain failure state before it expires.                   |
| `cache.store`                    | `null`        | Laravel cache store name used by Cadence. Leave null to use the default store. |
| `drivers.exponential.base_delay` | `2`           | Base delay used by the exponential driver.                                     |
| `drivers.fibonacci.base_delay`   | `1`           | Base delay used by the fibonacci driver.                                       |
| `drivers.linear.base_delay`      | `2`           | Base delay used by the linear driver.                                          |

## Using a Specific Backoff Strategy

Cadence supports multiple backoff strategies. You can configure the default strategy using the `CADENCE_DEFAULT_DRIVER` environment variable.

```env
CADENCE_DEFAULT_DRIVER=fibonacci
```

See the [Available Drivers](#available-drivers) section for the list of built-in drivers and their behavior.

If not specified, Cadence uses the `exponential` strategy by default.

---

## Using a Specific Laravel Cache Store

Cadence uses Laravel's cache abstraction, so it can work with any cache store supported by your Laravel application.

Set the store with an environment variable:

```env
CADENCE_CACHE_STORE=
```

Examples:

```env
CADENCE_CACHE_STORE=redis
CADENCE_CACHE_STORE=database
```

If the value is empty or not set, Cadence falls back to Laravel's default cache store.

---

# Quick Start

A typical workflow consists of three steps:

1. Ensure the key is not currently locked.
2. Record failures whenever the protected operation fails.
3. Record a success to reset the backoff state.

```php
use Kodefarmers\Cadence\Exceptions\CadenceLockedException;
use Kodefarmers\Cadence\Facades\Cadence;

$cadence = Cadence::driver();

$key = 'operation:resource-id';

try {
    $cadence->ensureNotLocked($key);

    // Perform the protected operation...

    if ($operationFailed) {
        $result = $cadence->recordFailure($key);

        return [
            'success' => false,
            'locked' => $result->isLocked,
            'retry_after' => $result->delay,
        ];
    }

    $cadence->recordSuccess($key);

    return [
        'success' => true,
    ];
} catch (CadenceLockedException $exception) {
    return [
        'success' => false,
        'message' => 'Operation is temporarily locked.',
        'retry_after' => $exception->retryAfter(),
    ];
}
```

By default, the first three failures are allowed without any delay. The fourth failure becomes the first backoff violation and applies the configured delay.

## Using Different Backoff Strategies

The `driver()` method accepts the name of the backoff strategy to use.

```php
$cadence = Cadence::driver('fibonacci');
```

See the [Available Drivers](#available-drivers) section for the list of built-in drivers and their behavior.

---

# Available Drivers

Laravel Cadence currently includes the following backoff drivers:

| Driver        | Description                                                               |
| ------------- | ------------------------------------------------------------------------- |
| `exponential` | Applies progressive exponential delays using the configured `base_delay`. |
| `fibonacci`   | Applies progressively increasing delays based on the Fibonacci sequence.  |
| `linear`      | Applies progressively increasing delays based on the linear sequence.     |

---

# How Cadence Works

Cadence tracks failures for a unique key. A key can represent anything you want to protect, such as:

- A user ID
- An email address
- An IP address
- An API token
- A webhook identifier

Each failed attempt increases the recorded attempt count for that key.

Once the configured free-attempt threshold has been exceeded, Cadence temporarily locks the key using the configured backoff strategy.

While the key is locked, calling `ensureNotLocked()` throws a `CadenceLockedException`.

Calling `recordSuccess()` clears the recorded failures and immediately removes any active lock.

---

# Public API

## Resolving a Cadence Engine

Using the facade:

```php
use Kodefarmers\Cadence\Facades\Cadence;

$cadence = Cadence::driver();
```

Using dependency injection:

```php
use Kodefarmers\Cadence\CadenceManager;

class LoginService
{
    public function __construct(
        private readonly CadenceManager $cadence,
    ) {
    }

    public function handle(string $key): void
    {
        $cadence = $this->cadence->driver();

        // ...
    }
}
```

---

## Recording Failures

```php
$result = $cadence->recordFailure($key);
```

Returns a `CadenceResult` containing:

| Property         | Description                          |
| ---------------- | ------------------------------------ |
| `attempt`        | Current attempt count.               |
| `violationCount` | Current violation count.             |
| `delay`          | Delay applied in seconds.            |
| `isLocked`       | Whether the key is currently locked. |

---

## Recording Success

```php
$cadence->recordSuccess($key);
```

Resets the recorded attempts and removes any active lock.

---

## Querying State

```php
$cadence->ensureNotLocked($key);

$cadence->isLocked($key);

$cadence->remainingBackoff($key);

$cadence->attempts($key);
```

### `ensureNotLocked()`

Throws `CadenceLockedException` if the key is currently locked.

### `isLocked()`

Returns whether the key is currently locked.

### `remainingBackoff()`

Returns the remaining lock duration in seconds.

### `attempts()`

Returns the current recorded attempt count.

---

## CadenceLockedException

When a key is locked, `ensureNotLocked()` throws `CadenceLockedException`.

The exception exposes:

- `key()`
- `retryAfter()`
- `attempts()`
- `violationCount()`

---

# Testing

Run the test suite:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

Format the codebase:

```bash
composer format
```

---

# Contributing

Contributions are welcome.

Please open an issue to discuss significant changes before submitting a pull request.
All pull requests should include appropriate tests for new functionality or behavior changes.

---

# Changelog

See `CHANGELOG.md` for a complete release history.

---

# Security

If you discover a security vulnerability, please report it privately instead of opening a public issue.

---

# License

Cadence is open-source software licensed under the MIT License.
