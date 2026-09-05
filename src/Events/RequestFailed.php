<?php

namespace Ifds\HttpAdapter\Events;

use Ifds\HttpAdapter\Enums\FailureReason;
use Throwable;

class RequestFailed
{
    public function __construct(
        public readonly string $driver,
        public readonly string $endpoint,
        public readonly string $method,
        public readonly string $url,
        public readonly Throwable $exception,
        public readonly float $durationMs,
        public readonly FailureReason $reason = FailureReason::Network,
    ) {}
}
