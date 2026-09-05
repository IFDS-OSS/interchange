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

### 1. Write a client for the API

Extend `AbstractApiClient`, tag it with the `#[Driver]` attribute, and describe its
endpoints. Each endpoint method name is the snake-cased array key.

```php
use Ifds\HttpAdapter\Attributes\Driver;
use Ifds\HttpAdapter\Client\AbstractApiClient;

#[Driver('postex')]
class PostexClient extends AbstractApiClient
{
    protected function defineEndpoints(): array
    {
        return [
            'quotes' => [
                'method' => 'POST',
                'path'   => '/api/v1/quotes',
            ],
            'tracking' => [
                'method' => 'GET',
                'path'   => '/api/v1/tracking/{code}',
            ],
        ];
    }

    // Optional: a typed convenience method over the generic builder.
    public function trackingFor(string $code): array
    {
        return $this->tracking()
            ->withPathParam('code', $code)
            ->withHeaders(['x-api-key' => $this->config->extra('api_key')])
            ->send()
            ->json();
    }
}
```

### 2. Register it in config

Add an entry to `config/http-adapter.php`. The config key **must** match the
`#[Driver]` attribute name — a mismatch fails fast at resolution time.

```php
'drivers' => [
    'postex' => [
        'client'    => \App\Adapters\PostexClient::class,
        'base_url'  => env('POSTEX_API_URL'),
        'stage_url' => 'https://stage.postex.ir',   // fallback when base_url is empty
        'timeout'   => 30,
        'mock_enabled' => env('POSTEX_MOCK', false),
        'retry' => ['times' => 3, 'backoff_ms' => 200],
        'circuit_breaker' => ['enabled' => true, 'failure_threshold' => 5, 'cooldown_seconds' => 30],
        'extra' => ['api_key' => env('POSTEX_API_KEY')],
    ],
],
```

### 3. Call it

```php
use Ifds\HttpAdapter\Facades\HttpAdapter;

$response = HttpAdapter::driver('postex')->quotes(['weight' => 500])->send();
$data = $response->json();

// Or via a convenience method on your client:
$tracking = HttpAdapter::driver('postex')->trackingFor('ABC123');
```

## The fluent builder

Every endpoint call returns the client so you can shape the request before `send()`:

```php
HttpAdapter::driver('postex')
    ->quotes($payload)              // sets the active endpoint + JSON body
    ->withPathParam('code', 'X1')   // interpolate {code} in the path
    ->withQueryParam('page', 2)     // ?page=2
    ->withHeaders(['x-api-key' => '...'])
    ->setTimeout(10)                // per-call timeout override
    ->withRetry(times: 2, backoffMs: 100) // per-call retry override
    ->withoutCircuitBreaker()       // opt this call out of the breaker
    ->send();                       // returns Illuminate\Http\Client\Response
```

Endpoint methods accept either an array or an `AbstractAdapterRequest` DTO as their
first argument; a DTO is converted to a snake-cased payload automatically.

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

Flip `mock_enabled` (or set the `{DRIVER}_MOCK` env var) and any endpoint with a `mock`
entry serves its canned response with no network call:

```php
'quotes' => [
    'method' => 'POST',
    'path'   => '/api/v1/quotes',
    'mock'   => fn (array $payload, array $headers) => ['price' => 1000],
],
```

The mock may be an array, an `Illuminate\Http\Client\Response`, or a closure returning
either. Mock data lives in your app, never in this package.

## DTOs & tolerant parsing

`AbstractAdapterRequest` (reflection-based `toPayload()`) and `AbstractAdapterResponse`
(`fromHttpResponse()` mapping) give you typed request/response objects. The
`ExtractsTolerantFields` trait helps parse third-party APIs with inconsistent key
casing (`isSuccess` vs `IsSuccess`, `data` vs `Data`):

```php
use Ifds\HttpAdapter\Support\ExtractsTolerantFields;

class QuoteResult
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

## License

MIT — see [LICENSE](LICENSE).
