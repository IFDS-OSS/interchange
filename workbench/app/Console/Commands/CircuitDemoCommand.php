<?php

namespace Workbench\App\Console\Commands;

use Ifds\Interchange\Enums\CircuitState;
use Ifds\Interchange\Events\CircuitStateChanged;
use Ifds\Interchange\Exceptions\CircuitOpenException;
use Ifds\Interchange\Facades\Interchange;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

class CircuitDemoCommand extends Command
{
    protected $signature = 'interchange:circuit-demo';

    protected $description = 'Drive the sample driver through failures to trip the circuit breaker (exercises the CircuitState enum in a booted app).';

    public function handle(): int
    {
        // Report every breaker transition, printing the CircuitState enum values.
        Event::listen(CircuitStateChanged::class, function (CircuitStateChanged $event): void {
            $this->line(sprintf(
                '  <fg=yellow>circuit</> [%s] %s -> %s',
                $event->driver,
                $event->from->value,
                $event->to->value,
            ));
        });

        // Force every upstream call to fail so the breaker trips.
        Http::fake(['*' => Http::response(['error' => true], 500)]);

        $this->info('Sending failing requests through the "sample" driver (threshold: 3)...');

        foreach (range(1, 3) as $attempt) {
            try {
                Interchange::driver('sample')->showPost()->withPathParam('id', 1)->send();
            } catch (RequestException $e) {
                $this->line("  attempt {$attempt}: <fg=red>failed</> ({$e->response->status()})");
            }
        }

        $this->newLine();
        $this->info('Breaker should now be OPEN — the next call must short-circuit:');

        try {
            Interchange::driver('sample')->showPost()->withPathParam('id', 1)->send();
            $this->error('  Expected the circuit to be open, but the call went through.');

            return self::FAILURE;
        } catch (CircuitOpenException $e) {
            $this->line('  <fg=green>short-circuited</> — no network call made');
        }

        $state = CircuitState::Open;
        $this->newLine();
        $this->info("Confirmed: circuit is {$state->name} ({$state->value}).");

        return self::SUCCESS;
    }
}
