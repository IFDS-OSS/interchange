<?php

namespace Ifds\Interchange\Data;

use Ifds\Interchange\Support\ExtractsTolerantFields;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use ReflectionProperty;

/**
 * @phpstan-consistent-constructor
 */
abstract class AbstractResponse
{
    use ExtractsTolerantFields;

    protected ?Response $httpResponse = null;

    /**
     * @var array<string, mixed>
     */
    private array $overrides = [];

    public static function make(): static
    {
        return new static;
    }

    public static function fromHttpResponse(Response $response): static
    {
        $instance = new static;
        $instance->httpResponse = $response;

        foreach ((array) ($response->json() ?? []) as $key => $value) {
            $property = Str::camel((string) $key);

            if (property_exists($instance, $property)) {
                $reflection = new ReflectionProperty($instance, $property);
                $reflection->setAccessible(true);
                $reflection->setValue($instance, $value);
            } else {
                $instance->overrides[$property] = $value;
            }
        }

        return $instance->transform();
    }

    public function transform(): static
    {
        return $this;
    }

    public function __get(string $name): mixed
    {
        return $this->overrides[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->overrides[$name] = $value;
    }

    public function http(): ?Response
    {
        return $this->httpResponse;
    }

    public function successful(): bool
    {
        return (bool) $this->httpResponse?->successful();
    }

    public function failed(): bool
    {
        return ! $this->successful();
    }

    public function status(): int
    {
        return $this->httpResponse?->status() ?? 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function json(): array
    {
        return (array) ($this->httpResponse?->json() ?? []);
    }
}
