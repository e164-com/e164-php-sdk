<?php

declare(strict_types=1);

namespace E164\Exception;

/**
 * Thrown when the number is well-formed but the API holds no record for it.
 *
 * Distinct from its parent, which means the input could not be a phone number
 * at all. "We have no data for +44 113 391 0781" and "'abc' is not a number"
 * are different problems and usually call for different handling: the first is
 * a gap in coverage, the second a bug in the caller.
 *
 * Extends {@see InvalidPhoneNumberException} rather than ApiException so that
 * code written against 3.0 -- when both cases arrived as
 * InvalidPhoneNumberException -- continues to catch it.
 */
final class NumberNotFoundException extends InvalidPhoneNumberException
{
    /**
     * @param string $phoneNumber The normalised digits that produced no records.
     */
    public function __construct(private readonly string $phoneNumber)
    {
        parent::__construct("No record found for phone number: $phoneNumber");
    }

    /**
     * The normalised number that produced no records.
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }
}
