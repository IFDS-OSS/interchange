# Changelog

All notable changes to `ifds-oss/http-adapter` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `AbstractApiClient` — fluent, driver-based outbound HTTP client with declarative
  endpoint definitions, path/query interpolation, per-endpoint mock/live switching,
  configurable retry with backoff, and per-driver circuit breaking.
- Config- and attribute-driven driver registry (`#[Driver('name')]` + `config/http-adapter.php`)
  resolved through an `Illuminate\Support\Manager`, so consuming apps register drivers
  without modifying the package. Driver config is read at resolve time, so drivers added
  or reconfigured after boot are picked up.
- Lifecycle events — `RequestSending`, `RequestRetrying`, `ResponseReceived`,
  `RequestFailed`, `CircuitStateChanged` — plus a default `LogHttpAdapterActivity`
  listener writing to a configurable log channel.
- `AbstractAdapterRequest` / `AbstractAdapterResponse` base DTOs and the
  `ExtractsTolerantFields` trait for casing-tolerant parsing of inconsistent third-party APIs.
- Laravel 13 support (`illuminate/*` `^13.0`), tested in CI on PHP 8.3 and 8.4 against
  `orchestra/testbench` `^11.0`. Laravel 11 and 12 remain supported.
