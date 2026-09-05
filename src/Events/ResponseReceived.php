<?php

namespace Ifds\HttpAdapter\Events;

use Illuminate\Http\Client\Response;

class ResponseReceived
{
    public function __construct(
        public readonly string $driver,
        public readonly string $endpoint,
        public readonly string $method,
        public readonly string $url,
        public readonly Response $response,
        public readonly float $durationMs,
        public readonly bool $mocked,
    ) {}
}
