<?php

use Ifds\Interchange\Exceptions\InvalidDriverConfigException;
use Ifds\Interchange\Exceptions\NoDefaultDriverException;
use Ifds\Interchange\Support\DriverConfig;
use Ifds\Interchange\Tests\Fixtures\AttributelessClient;
use Ifds\Interchange\Tests\Fixtures\LateSampleApiClient;
use Ifds\Interchange\Tests\Fixtures\SampleApiClient;

it('resolves a correctly configured driver into its client class', function () {
    expect(sampleDriver())->toBeInstanceOf(SampleApiClient::class);
});

it('throws when the config key does not match the client #[Driver] attribute', function () {
    config()->set('interchange.drivers.mismatch', [
        'client' => SampleApiClient::class, // declares #[Driver('sample')]
        'base_url' => 'https://jsonplaceholder.typicode.com',
    ]);

    expect(fn () => app('interchange')->driver('mismatch'))
        ->toThrow(InvalidDriverConfigException::class);
});

it('resolves a driver registered in config after the manager was already built', function () {
    // Force the manager to be constructed before the driver exists in config.
    expect(sampleDriver())->toBeInstanceOf(SampleApiClient::class);

    config()->set('interchange.drivers.late', [
        'client' => LateSampleApiClient::class,
        'base_url' => 'https://jsonplaceholder.typicode.com',
    ]);

    expect(app('interchange')->driver('late'))->toBeInstanceOf(LateSampleApiClient::class);
});

it('throws when the requested driver is not in the config at all', function () {
    expect(fn () => app('interchange')->driver('nope'))
        ->toThrow(InvalidDriverConfigException::class);
});

it('throws when a driver has no client class configured', function () {
    config()->set('interchange.drivers.clientless', [
        'base_url' => 'https://jsonplaceholder.typicode.com',
    ]);

    expect(fn () => app('interchange')->driver('clientless'))
        ->toThrow(InvalidDriverConfigException::class);
});

it('throws when the client class is missing the #[Driver] attribute', function () {
    config()->set('interchange.drivers.attributeless', [
        'client' => AttributelessClient::class,
        'base_url' => 'https://jsonplaceholder.typicode.com',
    ]);

    expect(fn () => app('interchange')->driver('attributeless'))
        ->toThrow(InvalidDriverConfigException::class);
});

it('throws when building a DriverConfig with neither base_url nor stage_url', function () {
    expect(fn () => DriverConfig::fromArray('x', ['timeout' => 5]))
        ->toThrow(InvalidDriverConfigException::class);
});

it('falls back to stage_url when base_url is absent', function () {
    $config = DriverConfig::fromArray('x', ['stage_url' => 'https://stage.test']);

    expect($config->baseUrl)->toBe('https://stage.test');
});

it('exposes arbitrary driver-specific values via extra()', function () {
    $config = DriverConfig::fromArray('x', [
        'base_url' => 'https://x.test',
        'extra' => ['api_key' => 'secret'],
    ]);

    expect($config->extra('api_key'))->toBe('secret')
        ->and($config->extra('missing', 'default'))->toBe('default');
});

it('throws NoDefaultDriverException when no driver name is given', function () {
    expect(fn () => app('interchange')->driver())
        ->toThrow(NoDefaultDriverException::class);
});
