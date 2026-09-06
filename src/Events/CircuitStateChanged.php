<?php

namespace Ifds\Interchange\Events;

use Ifds\Interchange\Enums\CircuitState;

class CircuitStateChanged
{
    public function __construct(
        public readonly string $driver,
        public readonly CircuitState $from,
        public readonly CircuitState $to,
    ) {}
}
