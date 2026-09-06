<?php

namespace Ifds\Interchange\Data;

use Illuminate\Support\Str;

/**
 * @phpstan-consistent-constructor
 */
abstract class AbstractAdapterRequest
{
    /**
     * @param  mixed  ...$args
     */
    public static function make(...$args): static
    {
        return new static(...$args);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $payload = [];

        foreach (get_object_vars($this) as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $payload[Str::snake($key)] = $value;
        }

        return $payload;
    }
}
