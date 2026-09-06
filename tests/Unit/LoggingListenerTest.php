<?php

use Ifds\Interchange\Events\RequestSending;
use Ifds\Interchange\Listeners\LogInterchangeActivity;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

it('logs to the explicitly configured interchange.log_channel', function () {
    config()->set('interchange.log_channel', 'my-channel');

    $logger = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->once()->with('my-channel')->andReturn($logger);

    (new LogInterchangeActivity)->onRequestSending(
        new RequestSending('sample', 'posts', 'GET', 'https://jsonplaceholder.typicode.com/posts', null, [])
    );

    $logger->shouldHaveReceived('info')->once();
});

it('falls back to the application default channel when log_channel is null', function () {
    config()->set('interchange.log_channel', null);
    config()->set('logging.default', 'stack');

    $logger = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->once()->with('stack')->andReturn($logger);

    (new LogInterchangeActivity)->onRequestSending(
        new RequestSending('sample', 'posts', 'GET', 'https://jsonplaceholder.typicode.com/posts', null, [])
    );

    $logger->shouldHaveReceived('info')->once();
});
