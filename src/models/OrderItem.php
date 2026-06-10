<?php

namespace white\commerce\sendcloud\models;

use yii\base\Arrayable;
use yii\base\ArrayableTrait;

class OrderItem implements Arrayable
{
    use ArrayableTrait {
        toArray as traitToArray;
    }

    /**
     * @param string $name
     * @param int $quantity
     * @param Price|null $totalPrice
     * @param string|null $itemId
     * @param string|null $productId
     * @param string|null $variantId
     * @param string|null $imageUrl
     * @param string|null $description
     * @param string|null $sku
     * @param string|null $hsCode
     * @param string|null $countryOfOrigin
     * @param array|null $properties
     * @param Price|null $unitPrice
     * @param array|null $measurement
     * @param string|null $ean
     * @param array|null $deliveryDates
     * @param string|null $midCode
     * @param string|null $materialContent
     * @param string|null $intendedUse
     * @param array|null $dangerousGoods
     */
    public function __construct(string $name, int $quantity, ?Price $totalPrice, ?string $itemId, ?string $productId, ?string $variantId, ?string $imageUrl, ?string $description, ?string $sku, ?string $hsCode, ?string $countryOfOrigin, ?array $properties, ?Price $unitPrice, ?array $measurement, ?string $ean, ?array $deliveryDates, ?string $midCode, ?string $materialContent, ?string $intendedUse, ?array $dangerousGoods)
    {
        $this->name = $name;
        $this->quantity = $quantity;
        $this->totalPrice = $totalPrice;
        $this->itemId = $itemId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->imageUrl = $imageUrl;
        $this->description = $description;
        $this->sku = $sku;
        $this->hsCode = $hsCode;
        $this->countryOfOrigin = $countryOfOrigin;
        $this->properties = $properties;
        $this->unitPrice = $unitPrice;
        $this->measurement = $measurement;
        $this->ean = $ean;
        $this->deliveryDates = $deliveryDates;
        $this->midCode = $midCode;
        $this->materialContent = $materialContent;
        $this->intendedUse = $intendedUse;
        $this->dangerousGoods = $dangerousGoods;
    }

    private string $name;

    private int $quantity;

    private ?Price $totalPrice;

    private ?string $itemId;

    private ?string $productId;

    private ?string $variantId;

    private ?string $imageUrl;

    private ?string $description;

    private ?string $sku;

    private ?string $hsCode;

    private ?string $countryOfOrigin;

    private ?array $properties;

    private ?Price $unitPrice;

    private ?array $measurement;

    private ?string $ean;

    private ?array $deliveryDates;

    private ?string $midCode;

    private ?string $materialContent;

    private ?string $intendedUse;

    private ?array $dangerousGoods;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getTotalPrice(): ?Price
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?Price $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

    public function getItemId(): ?string
    {
        return $this->itemId;
    }

    public function setItemId(?string $itemId): void
    {
        $this->itemId = $itemId;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): void
    {
        $this->productId = $productId;
    }

    public function getVariantId(): ?string
    {
        return $this->variantId;
    }

    public function setVariantId(?string $variantId): void
    {
        $this->variantId = $variantId;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): void
    {
        $this->sku = $sku;
    }

    public function getHsCode(): ?string
    {
        return $this->hsCode;
    }

    public function setHsCode(?string $hsCode): void
    {
        $this->hsCode = $hsCode;
    }

    public function getCountryOfOrigin(): ?string
    {
        return $this->countryOfOrigin;
    }

    public function setCountryOfOrigin(?string $countryOfOrigin): void
    {
        $this->countryOfOrigin = $countryOfOrigin;
    }

    public function getProperties(): ?array
    {
        return $this->properties;
    }

    public function setProperties(?array $properties): void
    {
        $this->properties = $properties;
    }

    public function getUnitPrice(): ?Price
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(?Price $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
    }

    public function getMeasurement(): ?array
    {
        return $this->measurement;
    }

    public function setMeasurement(?array $measurement): void
    {
        $this->measurement = $measurement;
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function setEan(?string $ean): void
    {
        $this->ean = $ean;
    }

    public function getDeliveryDates(): ?array
    {
        return $this->deliveryDates;
    }

    public function setDeliveryDates(?array $deliveryDates): void
    {
        $this->deliveryDates = $deliveryDates;
    }

    public function getMidCode(): ?string
    {
        return $this->midCode;
    }

    public function setMidCode(?string $midCode): void
    {
        $this->midCode = $midCode;
    }

    public function getMaterialContent(): ?string
    {
        return $this->materialContent;
    }

    public function setMaterialContent(?string $materialContent): void
    {
        $this->materialContent = $materialContent;
    }

    public function getIntendedUse(): ?string
    {
        return $this->intendedUse;
    }

    public function setIntendedUse(?string $intendedUse): void
    {
        $this->intendedUse = $intendedUse;
    }

    public function getDangerousGoods(): ?array
    {
        return $this->dangerousGoods;
    }

    public function setDangerousGoods(?array $dangerousGoods): void
    {
        $this->dangerousGoods = $dangerousGoods;
    }

    public function fields(): array
    {
        return [
            'name',
            'quantity',
            'total_price' => fn() => $this->totalPrice->toArray(),
            'item_id' => 'itemId',
            'product_id' => 'productId',
            'variant_id' => 'variantId',
            'image_url' => 'imageUrl',
            'description',
            'sku',
            'hs_code' => 'hsCode',
            'country_of_origin' => 'countryOfOrigin',
            'properties',
            'unit_price' => fn() => $this->unitPrice?->toArray(),
            'measurement',
            'ean',
            'delivery_dates' => 'deliveryDates',
            'mid_code' => 'midCode',
            'material_content' => 'materialContent',
            'intended_use' => 'intendedUse',
            'dangerous_goods' => 'dangerousGoods',
        ];
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = $this->traitToArray($fields, $expand, $recursive);
        return array_filter($data, fn($value) => !is_null($value));
    }
}
