# HTTP Adapter

[![Tests](https://github.com/ifds-oss/http-adapter/actions/workflows/tests.yml/badge.svg)](https://github.com/ifds-oss/http-adapter/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/ifds-oss/http-adapter.svg)](https://packagist.org/packages/ifds-oss/http-adapter)
[![License](https://img.shields.io/packagist/l/ifds-oss/http-adapter.svg)](LICENSE)

A driver-based outbound HTTP integration layer for Laravel. Define each third-party
API as a small **client class** with declarative endpoints, then call it through a
fluent, resilient, observable interface — one calling convention, one mocking
mechanism, and one logging/telemetry format for every integration your app talks to.

Built for the reality of integrating flaky external services: **retry with backoff**,
**per-driver circuit breaking**, **lifecycle events** for observability, and a
**config-driven driver registry** so adding a new integration never means editing this
package.

## Why

Most apps accrete one bespoke HTTP client per third party — each with its own retry
logic (or none), its own logging shape, its own way of being mocked in tests. This
package extracts that into a single abstraction:

- **One fluent client base** — declare endpoints as data, call them as methods.
- **Resilience built in** — opt-in retry/backoff and a cache-backed circuit breaker.
- **Observable by default** — every request/response/failure is a Laravel event.
- **Test-friendly** — flip a config flag to serve canned responses, no live calls.
- **Zero package edits to extend** — register drivers from your app's config.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require ifds-oss/http-adapter
```

The service provider and the `HttpAdapter` facade alias are auto-discovered. Publish
the config file if you want to customise it:

```bash
php artisan vendor:publish --tag=http-adapter-config
```

## Quick start

The examples below integrate [JSONPlaceholder](https://jsonplaceholder.typicode.com),
a free public sample REST API — the same one the test suite and the workbench app use,
so you can copy any of this and run it as-is.

### 1. Write a client for the API

Extend `AbstractApiClient`, tag it with the `#[Driver]` attribute, and describe its
endpoints. Each endpoint method name is the snake-cased array key.

```php
use Ifds\HttpAdapter\Attributes\Driver;
use Ifds\HttpAdapter\Client\AbstractApiClient;

#[Driver('sample')]
class SampleApiClient extends AbstractApiClient
{
    protected function defineEndpoints(): array
    {
        return [
            'posts' => [
                'method' => 'GET',
                'path'   => '/posts',
            ],
            'show_post' => [
                'method' => 'GET',
                'path'   => '/posts/{id}',
            ],
            'create_post' => [
                'method' => 'POST',
                'path'   => '/posts',
            ],
        ];
    }

    // Optional: a typed convenience method over the generic builder.
    public function commentsFor(int $postId): array
    {
        return $this->showPost()
            ->withPathParam('id', $postId)
            ->withHeaders(['x-api-key' => $this->config->extra('api_key')])
            ->send()
            ->json();
    }
}
```

Endpoint methods are resolved through `__call`, so static analysers can't see them.
Declare them on the class if you run PHPStan/Psalm:

```php
/**
 * @method $this posts()
 * @method $this showPost()
 * @method $this createPost(array|AbstractAdapterRequest $payload = [])
 */
#[Driver('sample')]
class SampleApiClient extends AbstractApiClient { /* ... */ }
```

### 2. Register it in config

Add an entry to `config/http-adapter.php`. The config key **must** match the
`#[Driver]` attribute name — a mismatch fails fast at resolution time.

```php
'drivers' => [
    'sample' => [
        'client'    => \App\Adapters\SampleApiClient::class,
        'base_url'  => env('SAMPLE_API_URL'),
        'stage_url' => 'https://jsonplaceholder.typicode.com', // fallback when base_url is empty
        'timeout'   => 30,
        'mock_enabled' => env('SAMPLE_API_MOCK', false),
        'retry' => ['times' => 3, 'backoff_ms' => 200],
        'circuit_breaker' => ['enabled' => true, 'failure_threshold' => 5, 'cooldown_seconds' => 30],
        'extra' => ['api_key' => env('SAMPLE_API_KEY')],
    ],
],
```

Driver config is read from the container's config repository **at resolve time**, not
snapshotted at boot, so drivers registered later in the request lifecycle (tests,
feature flags, runtime tenancy) resolve correctly.

### 3. Call it

```php
use Ifds\HttpAdapter\Facades\HttpAdapter;

$response = HttpAdapter::driver('sample')->posts()->send();
$data = $response->json();

// Or via a convenience method on your client:
$comments = HttpAdapter::driver('sample')->commentsFor(1);
```

## The fluent builder

Every endpoint call returns the client so you can shape the request before `send()`:

```php
HttpAdapter::driver('sample')
    ->createPost($payload)          // sets the active endpoint + JSON body
    ->withPathParam('id', 1)        // interpolate {id} in the path
    ->withQueryParam('page', 2)     // ?page=2
    ->withHeaders(['x-api-key' => '...'])
    ->setTimeout(10)                // per-call timeout override
    ->withRetry(times: 2, backoffMs: 100) // per-call retry override
    ->withoutCircuitBreaker()       // opt this call out of the breaker
    ->send();                       // returns Illuminate\Http\Client\Response
```

Endpoint methods accept either an array or an `AbstractAdapterRequest` DTO as their
first argument; a DTO is converted to a snake-cased payload automatically.

A resolved driver is memoised for the lifetime of the manager. If you change a
driver's config at runtime, drop the cached instance first:

```php
config()->set('http-adapter.drivers.sample.mock_enabled', true);
HttpAdapter::forgetDrivers();
```

## Resilience

### Retry

Configured per driver (or per endpoint, or per call via `withRetry()`). Uses Laravel's
own `Http::retry()` under the hood. **Only idempotent methods (GET/HEAD/PUT/DELETE) are
retried by default** — retrying a POST could double-submit, so you must opt in
explicitly:

```php
'retry' => ['times' => 3, 'backoff_ms' => 200, 'retry_non_idempotent' => true],
```

The default policy retries connection errors and 5xx responses. Pass a custom `when`
closure to `withRetry()` for full control.

### Circuit breaker

Opt-in per driver. After `failure_threshold` consecutive failures the breaker **opens**
and further calls short-circuit with a `CircuitOpenException` (no network call) until
`cooldown_seconds` elapse, after which a single trial call is allowed (**half-open**);
success closes it, failure re-opens it. State is persisted in the cache (default:
`config('cache.default')`), so it is shared across processes and workers.

```php
'circuit_breaker' => ['enabled' => true, 'failure_threshold' => 5, 'cooldown_seconds' => 30],
```

## Observability

Every call emits lifecycle events you can listen to like any Laravel event:

| Event | When |
|---|---|
| `RequestSending` | immediately before dispatch |
| `RequestRetrying` | before each retried attempt |
| `ResponseReceived` | after a response (live or mocked) is obtained |
| `RequestFailed` | on a connection/request failure or an open circuit |
| `CircuitStateChanged` | on any breaker state transition |

```php
use Ifds\HttpAdapter\Events\RequestFailed;

Event::listen(RequestFailed::class, function (RequestFailed $event) {
    Sentry::captureMessage("[{$event->driver}] {$event->endpoint} failed: {$event->reason->value}");
});
```

### Default logging

A built-in `LogHttpAdapterActivity` listener logs every event. It writes to
`config('http-adapter.log_channel')`, falling back to your app's default channel.
Disable it entirely with `'logging' => ['enabled' => false]`.

## Testing

Flip `mock_enabled` (or set the driver's mock env var) and any endpoint with a `mock`
entry serves its canned response with no network call:

```php
'create_post' => [
    'method' => 'POST',
    'path'   => '/posts',
    'mock'   => fn (array $payload, array $headers) => ['id' => 101] + $payload,
],
```

The mock may be an array, an `Illuminate\Http\Client\Response`, or a closure returning
either. Mock data lives in your app, never in this package. Endpoints without a `mock`
entry always go live, even when `mock_enabled` is true.

## DTOs & tolerant parsing

`AbstractAdapterRequest` (reflection-based `toPayload()`) and `AbstractAdapterResponse`
(`fromHttpResponse()` mapping) give you typed request/response objects. The
`ExtractsTolerantFields` trait helps parse third-party APIs with inconsistent key
casing (`isSuccess` vs `IsSuccess`, `data` vs `Data`):

```php
use Ifds\HttpAdapter\Support\ExtractsTolerantFields;

class PostResult
{
    use ExtractsTolerantFields;

    public function __construct(array $payload)
    {
        $this->ok    = $this->extractBool($payload, ['isSuccess', 'IsSuccess']);
        $this->items = $this->extractCollection($payload, ['data', 'Data', 'items']);
    }
}
```

## Development

```bash
composer install
composer test     # Pest via Orchestra Testbench
composer pint     # format
composer stan     # PHPStan / Larastan
```

The suite never touches the network — every test stubs the transport with
`Http::fake()` — but the fixture client models the JSONPlaceholder sample API so the
endpoint shapes under test stay realistic.

### Workbench

`workbench/` is a real, booted Laravel app that registers the same sample driver, for
poking at the package by hand:

```bash
vendor/bin/testbench http-adapter:sample 1      # live call to the sample API, plus the mock path
vendor/bin/testbench http-adapter:circuit-demo  # drive the breaker closed -> open (transport faked)
composer serve                                  # boot the workbench app over HTTP
```

### A note on the Laravel 11 CI matrix

Laravel 11 is past its security-fix window, so every 11.x release now carries an open
advisory and Composer refuses to install it by default. The package still supports and
tests 11.x; the CI job opts that ephemeral checkout out of the block with
`composer config --no-plugins audit.block-insecure false`. Your own applications should
leave that check on.

## License

MIT — see [LICENSE](LICENSE).
