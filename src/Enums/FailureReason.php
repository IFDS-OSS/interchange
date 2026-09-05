<?php

namespace Ifds\HttpAdapter\Enums;

enum FailureReason: string
{
    case Network = 'network';
    case ServerError = 'server_error';
    case CircuitOpen = 'circuit_open';
}
