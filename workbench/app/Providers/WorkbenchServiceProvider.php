<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Workbench\App\Clients\SampleApiClient;
use Workbench\App\Console\Commands\CircuitDemoCommand;
use Workbench\App\Console\Commands\SampleApiCommand;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Persist circuit-breaker state in the in-memory array store for the
        // demo (the workbench app's default cache store is the database driver,
        // which has no cache table).
        config()->set('interchange.circuit_breaker.store', 'array');

        // Register the sample driver so the package has something to resolve in
        // the booted workbench app. The circuit breaker is enabled with a low
        // threshold so the demo command can trip it quickly.
        config()->set('interchange.drivers.sample', [
            'client' => SampleApiClient::class,
            'base_url' => env('SAMPLE_API_URL', 'https://jsonplaceholder.typicode.com'),
            'timeout' => 10,
            'mock_enabled' => env('SAMPLE_API_MOCK', false),
            'retry' => ['times' => 2, 'backoff_ms' => 200],
            'circuit_breaker' => ['enabled' => true, 'failure_threshold' => 3, 'cooldown_seconds' => 30],
            'extra' => [],
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SampleApiCommand::class,
                CircuitDemoCommand::class,
            ]);
        }
    }
}
