<?php

namespace white\commerce\sendcloud\models;

class Price
{
    public static function fromArray(array $price): self
    {
        return new self(
            (string)$price['value'],
            (string)$price['currency'],
        );
    }

    public function toArray(): array
    {
        return [
            'value' => self::getValue(),
            'currency' => self::getCurrency(),
        ];
    }

    /**
     * @param string|float $value The price
     * @param string $currency The currency in ISO 4217
     */
    public function __construct(
        protected string|float $value,
        protected string $currency,
    ) {
        $this->value = (string)$value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
