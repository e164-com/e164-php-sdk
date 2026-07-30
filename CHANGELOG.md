# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [3.0.0] - 2026-07-30

### Breaking Changes
- Namespace changed from the placeholder `Vendor\E164` to `E164`. Replace `use Vendor\E164\E164;` with `use E164\E164;`.
- `Response` is replaced by `LookupResult`: immutable, built with `LookupResult::fromArray()`, no setters.
- `getTotalLengthMin()`, `getTotalLengthMax()` and `getWeight()` return `?int` instead of `?string`, matching the JSON numbers the API actually sends.
- The optional HTTP client is now typed as PSR-18 `Psr\Http\Client\ClientInterface` instead of `GuzzleHttp\ClientInterface`. Guzzle's `Client` implements both, so injecting one is unaffected; mocks typed against Guzzle's interface need updating.
- `+` is no longer preserved during sanitisation. All non-digits are stripped.
- Numbers with more than 15 digits are rejected with `InvalidPhoneNumberException` instead of being sent to the API.
- Invalid input now reports `Invalid phone number: no digits in input` rather than `Invalid phone number: empty input`.
- `E164` and `LookupResult` are `final`.

### Fixed
- **An injected HTTP client no longer breaks `lookup()`.** Base URL and headers were only applied to the internally constructed client, so a client without a `base_uri` — exactly what the README documented — sent the phone number as the request host. Absolute URLs are now built per request, and the number is URL-encoded.
- **The API key is no longer silently discarded when a client is injected.** It, along with `User-Agent` and `Referer`, was only attached during construction of the default client, so authenticated requests went out unauthenticated with no error. Headers are now set per request.
- **The default client applies timeouts** (10s request, 5s connect). Previously neither was set, so an unresponsive endpoint could hang a caller indefinitely.
- **A non-scalar field no longer throws an uncaught `TypeError`.** Passing an array or object into the typed setters escaped the documented exception hierarchy. Such a value now reads as `null` through its getter and stays intact in the raw payload.
- **HTTP status codes are preserved** on `ApiException` via `getStatusCode()`, instead of every failure being reported with code `0`.
- **Malformed JSON is reported as `ApiException`** rather than `InvalidPhoneNumberException`, which had blamed the caller's input for a server-side problem.
- **A stray `+` mid-number no longer changes which number is queried.** `44+1133910781` silently became a different lookup; it now normalises to `441133910781`.
- The `User-Agent` version no longer drifts. It is resolved from the installed package version rather than a hardcoded `1.0` that had survived two releases.

### Added
- `lookupAll()` returns every record the API holds for a number, best match first. Previously all but the first were discarded.
- `LookupResult::get()`, `has()`, `toArray()` and `JsonSerializable`, so fields added to the API after a release remain reachable.
- `E164\Exception\E164Exception`, implemented by every SDK exception, so callers can catch anything from the SDK in one block.
- `AuthenticationException` for HTTP 401/403 and `RateLimitException` for HTTP 429, both extending `ApiException`. `RateLimitException::getRetryAfter()` exposes the `Retry-After` delay.
- Constructor arguments for a PSR-17 request factory and a base URL override.
- `declare(strict_types=1)` across the package.
- `ext-json`, `composer-runtime-api`, `psr/http-client`, `psr/http-factory` and `psr/http-message` declared as dependencies. `ext-json` was required in practice but never declared.
- `composer test`, `composer stan` and `composer check` scripts; `support` metadata; `sort-packages`.

### Changed
- PHPStan raised from level 8 to level 10, now covering `tests/` as well as `src/`; PHPStan itself upgraded from 1.12 to 2.1.
- Test suite expanded from 11 tests to 50, with fixtures matching the live API payload. The previous fixtures typed `total_length_min` and `weight` as strings and `source` as `e164.com`, while the API sends numbers and `e164` — which is why the type coercion issue went unnoticed. Tests now assert the outgoing request URI and headers.
- CI adds PHP 8.5, a `--prefer-lowest` job, and `composer validate --strict`; PHPStan runs once rather than once per PHP version. Dropped the Xdebug setup, which cost time on every job without producing a coverage report.

## [2.1.0] - 2026-03-23

### Added
- Optional API key authentication via the second constructor argument, sent as an `X-API-Key` header.

## [2.0.0] - 2026-03-20

### Breaking Changes
- Minimum PHP version bumped from 7.4 to 8.1
- `lookup()` now throws `InvalidPhoneNumberException` and `ApiException` instead of generic `Exception`
- PHPUnit upgraded from 9.5 to 10.5

### Added
- `InvalidPhoneNumberException` for invalid, empty, or non-numeric phone numbers
- `ApiException` for HTTP/network failures
- PHPStan static analysis at level 8
- GitHub Actions CI across PHP 8.1, 8.2, 8.3, 8.4
- `.editorconfig` for consistent formatting
- Input format flexibility — numbers with `+`, dashes, and spaces are accepted

### Changed
- Replaced `FILTER_SANITIZE_NUMBER_INT` with regex sanitization
- API base URL and User-Agent extracted to class constants
- User-Agent changed from `MyCustomAgent/1.0 (Not a browser)` to `e164-php-sdk/1.0`
- Removed redundant `(string)` casts in Response setters
- Expanded test suite from 3 to 9 fully-mocked tests (no real API calls)
- Rewrote README with badges, quick start, and full API reference

## [1.0.0] - 2025-04-17

### Added
- Initial release
- Phone number lookup via e164.com API
- Response object with getters for all API fields
- Basic test suite

[3.0.0]: https://github.com/e164-com/e164-php-sdk/compare/2.1...3.0.0
[2.1.0]: https://github.com/e164-com/e164-php-sdk/compare/2.0...2.1
[2.0.0]: https://github.com/e164-com/e164-php-sdk/compare/1.0...2.0
[1.0.0]: https://github.com/e164-com/e164-php-sdk/releases/tag/1.0
