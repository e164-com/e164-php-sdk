# E164 PHP SDK

E164 PHP SDK is a PHP package for interacting with the [e164.com](https://e164.com) API.

## Installation

You can install this package via Composer. Run the following command in your terminal:

```
composer require e164-com/e164-php-sdk
```

## Usage

Here are some examples of how to use the E164 PHP SDK:

### Lookup a Phone Number

```php
require 'vendor/autoload.php';

use Vendor\E164\E164;

$e164 = new E164();
$phoneNumber = '441133910781';

try {
    $response = $e164->lookup($phoneNumber);

    echo "Prefix: " . $response->getPrefix() . PHP_EOL;
    echo "Calling Code: " . $response->getCallingCode() . PHP_EOL;
    echo "ISO3: " . $response->getIso3() . PHP_EOL;
    echo "Tadig: " . $response->getTadig() . PHP_EOL;
    echo "Mccmnc: " . $response->getMccmnc() . PHP_EOL;
    echo "Type: " . $response->getType() . PHP_EOL;
    echo "Location: " . $response->getLocation() . PHP_EOL;
    echo "Operator Brand: " . $response->getOperatorBrand() . PHP_EOL;
    echo "Operator Company: " . $response->getOperatorCompany() . PHP_EOL;
    echo "Total Length Min: " . $response->getTotalLengthMin() . PHP_EOL;
    echo "Total Length Max: " . $response->getTotalLengthMax() . PHP_EOL;
    echo "Weight: " . $response->getWeight() . PHP_EOL;
    echo "Source: " . $response->getSource() . PHP_EOL;

} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

### Error Handling

The `lookup` method can throw exceptions in various scenarios. Here are some common examples:

**Invalid Phone Number Format:**

If the provided phone number format is invalid (e.g., contains non-numeric characters or is too short/long), an exception might be thrown.

```php
// ... inside your try block ...
$phoneNumber = '00000000000';
$response = $e164->lookup($phoneNumber);
// ...
// Catch block output might be:
// 'Invalid phone number: 00000000000'
```

## Contributing

If you would like to contribute to this package, please fork the repository and submit a pull request. Make sure to follow the coding standards and include tests for any new features or bug fixes.

## License

This package is licensed under the MIT License. See the LICENSE file for more information.