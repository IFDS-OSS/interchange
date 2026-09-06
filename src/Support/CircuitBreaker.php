<?php

namespace Ifds\Interchange\Support;

use Ifds\Interchange\Enums\CircuitState;
use Ifds\Interchange\Events\CircuitStateChanged;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;

final class CircuitBreaker
{
    public function __construct(
        private readonly Repository $cache,
        private readonly string $driver,
        private readonly int $failureThreshold = 5,
        private readonly int $cooldownSeconds = 30,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(Repository $cache, string $driver, array $config): self
    {
        return new self(
            cache: $cache,
            driver: $driver,
            failureThreshold: (int) ($config['failure_threshold'] ?? 5),
            cooldownSeconds: (int) ($config['cooldown_seconds'] ?? 30),
        );
    }

    public function isOpen(): bool
    {
        $data = $this->read();

        if ($data['state'] === CircuitState::Closed->value) {
            return false;
        }

        if ($data['state'] === CircuitState::Open->value) {
            $elapsed = Carbon::now()->getTimestamp() - $data['opened_at'];

            if ($elapsed >= $this->cooldownSeconds) {
                $this->transition(CircuitState::Open, CircuitState::HalfOpen, $data);

                return false;
            }

            return true;
        }

        // HalfOpen: allow the trial request through.
        return false;
    }

    public function recordOutcome(bool $success): void
    {
        $data = $this->read();
        $state = CircuitState::from($data['state']);

        if ($success) {
            if ($state !== CircuitState::Closed) {
                $this->transition($state, CircuitState::Closed, $data, resetFailures: true);
            } else {
                $this->write(['state' => CircuitState::Closed->value, 'failures' => 0, 'opened_at' => null]);
            }

            return;
        }

        if ($state === CircuitState::HalfOpen) {
            $this->transition($state, CircuitState::Open, $data, openNow: true);

            return;
        }

        $failures = $data['failures'] + 1;

        if ($failures >= $this->failureThreshold) {
            $this->transition($state, CircuitState::Open, $data, openNow: true);

            return;
        }

        $this->write(['state' => CircuitState::Closed->value, 'failures' => $failures, 'opened_at' => null]);
    }

    /**
     * @param  array{state: string, failures: int, opened_at: int|null}  $data
     */
    private function transition(CircuitState $from, CircuitState $to, array $data, bool $resetFailures = false, bool $openNow = false): void
    {
        $this->write([
            'state' => $to->value,
            'failures' => $resetFailures ? 0 : $data['failures'],
            'opened_at' => $openNow ? Carbon::now()->getTimestamp() : $data['opened_at'],
        ]);

        event(new CircuitStateChanged($this->driver, $from, $to));
    }

    /**
     * @return array{state: string, failures: int, opened_at: int|null}
     */
    private function read(): array
    {
        return $this->cache->get($this->cacheKey(), [
            'state' => CircuitState::Closed->value,
            'failures' => 0,
            'opened_at' => null,
        ]);
    }

    /**
     * @param  array{state: string, failures: int, opened_at: int|null}  $data
     */
    private function write(array $data): void
    {
        $this->cache->forever($this->cacheKey(), $data);
    }

    private function cacheKey(): string
    {
        return "interchange:circuit:{$this->driver}";
    }
}
