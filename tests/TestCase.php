<?php

namespace Ifds\HttpAdapter\Tests;

use Ifds\HttpAdapter\HttpAdapterServiceProvider;
use Ifds\HttpAdapter\Tests\Fixtures\FakeApiClient;
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

        $app['config']->set('http-adapter.drivers.fake', [
            'client' => FakeApiClient::class,
            'base_url' => 'https://fake.test',
            'timeout' => 5,
            'mock_enabled' => false,
            'retry' => [],
            'circuit_breaker' => [],
            'extra' => ['api_key' => 'test-key'],
        ]);
    }

    /**
     * Merge extra config into the 'fake' driver definition for a single test.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function configureFakeDriver(array $overrides): void
    {
        $existing = config('http-adapter.drivers.fake', []);

        config()->set('http-adapter.drivers.fake', array_replace_recursive($existing, $overrides));
    }
}
