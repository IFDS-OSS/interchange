<?php

namespace Ifds\Interchange\Facades;

use Ifds\Interchange\AdapterManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Ifds\Interchange\Client\AbstractApiClient driver(string $driver)
 * @method static \Ifds\Interchange\AdapterManager forgetDrivers()
 *
 * @see AdapterManager
 */
class Interchange extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'interchange';
    }
}
