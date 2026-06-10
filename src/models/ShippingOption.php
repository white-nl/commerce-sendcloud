<?php

namespace white\commerce\sendcloud\models;

class ShippingOption
{
    public static function fromArray(array $shippingOption): self
    {
        return new self(
            (string)$shippingOption['code'],
            (string)$shippingOption['name'],
            (string)$shippingOption['carrier']['code'],
            (float)$shippingOption['weight']['min']['value'],
            (float)$shippingOption['weight']['max']['value'],
            (bool)$shippingOption['requirements']['is_service_point_required'],
        );
    }

    /**
     * @param string $code Unique identifier of the shipping method.
     * @param string $name Name of the shipping method, it should give an idea what the shipping method can be used for.
     * @param string $carrier A carrier_code which will indicate which carrier provides the shipping method.
     * @param float $minWeight Minimum allowed weight of the parcel for this shipping method.
     * @param float $maxWeight Maximum allowed weight of the parcel for this shipping method.
     * @param bool $servicePointInputRequired Will be true when the shipping method is meant to ship a parcel to a service point
     * @param int|null $craftMethodId The Craft shipping method id
     */
    public function __construct(
        protected string $code,
        protected string $name,
        protected string $carrier,
        protected float $minWeight,
        protected float $maxWeight,
        protected bool $servicePointInputRequired,
        protected ?int $craftMethodId = null,
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCarrier(): string
    {
        return $this->carrier;
    }

    public function getMinWeight(): float
    {
        return $this->minWeight;
    }

    public function getMaxWeight(): float
    {
        return $this->maxWeight;
    }

    public function isServicePointInputRequired(): bool
    {
        return $this->servicePointInputRequired;
    }

    public function getCraftMethodId(): ?int
    {
        return $this->craftMethodId;
    }

    public function setCraftMethodId(int $craftMethodId): void
    {
        $this->craftMethodId = $craftMethodId;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
