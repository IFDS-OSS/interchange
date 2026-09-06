<?php

use Ifds\HttpAdapter\Client\AbstractApiClient;
use Ifds\HttpAdapter\Tests\TestCase;

uses(TestCase::class)->in('Unit');

/**
 * Resolve the pre-registered 'sample' driver from the container.
 */
function sampleDriver(): AbstractApiClient
{
    return app('http-adapter')->driver('sample');
}
