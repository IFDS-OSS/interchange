<?php

use Ifds\HttpAdapter\Exceptions\UndefinedEndpointException;
use Illuminate\Support\Facades\Http;

it('resolves a snake_cased endpoint method to its configured URL and verb', function () {
    Http::fake(['*' => Http::response(['ok' => true])]);

    sampleDriver()->posts()->send();

    Http::assertSent(fn ($request) => $request->url() === 'https://jsonplaceholder.typicode.com/posts'
        && $request->method() === 'GET');
});

it('throws when calling an endpoint that is not defined', function () {
    expect(fn () => sampleDriver()->somethingUndefined())
        ->toThrow(UndefinedEndpointException::class);
});

it('interpolates path parameters into the URL', function () {
    Http::fake(['*' => Http::response(['ok' => true])]);

    sampleDriver()->showPost()->withPathParam('id', 42)->send();

    Http::assertSent(fn ($request) => $request->url() === 'https://jsonplaceholder.typicode.com/posts/42');
});

it('appends query parameters to the URL', function () {
    Http::fake(['*' => Http::response(['ok' => true])]);

    sampleDriver()->posts()->withQueryParam('page', 2)->withQueryParams(['sort' => 'asc'])->send();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'page=2')
        && str_contains($request->url(), 'sort=asc'));
});

it('sends the request payload as JSON', function () {
    Http::fake(['*' => Http::response(['ok' => true])]);

    sampleDriver()->createPost(['title' => 'widget'])->send();

    Http::assertSent(fn ($request) => $request['title'] === 'widget'
        && $request->hasHeader('Content-Type', 'application/json'));
});
