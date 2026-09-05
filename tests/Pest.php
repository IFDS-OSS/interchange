<?php

use Ifds\HttpAdapter\Client\AbstractApiClient;
use Ifds\HttpAdapter\Tests\TestCase;

uses(TestCase::class)->in('Unit');

/**
 * Resolve the pre-registered 'fake' driver from the container.
 */
function fakeDriver(): AbstractApiClient
{
    return app('http-adapter')->driver('fake');
}
