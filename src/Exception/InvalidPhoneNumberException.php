<?php

declare(strict_types=1);

namespace E164\Exception;

use RuntimeException;

/**
 * Thrown when the supplied number is not a usable E.164 number: it contains no
 * digits, exceeds the 15-digit maximum, or begins with 0.
 *
 * Deliberately not final. {@see NumberNotFoundException} extends it so that
 * code written against 3.0 -- where a well-formed number the API held no record
 * for also arrived as an InvalidPhoneNumberException -- keeps working unchanged.
 */
class InvalidPhoneNumberException extends RuntimeException implements E164Exception
{
}
