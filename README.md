# E164 PHP SDK

[![Tests](https://github.com/e164-com/e164-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/e164-com/e164-php-sdk/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/e164-com/e164-php-sdk.svg?v=2)](https://packagist.org/packages/e164-com/e164-php-sdk)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg)](https://phpstan.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

The official PHP SDK for the [E164 API](https://e164.com) — phone number validation and network lookup.

## Requirements

- PHP 8.1+
- ext-json

## Installation

```bash
composer require e164-com/e164-php-sdk
```

## Quick Start

```php
use Vendor\E164\E164;

$e164 = new E164();
$result = $e164->lookup('441133910781');

echo $result->getType();            // "GEOGRAPHIC"
echo $result->getCallingCode();     // "44"
echo $result->getIso3();            // "GBR"
echo $result->getOperatorBrand();   // "BT"
```

## Working with Results

```php
$result = $e164->lookup('441133910781');

// Number identification
$result->getPrefix();          // "44113391"
$result->getCallingCode();     // "44"
$result->getIso3();            // "GBR"
$result->getType();            // "GEOGRAPHIC"
$result->getLocation();        // Location if available

// Network details
$result->getTadig();           // TADIG code
$result->getMccmnc();          // "234"
$result->getOperatorBrand();   // "BT"
$result->getOperatorCompany(); // "BT"

// Number length constraints
$result->getTotalLengthMin();  // "12"
$result->getTotalLengthMax();  // "12"

// Metadata
$result->getWeight();          // "11"
$result->getSource();          // "e164.com"
```

## Input Format

The SDK accepts phone numbers in various formats. Non-numeric characters (except `+`) are stripped automatically:

```php
$e164->lookup('441133910781');      // digits only
$e164->lookup('+441133910781');     // with + prefix
$e164->lookup('+44-113-391-0781'); // formatted
```

## Custom HTTP Client

You can inject your own Guzzle client for custom configuration (proxies, timeouts, etc.):

```php
use GuzzleHttp\Client;
use Vendor\E164\E164;

$client = new Client([
    'timeout' => 10,
    'headers' => [
        'User-Agent' => 'MyApp/1.0',
    ],
]);

$e164 = new E164($client);
```

## Error Handling

```php
use Vendor\E164\E164;
use Vendor\E164\Exception\InvalidPhoneNumberException;
use Vendor\E164\Exception\ApiException;

try {
    $result = $e164->lookup('441133910781');
} catch (InvalidPhoneNumberException $e) {
    // Phone number is empty, non-numeric, or not found
} catch (ApiException $e) {
    // HTTP request failed (network error, timeout, server error)
}
```

Both exceptions extend `RuntimeException`, so you can catch them individually or together.

## Running Tests

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Run static analysis
vendor/bin/phpstan analyse
```

## Contributing

Fork the repository and submit a pull request. Please include tests for any new features or bug fixes.

## License

MIT
