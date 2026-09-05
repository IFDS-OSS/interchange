<?php

namespace Ifds\HttpAdapter\Client;

use Closure;
use Ifds\HttpAdapter\Data\AbstractAdapterRequest;
use Ifds\HttpAdapter\Enums\FailureReason;
use Ifds\HttpAdapter\Events\RequestFailed;
use Ifds\HttpAdapter\Events\RequestRetrying;
use Ifds\HttpAdapter\Events\RequestSending;
use Ifds\HttpAdapter\Events\ResponseReceived;
use Ifds\HttpAdapter\Exceptions\CircuitOpenException;
use Ifds\HttpAdapter\Exceptions\NoActiveEndpointException;
use Ifds\HttpAdapter\Exceptions\UndefinedEndpointException;
use Ifds\HttpAdapter\Support\CircuitBreaker;
use Ifds\HttpAdapter\Support\DriverConfig;
use Ifds\HttpAdapter\Support\RetryPolicy;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

abstract class AbstractApiClient
{
    protected DriverConfig $config;

    protected ?string $log_group = null;

    protected ?string $active_endpoint = null;

    /**
     * @var array{headers: array<string, mixed>, timeout: int, payload: array<string, mixed>|null}
     */
    protected array $request_config = [
        'headers' => [],
        'timeout' => 30,
        'payload' => null,
    ];

    /**
     * @var array<string, mixed>
     */
    protected array $path_params = [];

    /**
     * @var array<string, mixed>
     */
    protected array $query_params = [];

    /**
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $endpoint_cache = null;

    private ?RetryPolicy $retry_override = null;

    private bool $circuit_breaker_disabled = false;

    /**
     * @return array<string, array<string, mixed>>
     */
    abstract protected function defineEndpoints(): array;

