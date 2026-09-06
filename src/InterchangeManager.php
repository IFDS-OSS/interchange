<?php

namespace Ifds\Interchange;

use Ifds\Interchange\Client\AbstractApiClient;
use Ifds\Interchange\Exceptions\NoDefaultDriverException;
use Ifds\Interchange\Support\DriverRegistrar;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Manager;

class InterchangeManager extends Manager
{
    private DriverRegistrar $registrar;

    public function __construct(Container $container, ?DriverRegistrar $registrar = null)
    {
        parent::__construct($container);

        $this->registrar = $registrar ?? new DriverRegistrar;
    }

    /**
     * This package has no sensible "default" integration — every call names its
     * driver explicitly.
     */
    public function getDefaultDriver(): string
    {
        throw NoDefaultDriverException::make();
    }

    /**
     * The key type is left open because Laravel narrowed the parent's
     * `$parameters` docblock from `array` to `array<string, mixed>` in 13.x,
     * and this override has to stay compatible across 11.x-13.x.
     *
     * @param  array<array-key, mixed>  $parameters
     * @return AbstractApiClient
     */
    public function __call($method, $parameters)
    {
        return $this->driver($method);
    }

    /**
     * Resolve a driver straight from the live config, so drivers registered (or
     * reconfigured) after the manager was constructed still resolve correctly.
     *
     * @param  string  $driver
     * @return AbstractApiClient
     */
    protected function createDriver($driver)
    {
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        return $this->registrar->resolve($this->container, $driver);
    }
}
