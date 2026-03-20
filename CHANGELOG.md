# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project adheres to [Semantic Versioning](https://semver.org/).

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

[2.0.0]: https://github.com/e164-com/e164-php-sdk/compare/1.0...2.0
[1.0.0]: https://github.com/e164-com/e164-php-sdk/releases/tag/1.0
