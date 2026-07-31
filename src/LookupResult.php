<?php

declare(strict_types=1);

namespace E164;

use JsonSerializable;

/**
 * One record returned by a number lookup.
 *
 * Immutable. Alongside the typed getters, the untouched API payload stays
 * reachable via {@see self::get()} and {@see self::toArray()} so fields added
 * to the API after this SDK was released are never lost.
 */
final class LookupResult implements JsonSerializable
{
    /**
     * @param array<string, mixed> $raw The decoded API record, exactly as received.
     */
    public function __construct(
        private readonly ?string $prefix = null,
        private readonly ?int $callingCode = null,
        private readonly ?string $iso3 = null,
        private readonly ?string $tadig = null,
        private readonly ?string $mccmnc = null,
        private readonly ?string $type = null,
        private readonly ?string $location = null,
        private readonly ?string $operatorBrand = null,
        private readonly ?string $operatorCompany = null,
        private readonly ?int $totalLengthMin = null,
        private readonly ?int $totalLengthMax = null,
        private readonly ?int $weight = null,
        private readonly ?string $source = null,
        private readonly array $raw = [],
    ) {
    }

    /**
     * Builds a result from one decoded API record.
     *
     * Values are coerced to the getter's type rather than trusted, because the
     * API sends several of these fields as JSON numbers. A field holding a
     * non-scalar reads as null through its getter and stays intact in the raw
     * payload — an unexpected shape must never break a lookup.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            prefix: self::readString($data, 'prefix'),
            callingCode: self::readInt($data, 'calling_code'),
            iso3: self::readString($data, 'iso3'),
            tadig: self::readString($data, 'tadig'),
            mccmnc: self::readString($data, 'mccmnc'),
            type: self::readString($data, 'type'),
            location: self::readString($data, 'location'),
            operatorBrand: self::readString($data, 'operator_brand'),
            operatorCompany: self::readString($data, 'operator_company'),
            totalLengthMin: self::readInt($data, 'total_length_min'),
            totalLengthMax: self::readInt($data, 'total_length_max'),
            weight: self::readInt($data, 'weight'),
            source: self::readString($data, 'source'),
            raw: $data,
        );
    }

    /**
     * The matched dialling prefix, e.g. "44113391".
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * Country calling code, e.g. 44.
     *
     * The API sends this as a JSON number, and toArray()/jsonSerialize() have
     * always reported it as one. Returning a string here made a single field
     * read as two different types off the same object.
     */
    public function getCallingCode(): ?int
    {
        return $this->callingCode;
    }

    /**
     * ISO 3166-1 alpha-3 country code, e.g. "GBR".
     */
    public function getIso3(): ?string
    {
        return $this->iso3;
    }

    /**
     * TADIG code of the operator, where known.
     */
    public function getTadig(): ?string
    {
        return $this->tadig;
    }

    /**
     * Mobile country and network code, where known.
     */
    public function getMccmnc(): ?string
    {
        return $this->mccmnc;
    }

    /**
     * Number classification, e.g. "GEOGRAPHIC" or "MOBILE".
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Geographic location for the prefix, where known.
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * Consumer-facing operator brand, e.g. "BT".
     */
    public function getOperatorBrand(): ?string
    {
        return $this->operatorBrand;
    }

    /**
     * Legal operator company name.
     */
    public function getOperatorCompany(): ?string
    {
        return $this->operatorCompany;
    }

    /**
     * Minimum total number length in digits, including the calling code.
     */
    public function getTotalLengthMin(): ?int
    {
        return $this->totalLengthMin;
    }

    /**
     * Maximum total number length in digits, including the calling code.
     */
    public function getTotalLengthMax(): ?int
    {
        return $this->totalLengthMax;
    }

    /**
     * Match confidence weight assigned by the API.
     */
    public function getWeight(): ?int
    {
        return $this->weight;
    }

    /**
     * Origin of the record, e.g. "e164".
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * Reads a single field straight from the API payload, bypassing coercion.
     *
     * Use this for fields the API has added since this SDK was released, or
     * when you need a value's exact JSON type.
     */
    public function get(string $key): mixed
    {
        return $this->raw[$key] ?? null;
    }

    /**
     * Whether the API payload carried this field at all, even as null.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->raw);
    }

    /**
     * The decoded API record, exactly as received.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function readString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (is_string($value)) {
            return $value;
        }

        return is_int($value) || is_float($value) ? (string) $value : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function readInt(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        if (is_int($value)) {
            return $value;
        }

        return is_float($value) || (is_string($value) && is_numeric($value)) ? (int) $value : null;
    }
}
