<?php

namespace Vendor\E164;

class Response
{
    private ?string $prefix;
    private ?string $calling_code;
    private ?string $iso3;
    private ?string $tadig;
    private ?string $mccmnc;
    private ?string $type;
    private ?string $location;
    private ?string $operator_brand;
    private ?string $operator_company;
    private ?string $total_length_min;
    private ?string $total_length_max;
    private ?string $weight;
    private ?string $source;

    /**
     * Get the prefix.
     *
     * @return ?string
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * Set the prefix.
     *
     * @param ?string $prefix
     * @return void
     */
    public function setPrefix(?string $prefix): void
    {
        $this->prefix = $prefix === null ? null : (string)$prefix;
    }

    /**
     * Get the calling code.
     *
     * @return ?string
     */
    public function getCallingCode(): ?string
    {
        return $this->calling_code;
    }

    /**
     * Set the calling code.
     *
     * @param ?string $calling_code
     * @return void
     */
    public function setCallingCode(?string $calling_code): void
    {
        $this->calling_code = $calling_code === null ? null : (string)$calling_code;
    }

    /**
     * Get the ISO3 code.
     *
     * @return ?string
     */
    public function getIso3(): ?string
    {
        return $this->iso3;
    }

    /**
     * Set the ISO3 code.
     *
     * @param ?string $iso3
     * @return void
     */
    public function setIso3(?string $iso3): void
    {
        $this->iso3 = $iso3 === null ? null : (string)$iso3;
    }

    /**
     * Get the Tadig.
     *
     * @return ?string
     */
    public function getTadig(): ?string
    {
        return $this->tadig;
    }

    /**
     * Set the Tadig.
     *
     * @param ?string $tadig
     * @return void
     */
    public function setTadig(?string $tadig): void
    {
        $this->tadig = $tadig === null ? null : (string)$tadig;
    }

    /**
     * Get the MCCMNC.
     *
     * @return ?string
     */
    public function getMccmnc(): ?string
    {
        return $this->mccmnc;
    }

    /**
     * Set the MCCMNC.
     *
     * @param ?string $mccmnc
     * @return void
     */
    public function setMccmnc(?string $mccmnc): void
    {
        $this->mccmnc = $mccmnc === null ? null : (string)$mccmnc;
    }

    /**
     * Get the type.
     *
     * @return ?string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set the type.
     *
     * @param ?string $type
     * @return void
     */
    public function setType(?string $type): void
    {
        $this->type = $type === null ? null : (string)$type;
    }

    /**
     * Get the location.
     *
     * @return ?string
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * Set the location.
     *
     * @param ?string $location
     * @return void
     */
    public function setLocation(?string $location): void
    {
        $this->location = $location === null ? null : (string)$location;
    }

    /**
     * Get the operator brand.
     *
     * @return ?string
     */
    public function getOperatorBrand(): ?string
    {
        return $this->operator_brand;
    }

    /**
     * Set the operator brand.
     *
     * @param ?string $operator_brand
     * @return void
     */
    public function setOperatorBrand(?string $operator_brand): void
    {
        $this->operator_brand = $operator_brand === null ? null : (string)$operator_brand;
    }

    /**
     * Get the operator company.
     *
     * @return ?string
     */
    public function getOperatorCompany(): ?string
    {
        return $this->operator_company;
    }

    /**
     * Set the operator company.
     *
     * @param ?string $operator_company
     * @return void
     */
    public function setOperatorCompany(?string $operator_company): void
    {
        $this->operator_company = $operator_company === null ? null : (string)$operator_company;
    }

    /**
     * Get the total length min.
     *
     * @return ?string
     */
    public function getTotalLengthMin(): ?string
    {
        return $this->total_length_min;
    }

    /**
     * Set the total length min.
     *
     * @param ?string $total_length_min
     * @return void
     */
    public function setTotalLengthMin(?string $total_length_min): void
    {
        $this->total_length_min = $total_length_min === null ? null : (string)$total_length_min;
    }

    /**
     * Get the total length max.
     *
     * @return ?string
     */
    public function getTotalLengthMax(): ?string
    {
        return $this->total_length_max;
    }

    /**
     * Set the total length max.
     *
     * @param ?string $total_length_max
     * @return void
     */
    public function setTotalLengthMax(?string $total_length_max): void
    {
        $this->total_length_max = $total_length_max === null ? null : (string)$total_length_max;
    }

    /**
     * Get the weight.
     *
     * @return ?string
     */
    public function getWeight(): ?string
    {
        return $this->weight;
    }

    /**
     * Set the weight.
     *
     * @param ?string $weight
     * @return void
     */
    public function setWeight(?string $weight): void
    {
        $this->weight = $weight === null ? null : (string)$weight;
    }

    /**
     * Get the source.
     *
     * @return ?string
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * Set the source.
     *
     * @param ?string $source
     * @return void
     */
    public function setSource(?string $source): void
    {
        $this->source = $source === null ? null : (string)$source;
    }
}
