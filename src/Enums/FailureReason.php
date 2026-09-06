<?php

namespace Ifds\Interchange\Enums;

enum FailureReason: string
{
    case Network = 'network';
    case ServerError = 'server_error';
    case CircuitOpen = 'circuit_open';
}
