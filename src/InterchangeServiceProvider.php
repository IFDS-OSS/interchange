<?php

namespace Ifds\Interchange;

use Ifds\Interchange\Events\CircuitStateChanged;
use Ifds\Interchange\Events\RequestFailed;
use Ifds\Interchange\Events\RequestRetrying;
use Ifds\Interchange\Events\RequestSending;
use Ifds\Interchange\Events\ResponseReceived;
use Ifds\Interchange\Listeners\LogInterchangeActivity;
use Ifds\Interchange\Support\DriverRegistrar;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class InterchangeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/interchange.php', 'interchange');

        $this->app->singleton(AdapterManager::class, fn ($app) => new AdapterManager($app, new DriverRegistrar));

        $this->app->alias(AdapterManager::class, 'interchange');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/interchange.php' => $this->app->configPath('interchange.php'),
        ], 'interchange-config');

        if ($this->app['config']->get('interchange.logging.enabled', true)) {
            Event::listen(RequestSending::class, [LogInterchangeActivity::class, 'onRequestSending']);
            Event::listen(RequestRetrying::class, [LogInterchangeActivity::class, 'onRequestRetrying']);
            Event::listen(ResponseReceived::class, [LogInterchangeActivity::class, 'onResponseReceived']);
            Event::listen(RequestFailed::class, [LogInterchangeActivity::class, 'onRequestFailed']);
            Event::listen(CircuitStateChanged::class, [LogInterchangeActivity::class, 'onCircuitStateChanged']);
        }
    }
}
