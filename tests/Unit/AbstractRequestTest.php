<?php

use Ifds\Interchange\Tests\Fixtures\SampleRequest;

it('builds a payload with snake_cased keys from public properties', function () {
    $payload = SampleRequest::make('Ada', 'Lovelace', 3)->toPayload();

    expect($payload)->toBe([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'order_count' => 3,
    ]);
});

it('omits null and empty-string properties from the payload', function () {
    $payload = SampleRequest::make('Ada', null, 0)->toPayload();

    // last_name (null) is dropped; order_count (0) is kept because 0 is not null/''.
    expect($payload)->toBe([
        'first_name' => 'Ada',
        'order_count' => 0,
    ]);
});

it('returns an empty payload when every property is null', function () {
    expect(SampleRequest::make()->toPayload())->toBe([]);
});
