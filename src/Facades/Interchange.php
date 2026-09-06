<?php

namespace Ifds\Interchange\Facades;

use Ifds\Interchange\InterchangeManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Ifds\Interchange\Client\AbstractApiClient driver(string $driver)
 * @method static \Ifds\Interchange\InterchangeManager forgetDrivers()
 *
 * @see InterchangeManager
 */
class Interchange extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'interchange';
    }
}
