<?php

declare(strict_types=1);

namespace E164\Exception;

use RuntimeException;
use Throwable;

/**
 * Thrown when the API could not be reached, answered with an unexpected
 * status, or returned a body the SDK could not parse.
 */
class ApiException extends RuntimeException implements E164Exception
{
    /**
     * @param int|null $statusCode HTTP status code, or null for transport-level failures.
     */
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    /**
     * The HTTP status code that caused this exception.
     *
     * Null when the request never produced a response (DNS failure, connection
     * refused, timeout), which is how callers can tell a transport failure from
     * a server-side error.
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
}
