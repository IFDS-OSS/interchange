<?php

namespace Ifds\HttpAdapter\Support;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Throwable;

final class RetryPolicy
{
    private const IDEMPOTENT_METHODS = ['GET', 'HEAD', 'PUT', 'DELETE'];

    public function __construct(
        public readonly int $times = 0,
        public readonly int|Closure $backoffMs = 200,
        public readonly ?Closure $when = null,
        public readonly bool $retryNonIdempotent = false,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            times: (int) ($config['times'] ?? 0),
            backoffMs: $config['backoff_ms'] ?? 200,
            when: null,
            retryNonIdempotent: (bool) ($config['retry_non_idempotent'] ?? false),
        );
    }

    public function shouldRetry(string $method, Throwable $exception): bool
    {
        if ($this->when instanceof Closure) {
            return (bool) ($this->when)($exception);
        }

        if (! $this->retryNonIdempotent && ! in_array(strtoupper($method), self::IDEMPOTENT_METHODS, true)) {
            return false;
        }

        if ($exception instanceof ConnectionException) {
            return true;
        }

        if ($exception instanceof RequestException) {
            return $exception->response->status() >= 500;
        }

        return false;
    }
}
