<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Workbench\App\Adapters\DemoClient;
use Workbench\App\Console\Commands\CircuitDemoCommand;

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
        config()->set('http-adapter.circuit_breaker.store', 'array');

        // Register a demo driver so the package has something to resolve in the
        // booted workbench app. Circuit breaker is enabled with a low threshold
        // so the demo command can trip it quickly.
        config()->set('http-adapter.drivers.demo', [
            'client' => DemoClient::class,
            'base_url' => 'https://httpbin.org',
            'timeout' => 5,
            'mock_enabled' => false,
            'retry' => [],
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
                CircuitDemoCommand::class,
            ]);
        }
    }
}
