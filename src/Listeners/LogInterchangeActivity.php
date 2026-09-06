<?php

namespace Ifds\Interchange\Listeners;

use Ifds\Interchange\Events\CircuitStateChanged;
use Ifds\Interchange\Events\RequestFailed;
use Ifds\Interchange\Events\RequestRetrying;
use Ifds\Interchange\Events\RequestSending;
use Ifds\Interchange\Events\ResponseReceived;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class LogInterchangeActivity
{
    public function onRequestSending(RequestSending $event): void
    {
        $this->channel()->info("HTTP {$event->method} to {$event->url}", [
            'type' => 'request',
            'driver' => $event->driver,
            'endpoint' => $event->endpoint,
            'method' => $event->method,
            'url' => $event->url,
            'payload' => $event->payload,
            'headers' => $event->headers,
        ]);
    }

    public function onRequestRetrying(RequestRetrying $event): void
    {
        $this->channel()->warning("HTTP retry for [{$event->driver}::{$event->endpoint}]: {$event->exception->getMessage()}", [
            'type' => 'retry',
            'driver' => $event->driver,
            'endpoint' => $event->endpoint,
        ]);
    }

    public function onResponseReceived(ResponseReceived $event): void
    {
        $status = $event->response->status();
        $level = $event->response->successful() ? 'info' : 'warning';

        $this->channel()->log($level, "HTTP {$status} in {$event->durationMs}ms", [
            'type' => 'response',
            'driver' => $event->driver,
            'endpoint' => $event->endpoint,
            'status' => $status,
            'duration_ms' => $event->durationMs,
            'mocked' => $event->mocked,
        ]);
    }

    public function onRequestFailed(RequestFailed $event): void
    {
        $this->channel()->error("HTTP error on [{$event->driver}::{$event->endpoint}]: {$event->exception->getMessage()}", [
            'type' => 'error',
            'driver' => $event->driver,
            'endpoint' => $event->endpoint,
            'method' => $event->method,
            'url' => $event->url,
            'reason' => $event->reason->value,
            'duration_ms' => $event->durationMs,
        ]);
    }

    public function onCircuitStateChanged(CircuitStateChanged $event): void
    {
        $this->channel()->warning("Circuit for [{$event->driver}] transitioned {$event->from->value} -> {$event->to->value}", [
            'type' => 'circuit_state_changed',
            'driver' => $event->driver,
            'from' => $event->from->value,
            'to' => $event->to->value,
        ]);
    }

    private function channel(): LoggerInterface
    {
        return Log::channel(config('interchange.log_channel') ?? config('logging.default'));
    }
}
