<?php

namespace Ifds\HttpAdapter\Events;

use Ifds\HttpAdapter\Support\CircuitState;

class CircuitStateChanged
{
    public function __construct(
        public readonly string $driver,
        public readonly CircuitState $from,
        public readonly CircuitState $to,
    ) {}
}
