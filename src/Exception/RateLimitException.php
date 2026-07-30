<?php

declare(strict_types=1);

namespace E164\Exception;

use Throwable;

/**
 * Thrown when the API rejects a request because the caller is being rate
 * limited (HTTP 429).
 */
final class RateLimitException extends ApiException
{
    public function __construct(
        string $message,
        private readonly ?int $retryAfter = null,
        ?int $statusCode = 429,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Seconds to wait before retrying, taken from the `Retry-After` response
     * header. Null when the API did not send one (or sent a date rather than a
     * delay), in which case pick your own backoff.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
