<?php

namespace Ifds\HttpAdapter\Support;

trait ExtractsTolerantFields
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    protected function extractString(array $payload, array $keys, string $default = ''): string
    {
        $value = $this->firstPresentValue($payload, $keys);

        return $value === null ? $default : (string) $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    protected function extractNullableString(array $payload, array $keys): ?string
    {
        $value = $this->firstPresentValue($payload, $keys);

        return $value === null ? null : (string) $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    protected function extractInt(array $payload, array $keys, int $default = 0): int
    {
        $value = $this->firstPresentValue($payload, $keys);

        return $value === null ? $default : (int) $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    protected function extractNullableInt(array $payload, array $keys): ?int
    {
        $value = $this->firstPresentValue($payload, $keys);

        return $value === null ? null : (int) $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    protected function extractBool(array $payload, array $keys, bool $default = false): bool
    {
        $value = $this->firstPresentValue($payload, $keys);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $candidateKeys
     * @return array<int|string, mixed>
     */
    protected function extractCollection(array $payload, array $candidateKeys = ['data', 'Data', 'items']): array
    {
        $value = $this->firstPresentValue($payload, $candidateKeys);

        return is_array($value) ? $value : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    private function firstPresentValue(array $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] !== null) {
                return $payload[$key];
            }
        }

        return null;
    }
}
