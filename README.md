# E164 PHP SDK

[![Tests](https://github.com/e164-com/e164-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/e164-com/e164-php-sdk/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/e164-com/e164-php-sdk.svg)](https://packagist.org/packages/e164-com/e164-php-sdk)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%2010-brightgreen.svg)](https://phpstan.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

The official PHP SDK for the [E164 API](https://e164.com) — phone number validation and network lookup.

## Requirements

- PHP 8.1+
- ext-json
- Any [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client (Guzzle is installed by default)

## Installation

```bash
composer require e164-com/e164-php-sdk
```

## Quick Start

```php
use E164\E164;

$e164 = new E164();
$result = $e164->lookup('441133910781');

echo $result->getType();            // "GEOGRAPHIC"
echo $result->getCallingCode();     // 44
echo $result->getIso3();            // "GBR"
echo $result->getOperatorBrand();   // "BT"
```

## Authentication (Optional)

The E164 API works without authentication, but if you have an API key you can pass it as the second argument:

```php
$e164 = new E164(null, 'your-api-key');
```

The key is sent as an `X-API-Key` header on every request, including when you supply your own HTTP client.

## Working with Results

`lookup()` returns an immutable `E164\LookupResult`:

```php
$result = $e164->lookup('441133910781');

// Number identification
$result->getPrefix();          // "44113391"
$result->getCallingCode();     // 44 (int)
$result->getIso3();            // "GBR"
$result->getType();            // "GEOGRAPHIC"
$result->getLocation();        // Location if available, else null

// Network details
$result->getTadig();           // TADIG code, e.g. "GBRJT" for a mobile number
$result->getMccmnc();          // e.g. "23450" for a mobile number
$result->getOperatorBrand();   // "BT"
$result->getOperatorCompany(); // "BT"

// Number length constraints (integers)
$result->getTotalLengthMin();  // 12
$result->getTotalLengthMax();  // 12

// Metadata
$result->getWeight();          // 11
$result->getSource();          // "e164"
```

Any field the API omits reads as `null`, so guard on the ones you depend on. Landline
lookups typically have no `tadig` or `mccmnc`; mobile lookups do.

### Multiple matches

A number can match more than one record. `lookup()` returns the best match; use
`lookupAll()` when you want them all, ordered best match first:

```php
foreach ($e164->lookupAll('12124567890') as $match) {
    echo $match->getPrefix() . ' ' . $match->getOperatorBrand() . PHP_EOL;
}
```

`lookupAll()` never returns an empty array — like `lookup()`, it throws
`NumberNotFoundException` when the API holds no record for the number.

### Raw payload access

The decoded API record stays reachable, so fields added to the API after this SDK
was released are never lost:

```php
$result->toArray();              // the whole record as received
$result->get('total_length_min'); // 12 — the exact JSON value, uncoerced
$result->has('tadig');           // true even when the value is null
json_encode($result);            // re-encodes to the original payload
```

## Input Format

Everything that is not a digit is stripped, so all of these are equivalent:

```php
$e164->lookup('441133910781');
$e164->lookup('+441133910781');
$e164->lookup('+44-113-391-0781');
$e164->lookup('+44 (113) 391 0781');
```

Input with no digits at all, or with more than the 15 digits E.164 permits, is
rejected without an API call.

So is a number beginning with `0`. Country calling codes run from 1 to 999 and
never start with one, so a number still carrying a national trunk prefix or an
international access code is not in E.164 form — strip it before calling:

```php
$e164->lookup('00441133910781');  // InvalidPhoneNumberException
$e164->lookup('+441133910781');   // correct
```

## Custom HTTP Client

Any PSR-18 client works. The SDK builds absolute URLs and sets its own headers, so
an injected client needs no particular configuration — no `base_uri` required:

```php
use GuzzleHttp\Client;
use E164\E164;

$e164 = new E164(new Client(['timeout' => 5]));
```

The default client applies a 10-second request timeout and a 5-second connect
timeout. When you inject your own client, its timeouts are yours to set.

You can also supply a PSR-17 request factory and override the base URL:

```php
$e164 = new E164(
    client: new Client(),
    apiKey: 'your-api-key',
    requestFactory: new GuzzleHttp\Psr7\HttpFactory(),
    baseUrl: 'https://staging.e164.com',
);
```

## Error Handling

```php
use E164\Exception\ApiException;
use E164\Exception\AuthenticationException;
use E164\Exception\InvalidPhoneNumberException;
use E164\Exception\NumberNotFoundException;
use E164\Exception\RateLimitException;

try {
    $result = $e164->lookup('441133910781');
} catch (NumberNotFoundException $e) {
    // Well-formed number, but the API holds no record for it.
    // $e->getPhoneNumber() returns the normalised digits that were looked up.
} catch (InvalidPhoneNumberException $e) {
    // Not a usable number: no digits, over 15 digits, or starts with 0.
} catch (AuthenticationException $e) {
    // API key missing or rejected (HTTP 401/403).
} catch (RateLimitException $e) {
    sleep($e->getRetryAfter() ?? 60);
} catch (ApiException $e) {
    // Any other API or transport failure.
    $e->getStatusCode(); // HTTP status, or null if the request never got a response
}
```

Order matters: `NumberNotFoundException` extends `InvalidPhoneNumberException`, so it
must be caught first if you want to treat the two differently. Catching only
`InvalidPhoneNumberException` still covers both — which is what 3.0 did, when a missing
record and malformed input were the same exception.

`AuthenticationException` and `RateLimitException` both extend `ApiException`, so
catching `ApiException` alone covers every API-side failure. Every exception the SDK
throws implements `E164\Exception\E164Exception` and extends `RuntimeException`:

```php
use E164\Exception\E164Exception;

try {
    $result = $e164->lookup($number);
} catch (E164Exception $e) {
    // Anything this SDK can throw.
}
```

Use `getStatusCode()` to tell a server-side error from a transport failure: it
returns the HTTP status for the former and `null` for the latter (DNS failure,
connection refused, timeout).

## Upgrading from 2.x

3.0 renamed the placeholder `Vendor\E164` namespace. For most projects the upgrade
is a find-and-replace:

```diff
-use Vendor\E164\E164;
-use Vendor\E164\Response;
+use E164\E164;
+use E164\LookupResult;
```

Then note these behaviour changes:

- `Response` is now `LookupResult`, immutable, and built via `LookupResult::fromArray()` instead of setters.
- `getCallingCode()`, `getTotalLengthMin()`, `getTotalLengthMax()` and `getWeight()` return `?int` instead of `?string`.
- A custom HTTP client is now typed as PSR-18 rather than `GuzzleHttp\ClientInterface`. Guzzle's own `Client` satisfies both, so injecting one still works.
- Numbers over 15 digits are now rejected rather than sent to the API.

See [CHANGELOG.md](CHANGELOG.md) for the full list.

## Development

```bash
composer install
composer check   # PHPStan, then the test suite
composer test    # tests only
composer stan    # static analysis only
```

## Contributing

Fork the repository and submit a pull request. Please include tests for any new features or bug fixes.

## License

MIT
