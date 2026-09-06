<?php

namespace Ifds\Interchange\Support;

use Ifds\Interchange\Exceptions\InvalidDriverConfigException;

final class DriverConfig
{
    /**
     * @param  array<string, mixed>  $retry
     * @param  array<string, mixed>  $circuitBreaker
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public readonly string $name,
        public readonly string $baseUrl,
        public readonly int $timeout,
        public readonly bool $mockEnabled,
        public readonly array $retry,
        public readonly array $circuitBreaker,
        public readonly array $extra,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(string $name, array $config): self
    {
        $baseUrl = $config['base_url'] ?? $config['stage_url'] ?? null;

        if (empty($baseUrl)) {
            throw InvalidDriverConfigException::missingBaseUrl($name);
        }

        return new self(
            name: $name,
            baseUrl: $baseUrl,
            timeout: (int) ($config['timeout'] ?? 30),
            mockEnabled: (bool) ($config['mock_enabled'] ?? false),
            retry: $config['retry'] ?? [],
            circuitBreaker: $config['circuit_breaker'] ?? [],
            extra: $config['extra'] ?? [],
        );
    }

    public function extra(string $key, mixed $default = null): mixed
    {
        return $this->extra[$key] ?? $default;
    }
}
