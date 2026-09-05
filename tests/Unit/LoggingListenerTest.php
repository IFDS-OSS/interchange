<?php

use Ifds\HttpAdapter\Events\RequestSending;
use Ifds\HttpAdapter\Listeners\LogHttpAdapterActivity;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

it('logs to the explicitly configured http-adapter.log_channel', function () {
    config()->set('http-adapter.log_channel', 'my-channel');

    $logger = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->once()->with('my-channel')->andReturn($logger);

    (new LogHttpAdapterActivity)->onRequestSending(
        new RequestSending('fake', 'ping', 'GET', 'https://fake.test/ping', null, [])
    );

    $logger->shouldHaveReceived('info')->once();
});

it('falls back to the application default channel when log_channel is null', function () {
    config()->set('http-adapter.log_channel', null);
    config()->set('logging.default', 'stack');

    $logger = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->once()->with('stack')->andReturn($logger);

    (new LogHttpAdapterActivity)->onRequestSending(
        new RequestSending('fake', 'ping', 'GET', 'https://fake.test/ping', null, [])
    );

    $logger->shouldHaveReceived('info')->once();
});
