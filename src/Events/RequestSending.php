<?php

namespace Ifds\Interchange\Events;

class RequestSending
{
    /**
     * @param  array<string, mixed>|null  $payload
     * @param  array<string, mixed>  $headers
     */
    public function __construct(
        public readonly string $driver,
        public readonly string $endpoint,
        public readonly string $method,
        public readonly string $url,
        public readonly ?array $payload,
        public readonly array $headers,
    ) {}
}
