<?php

namespace white\commerce\sendcloud\models;

class ShippingMethod
{
    public static function fromArray(array $shippingMethod): self
    {
        $countries = [];
        foreach ($shippingMethod['countries'] as $country) {
            $countries[] = $country['iso_2'];
        }
        return new self(
            (int)$shippingMethod['id'],
            (string)$shippingMethod['name'],
            (string)$shippingMethod['carrier'],
            (float)$shippingMethod['min_weight'],
            (float)$shippingMethod['max_weight'],
            $countries,
            $shippingMethod['service_point_input'] === 'required',
        );
    }

    /**
     * @param int $id Unique identifier of the shipping method.
     * @param string $name Name of the shipping method, it should give an idea what the shipping method can be used for.
     * @param string $carrier A carrier_code which will indicate which carrier provides the shipping method.
     * @param float $minWeight Minimum allowed weight of the parcel for this shipping method.
     * @param float $maxWeight Maximum allowed weight of the parcel for this shipping method.
     * @param array $countries A list of SO 3166-1 alpha-2 country codes that you can ship to with the shipping method.
     * @param bool $servicePointInputRequired Will be true when the shipping method is meant to ship a parcel to a service point
     * @param int|null $craftMethodId The Craft shipping method id
     */
    public function __construct(
        protected int $id,
        protected string $name,
        protected string $carrier,
        protected float $minWeight,
        protected float $maxWeight,
        protected array $countries,
        protected bool $servicePointInputRequired,
        protected ?int $craftMethodId = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getCountries(): array
    {
        return $this->countries;
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
