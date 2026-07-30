<?php

declare(strict_types=1);

namespace E164\Exception;

/**
 * Thrown when the API rejects the supplied credentials (HTTP 401 or 403).
 */
final class AuthenticationException extends ApiException
{
}
