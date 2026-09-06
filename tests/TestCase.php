<?php

namespace Ifds\HttpAdapter\Tests;

use Ifds\HttpAdapter\HttpAdapterServiceProvider;
use Ifds\HttpAdapter\Tests\Fixtures\SampleApiClient;
use Monolog\Handler\NullHandler;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            HttpAdapterServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');

        // A guaranteed no-op log channel so the default activity listener never
        // fails on a missing channel while other behaviour is under test.
        $app['config']->set('logging.default', 'null');
        $app['config']->set('logging.channels.null', [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ]);

        $app['config']->set('http-adapter.drivers.sample', [
            'client' => SampleApiClient::class,
            'base_url' => 'https://jsonplaceholder.typicode.com',
            'timeout' => 5,
            'mock_enabled' => false,
            'retry' => [],
            'circuit_breaker' => [],
            'extra' => ['api_key' => 'test-key'],
        ]);
    }

    /**
     * Merge extra config into the 'sample' driver definition for a single test.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function configureSampleDriver(array $overrides): void
    {
        $existing = config('http-adapter.drivers.sample', []);

        config()->set('http-adapter.drivers.sample', array_replace_recursive($existing, $overrides));
    }
}