    public function configure(DriverConfig $config): static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $endpoint_key, array $arguments): static
    {
        $endpoint_key = Str::snake($endpoint_key);
        $endpoints = $this->getEndpoints();

        if (! array_key_exists($endpoint_key, $endpoints)) {
            throw UndefinedEndpointException::forKey($endpoint_key);
        }

        $this->active_endpoint = $endpoint_key;
        $this->resetRequestConfig();

        $endpoint = $endpoints[$endpoint_key];
        $this->setTimeout($endpoint['default_timeout'] ?? $this->config->timeout);

        if (isset($endpoint['required_headers'])) {
            $this->withHeaders($endpoint['required_headers']);
        }

        if (isset($arguments[0])) {
            $payload = $arguments[0];

            if ($payload instanceof AbstractAdapterRequest) {
                $this->withPayload($payload->toPayload());
            } elseif (is_array($payload)) {
                $this->withPayload($payload);
            }
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function withPathParams(array $params): static
    {
        $this->path_params = array_merge($this->path_params, $params);

        return $this;
    }

    public function withPathParam(string $key, mixed $value): static
    {
        $this->path_params[$key] = $value;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function withQueryParams(array $params): static
    {
        $this->query_params = array_merge($this->query_params, $params);

        return $this;
    }

    public function withQueryParam(string $key, mixed $value): static
    {
        $this->query_params[$key] = $value;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function withPayload(array $data): static
    {
        $this->request_config['payload'] = $data;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    public function withHeaders(array $headers): static
    {
        $this->request_config['headers'] = array_merge($this->request_config['headers'], $headers);

        return $this;
    }

    public function setTimeout(int $seconds): static
    {
        $this->request_config['timeout'] = $seconds;

        return $this;
    }

    public function setLogGroup(string $group): static
    {
        $this->log_group = $group;

        return $this;
    }

    public function withRetry(int $times, int|Closure $backoffMs = 200, ?Closure $when = null): static
    {
        $this->retry_override = new RetryPolicy($times, $backoffMs, $when, retryNonIdempotent: true);

        return $this;
    }

    public function withoutCircuitBreaker(): static
    {
        $this->circuit_breaker_disabled = true;

        return $this;
    }

    public function send(): Response
    {
        $this->validateRequest();

        $endpoint = $this->getEndpoints()[$this->active_endpoint];
        $method = strtoupper($endpoint['method'] ?? 'GET');
        $url = $this->constructFullUrl($endpoint['path'] ?? $this->active_endpoint);
        $breaker = null;

        try {
            if ($this->shouldUseMock($endpoint)) {
                return $this->prepareMockResponse($method, $url, $endpoint['mock']);
            }

            $breaker = $this->resolveCircuitBreaker($endpoint);

            if ($breaker && $breaker->isOpen()) {
                $exception = CircuitOpenException::forDriver($this->getDriverName());

                event(new RequestFailed(
                    $this->getDriverName(),
                    $this->active_endpoint,
                    $method,
                    $url,
                    $exception,
                    0.0,
                    FailureReason::CircuitOpen,
                ));

                throw $exception;
            }

            $params = $this->buildRequestParams();

            event(new RequestSending(
                $this->getDriverName(),
                $this->active_endpoint,
                $method,
                $url,
                $params['json'] ?? null,
                $params['headers'],
            ));

            $retry = $this->resolveRetryPolicy($endpoint);
            $start = microtime(true);

            $pending = Http::timeout($params['timeout']);

            if ($retry->times > 0) {
                $pending = $pending->retry(
                    $retry->times,
                    $retry->backoffMs,
                    function (Throwable $e) use ($retry, $method) {
                        $shouldRetry = $retry->shouldRetry($method, $e);

                        if ($shouldRetry) {
                            event(new RequestRetrying($this->getDriverName(), $this->active_endpoint, $e));
                        }

                        return $shouldRetry;
                    },
                    throw: false,
                );
            }

            $response = $pending->send($method, $url, $params);
            $duration = (microtime(true) - $start) * 1000;

            event(new ResponseReceived(
                $this->getDriverName(),
                $this->active_endpoint,
                $method,
                $url,
                $response,
                $duration,
                false,
            ));

            if ($response->successful()) {
                $breaker?->recordOutcome(true);
            }

            // A non-successful response throws here and is handled (and recorded
            // as a failure) exactly once in the catch block below.
            $response->throw();

            return $response;
        } catch (ConnectionException|RequestException $e) {
            $breaker?->recordOutcome(false);

            $duration = (microtime(true) - $start) * 1000;
            $reason = $e instanceof ConnectionException ? FailureReason::Network : FailureReason::ServerError;

            event(new RequestFailed($this->getDriverName(), $this->active_endpoint, $method, $url, $e, $duration, $reason));

            throw $e;
        } finally {
            $this->resetRequestConfig();
        }
    }

    protected function getDriverName(): string
    {
        return $this->config->name;
    }

    protected function getBaseUri(): string
    {
        return $this->config->baseUrl;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @param  array<string, mixed>  $endpoint
     */
    private function resolveCircuitBreaker(array $endpoint): ?CircuitBreaker
    {
        if ($this->circuit_breaker_disabled) {
            return null;
        }

        $cbConfig = $endpoint['circuit_breaker'] ?? $this->config->circuitBreaker;

        if (empty($cbConfig['enabled'])) {
            return null;
        }

        $store = config('http-adapter.circuit_breaker.store') ?: config('cache.default');

        return CircuitBreaker::fromConfig(Cache::store($store), $this->getDriverName(), $cbConfig);
    }

    /**
     * @param  array<string, mixed>  $endpoint
     */
    private function resolveRetryPolicy(array $endpoint): RetryPolicy
    {
        if ($this->retry_override) {
            return $this->retry_override;
        }

        return RetryPolicy::fromArray($endpoint['retry'] ?? $this->config->retry);
    }

    /**
     * @param  array<string, mixed>  $endpoint
     */
    private function shouldUseMock(array $endpoint): bool
    {
        if (! isset($endpoint['mock'])) {
            return false;
        }

        return $this->config->mockEnabled;
    }

    private function prepareMockResponse(string $method, string $url, mixed $mock): Response
    {
        if (is_callable($mock)) {
            $mock = $mock($this->request_config['payload'] ?? [], $this->request_config['headers']);
        }

        if ($mock instanceof Response) {
            $response = $mock;
        } else {
            $response = new Response(Http::response($mock)->wait());
        }

        event(new ResponseReceived($this->getDriverName(), $this->active_endpoint, $method, $url, $response, 0.0, true));

        return $response;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getEndpoints(): array
    {
        if ($this->endpoint_cache === null) {
            $this->endpoint_cache = $this->defineEndpoints();
        }

        return $this->endpoint_cache;
    }

    private function constructFullUrl(string $path): string
    {
        $base = rtrim($this->getBaseUri(), '/');
        $path = ltrim($path, '/');

        foreach ($this->path_params as $key => $value) {
            $path = str_replace("{{$key}}", urlencode((string) $value), $path);
        }

        $full = $base.'/'.$path;

        if (! empty($this->query_params)) {
            $full .= '?'.http_build_query($this->query_params);
        }

        return $full;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestParams(): array
    {
        $params = [
            'headers' => array_merge($this->getDefaultHeaders(), $this->request_config['headers']),
            'timeout' => $this->request_config['timeout'],
        ];

        if ($this->request_config['payload'] !== null) {
            $params['json'] = $this->request_config['payload'];
        }

        return $params;
    }

    private function validateRequest(): void
    {
        if ($this->active_endpoint === null) {
            throw NoActiveEndpointException::make();
        }

        if (! array_key_exists($this->active_endpoint, $this->getEndpoints())) {
            throw UndefinedEndpointException::forKey($this->active_endpoint);
        }
    }

    private function resetRequestConfig(): void
    {
        $this->request_config = [
            'headers' => [],
            'timeout' => 30,
            'payload' => null,
        ];
        $this->path_params = [];
        $this->query_params = [];
        $this->retry_override = null;
        $this->circuit_breaker_disabled = false;
    }
}
