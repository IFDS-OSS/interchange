<?php

use Ifds\Interchange\Client\AbstractApiClient;
use Ifds\Interchange\Tests\TestCase;

uses(TestCase::class)->in('Unit');

/**
 * Resolve the pre-registered 'sample' driver from the container.
 */
function sampleDriver(): AbstractApiClient
{
    return app('interchange')->driver('sample');
}
