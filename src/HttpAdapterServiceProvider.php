<?php

namespace Ifds\HttpAdapter;

use Ifds\HttpAdapter\Events\CircuitStateChanged;
use Ifds\HttpAdapter\Events\RequestFailed;
use Ifds\HttpAdapter\Events\RequestRetrying;
use Ifds\HttpAdapter\Events\RequestSending;
use Ifds\HttpAdapter\Events\ResponseReceived;
use Ifds\HttpAdapter\Listeners\LogHttpAdapterActivity;
use Ifds\HttpAdapter\Support\DriverRegistrar;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class HttpAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/http-adapter.php', 'http-adapter');

        $this->app->singleton(AdapterManager::class, fn ($app) => new AdapterManager($app, new DriverRegistrar));

        $this->app->alias(AdapterManager::class, 'http-adapter');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/http-adapter.php' => $this->app->configPath('http-adapter.php'),
        ], 'http-adapter-config');

        if ($this->app['config']->get('http-adapter.logging.enabled', true)) {
            Event::listen(RequestSending::class, [LogHttpAdapterActivity::class, 'onRequestSending']);
            Event::listen(RequestRetrying::class, [LogHttpAdapterActivity::class, 'onRequestRetrying']);
            Event::listen(ResponseReceived::class, [LogHttpAdapterActivity::class, 'onResponseReceived']);
            Event::listen(RequestFailed::class, [LogHttpAdapterActivity::class, 'onRequestFailed']);
            Event::listen(CircuitStateChanged::class, [LogHttpAdapterActivity::class, 'onCircuitStateChanged']);
        }
    }
}
