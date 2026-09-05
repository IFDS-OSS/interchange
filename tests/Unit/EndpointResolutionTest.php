<?php

use Ifds\HttpAdapter\Exceptions\UndefinedEndpointException;
use Illuminate\Support\Facades\Http;

it('resolves a snake_cased endpoint method to its configured URL and verb', function () {
    Http::fake(['*' => Http::response(['ok' => true])]);

    fakeDriver()->ping()->send();

    Http::assertSent(fn ($request) => $request->url() === 'https://fake.test/ping'
        && $request->method() === 'GET');
});

it('throws when calling an endpoint that is not defined', function () {
    expect(fn () => fakeDriver()->somethingUndefined())
        ->toThrow(UndefinedEndpointException::class);
});

it('interpolates path parameters into the URL', function () {
    Http::fake(['*' => Http::response(['ok' => true])]);

    fakeDriver()->showUser()->withPathParam('id', 42)->send();

    Http::assertSent(fn ($request) => $request->url() === 'https://fake.test/users/42');
});

it('appends query parameters to the URL', function () {
    Http::fake(['*' => Http::response(['ok' => true])]);

    fakeDriver()->ping()->withQueryParam('page', 2)->withQueryParams(['sort' => 'asc'])->send();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'page=2')
        && str_contains($request->url(), 'sort=asc'));
});

it('sends the request payload as JSON', function () {
    Http::fake(['*' => Http::response(['ok' => true])]);

    fakeDriver()->unstablePost(['name' => 'widget'])->send();

    Http::assertSent(fn ($request) => $request['name'] === 'widget'
        && $request->hasHeader('Content-Type', 'application/json'));
});
