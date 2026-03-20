<?php

namespace Vendor\E164;

class Response
{
    private ?string $prefix = null;
    private ?string $calling_code = null;
    private ?string $iso3 = null;
    private ?string $tadig = null;
    private ?string $mccmnc = null;
    private ?string $type = null;
    private ?string $location = null;
    private ?string $operator_brand = null;
    private ?string $operator_company = null;
    private ?string $total_length_min = null;
    private ?string $total_length_max = null;
    private ?string $weight = null;
    private ?string $source = null;

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function setPrefix(?string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function getCallingCode(): ?string
    {
        return $this->calling_code;
    }

    public function setCallingCode(?string $calling_code): void
    {
        $this->calling_code = $calling_code;
    }

    public function getIso3(): ?string
    {
        return $this->iso3;
    }

    public function setIso3(?string $iso3): void
    {
        $this->iso3 = $iso3;
    }

    public function getTadig(): ?string
    {
        return $this->tadig;
    }

    public function setTadig(?string $tadig): void
    {
        $this->tadig = $tadig;
    }

    public function getMccmnc(): ?string
    {
        return $this->mccmnc;
    }

    public function setMccmnc(?string $mccmnc): void
    {
        $this->mccmnc = $mccmnc;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }

    public function getOperatorBrand(): ?string
    {
        return $this->operator_brand;
    }

    public function setOperatorBrand(?string $operator_brand): void
    {
        $this->operator_brand = $operator_brand;
    }

    public function getOperatorCompany(): ?string
    {
        return $this->operator_company;
    }

    public function setOperatorCompany(?string $operator_company): void
    {
        $this->operator_company = $operator_company;
    }

    public function getTotalLengthMin(): ?string
    {
        return $this->total_length_min;
    }

    public function setTotalLengthMin(?string $total_length_min): void
    {
        $this->total_length_min = $total_length_min;
    }

    public function getTotalLengthMax(): ?string
    {
        return $this->total_length_max;
    }

    public function setTotalLengthMax(?string $total_length_max): void
    {
        $this->total_length_max = $total_length_max;
    }

    public function getWeight(): ?string
    {
        return $this->weight;
    }

    public function setWeight(?string $weight): void
    {
        $this->weight = $weight;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source;
    }
}
