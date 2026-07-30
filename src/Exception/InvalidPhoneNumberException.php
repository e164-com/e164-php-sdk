<?php

declare(strict_types=1);

namespace E164\Exception;

use RuntimeException;

/**
 * Thrown when the supplied number cannot be looked up: it contains no digits,
 * exceeds the 15-digit E.164 maximum, or the API holds no record for it.
 */
final class InvalidPhoneNumberException extends RuntimeException implements E164Exception
{
}
