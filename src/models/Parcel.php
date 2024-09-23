<?php

namespace white\commerce\sendcloud\models;

use DateTimeImmutable;
use white\commerce\sendcloud\enums\LabelFormat;
use white\commerce\sendcloud\enums\ParcelStatus;
use white\commerce\sendcloud\enums\ShipmentType;
use yii\base\Arrayable;
use yii\base\ArrayableTrait;

class Parcel implements Arrayable
{
    use ArrayableTrait {
        toArray as traitToArray;
    }

    private ?int $id = null;

    private ?int $contract = null;

    private Address $address;

    private bool $requestLabel = false;

    private string $email;

    private ShippingMethod $shippingMethod;

    /**
     * Weight of the parcel in kilograms, if none given the default weight from settings is used. If you provide no weight in your request we’ll use the default weight set in your settings.
     *
     * @var string|null
     */
    private ?string $weight = null;

    private string $orderNumber;

    private ?int $insuredValue = null;

    private string $totalOrderValueCurrency;

    private string $totalOrderValue;

    private int $quantity;

    private string $shippingMethodCheckoutName;

    private string $toPostNumber = '';

    private ?int $senderAddress = null;

    private string $customsInvoiceNr = '';

    private ShipmentType $customsShipmentType = ShipmentType::CommercialGoods;

    private ?string $reference = null;

    private ?string $externalReference = null;

    private ?int $toServicePoint = null;

    private ?int $totalInsuredValue = null;

    private ?string $shipmentUuid = null;

    /**
     * @var ParcelItem[]
     */
    private array $parcelItems = [];

    private bool $isReturn = false;

    private string $length;

    private string $width;

    private string $height;

    private bool $requestLabelAsync = false;

    private bool $applyShippingRules = true;

    private ?Address $returnSenderAddress = null;

    private ?\DateTimeImmutable $created = null;

    private ?string $trackingNumber = null;

    private ?ParcelStatus $parcelStatus = null;

    private ?string $carrier = null;

    /** @var string[]|null */
    public ?array $labelUrls;

    private ?string $trackingUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->getAddress()->getName();
    }

    public function getContract(): ?int
    {
        return $this->contract;
    }

    public function setContract(?int $contract): void
    {
        $this->contract = $contract;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    public function isRequestLabel(): bool
    {
        return $this->requestLabel;
    }

    public function setRequestLabel(bool $requestLabel): void
    {
        $this->requestLabel = $requestLabel;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getShippingMethod(): ShippingMethod
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(ShippingMethod $shippingMethod): void
    {
        $this->shippingMethod = $shippingMethod;
    }

    public function getWeight(): string|null
    {
        return $this->weight;
    }

    public function setWeight(?string $weight = null): void
    {
        $this->weight = $weight ? number_format((float)$weight, 3) : null;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function getInsuredValue(): ?int
    {
        return $this->insuredValue;
    }

    public function setInsuredValue(?int $insuredValue): void
    {
        $this->insuredValue = $insuredValue;
    }

    public function getTotalOrderValueCurrency(): string
    {
        return $this->totalOrderValueCurrency;
    }

    public function setTotalOrderValueCurrency(string $totalOrderValueCurrency): void
    {
        $this->totalOrderValueCurrency = $totalOrderValueCurrency;
    }

    public function getTotalOrderValue(): string
    {
        return $this->totalOrderValue;
    }

    public function setTotalOrderValue(string $totalOrderValue): void
    {
        $this->totalOrderValue = $totalOrderValue;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getShippingMethodCheckoutName(): string
    {
        return $this->shippingMethodCheckoutName;
    }

    public function setShippingMethodCheckoutName(string $shippingMethodCheckoutName): void
    {
        $this->shippingMethodCheckoutName = $shippingMethodCheckoutName;
    }

    public function getToPostNumber(): string
    {
        return $this->toPostNumber;
    }

    public function setToPostNumber(string $toPostNumber): void
    {
        $this->toPostNumber = $toPostNumber;
    }

    public function getSenderAddress(): ?int
    {
        return $this->senderAddress;
    }

    public function setSenderAddress(?int $senderAddress): void
    {
        $this->senderAddress = $senderAddress;
    }

    public function getCustomsInvoiceNr(): string
    {
        return $this->customsInvoiceNr;
    }

    public function setCustomsInvoiceNr(string $customsInvoiceNr): void
    {
        $this->customsInvoiceNr = $customsInvoiceNr;
    }

    public function getCustomsShipmentType(): ShipmentType
    {
        return $this->customsShipmentType;
    }

    public function setCustomsShipmentType(ShipmentType $customsShipmentType): void
    {
        $this->customsShipmentType = $customsShipmentType;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): void
    {
        $this->reference = $reference;
    }

    public function getExternalReference(): string
    {
        return $this->externalReference;
    }

    public function setExternalReference(?string $externalReference): void
    {
        $this->externalReference = $externalReference;
    }

    public function getToServicePoint(): ?int
    {
        return $this->toServicePoint;
    }

    public function setToServicePoint(?int $toServicePoint): void
    {
        $this->toServicePoint = $toServicePoint;
    }

    public function getTotalInsuredValue(): ?int
    {
        return $this->totalInsuredValue;
    }

    public function setTotalInsuredValue(?int $totalInsuredValue): void
    {
        $this->totalInsuredValue = $totalInsuredValue;
    }

    public function getShipmentUuid(): ?string
    {
        return $this->shipmentUuid;
    }

    public function setShipmentUuid(?string $shipmentUuid): void
    {
        $this->shipmentUuid = $shipmentUuid;
    }

    public function getParcelItems(): array
    {
        return $this->parcelItems;
    }

    public function setParcelItems(array $parcelItems): void
    {
        $this->parcelItems = $parcelItems;
    }

    public function isReturn(): bool
    {
        return $this->isReturn;
    }

    public function setIsReturn(bool $isReturn): void
    {
        $this->isReturn = $isReturn;
    }

    public function getLength(): string
    {
        return $this->length;
    }

    public function setLength(string $length): void
    {
        $this->length = $length;
    }

    public function getWidth(): string
    {
        return $this->width;
    }

    public function setWidth(string $width): void
    {
        $this->width = $width;
    }

    public function getHeight(): string
    {
        return $this->height;
    }

    public function setHeight(string $height): void
    {
        $this->height = $height;
    }

    public function isRequestLabelAsync(): bool
    {
        return $this->requestLabelAsync;
    }

    public function setRequestLabelAsync(bool $requestLabelAsync): void
    {
        $this->requestLabelAsync = $requestLabelAsync;
    }

    public function isApplyShippingRules(): bool
    {
        return $this->applyShippingRules;
    }

    public function setApplyShippingRules(bool $applyShippingRules): void
    {
        $this->applyShippingRules = $applyShippingRules;
    }

    public function getReturnSenderAddress(): ?Address
    {
        return $this->returnSenderAddress;
    }

    public function setReturnSenderAddress(?Address $returnSenderAddress): void
    {
        $this->returnSenderAddress = $returnSenderAddress;
    }

    public function getCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(?DateTimeImmutable $created): void
    {
        $this->created = $created;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): void
    {
        $this->trackingNumber = $trackingNumber;
    }

    public function getParcelStatus(): ?ParcelStatus
    {
        return $this->parcelStatus;
    }

    public function setParcelStatus(?ParcelStatus $parcelStatus): void
    {
        $this->parcelStatus = $parcelStatus;
    }

    public function getLabelUrls(): ?array
    {
        return $this->labelUrls;
    }

    public function getLabelUrl(LabelFormat $format): ?string
    {
        return $this->labelUrls[$format->name] ?? null;
    }

    public function setLabelUrls(?array $labelUrls): void
    {
        $this->labelUrls = $labelUrls;
    }

    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    public function setTrackingUrl(?string $trackingUrl): void
    {
        $this->trackingUrl = $trackingUrl;
    }

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function setCarrier(?string $carrier): void
    {
        $this->carrier = $carrier;
    }

    public static function fromData(array $data): self
    {
        $labelUrls = [];
        foreach (LabelFormat::cases() as $format) {
            $labelUrl = $format->getUrl($data);
            if ($labelUrl) {
                $labelUrls[$format->name] = $labelUrl;
            }
        }

        $parcelItems = [];
        if (isset($data['parcel_items'])) {
            foreach ($data['parcel_items'] as $parcel_item) {
                $parcelItemData = [
                    'hsCode' => $parcel_item['hs_code'],
                    'weight' => $parcel_item['weight'],
                    'quantity' => $parcel_item['quantity'],
                    'description' => $parcel_item['description'],
                    'originCountry' => $parcel_item['origin_country'],
                    'value' => $parcel_item['value'],
                    'sku' => $parcel_item['sku'],
                    'productId' => $parcel_item['product_id'],
                    'properties' => $parcel_item['properties'],
                    'itemId' => $parcel_item['item_id'],
                    'returnReason' => $parcel_item['return_reason'],
                    'returnMessage' => $parcel_item['return_message'],
                    'midCode' => $parcel_item['mid_code'] ?? null,
                    'materialContent' => $parcel_item['material_content'] ?? null,
                    'intendedUse' => $parcel_item['intended_use'] ?? null,
                ];
                $parcelItems[] = \Craft::createObject(ParcelItem::class, $parcelItemData);
            }
        }

        $errors = [];
        if (isset($data['errors'])) {
            foreach ($data['errors'] as $key => $error) {
                $errors[$key][] = $error;
            }
        }

        $parcel = new Parcel();
        $parcel->setId($data['id']);
        $parcel->setParcelStatus(ParcelStatus::tryFrom($data['status']['id']));
        $parcel->setEmail($data['email']);
        $parcel->setCreated(new DateTimeImmutable($data['date_created']));
        $parcel->setTrackingNumber($data['tracking_number']);
        $parcel->setWeight($data['weight']);
        $parcel->setLabelUrls($labelUrls);
        $parcel->setTrackingUrl($data['tracking_url'] ?? null);
        $parcel->setCarrier($data['carrier']['code']);
        $parcel->setOrderNumber($data['order_number']);
        $parcel->setToServicePoint($data['to_service_point']);
        $parcel->setCustomsInvoiceNr($data['customs_invoice_nr']);
        $parcel->setCustomsShipmentType(ShipmentType::tryFrom($data['customs_shipment_type']));
        $parcel->setParcelItems($parcelItems);
        $address = Address::fromParcelData($data);
        $parcel->setAddress($address);

        return $parcel;
    }

    public function fields(): array
    {
        return [
            'name' => fn(Parcel $parcel) => $parcel->getName(),
            'contract',
            'company_name' => fn(Parcel $parcel) => $parcel->getAddress()->getCompanyName(),
            'address' => fn(Parcel $parcel) => $parcel->getAddress()->getAddress(),
            'address_2' => fn(Parcel $parcel) => $parcel->getAddress()->getAddress2(),
            'city' => fn(Parcel $parcel) => $parcel->getAddress()->getCity(),
            'postal_code' => fn(Parcel $parcel) => $parcel->getAddress()->getPostalCode(),
            'country' => fn(Parcel $parcel) => $parcel->getAddress()->getCountry(),
            'country_state' => fn(Parcel $parcel) => $parcel->getAddress()->getCountryState(),
            'telephone' => fn(Parcel $parcel) => $parcel->getAddress()->getTelephone(),
            'email',
            'to_service_point' => 'toServicePoint',
            'to_post_number' => 'toPostNumber',
            'sender_address' => 'senderAddress',
            'order_number' => 'orderNumber',
            'weight',
            'customs_invoice_nr' => 'customsInvoiceNr',
            'customs_shipment_type' => fn(Parcel $parcel) => $parcel->getCustomsShipmentType()->value,
            'reference',
            'external_reference' => 'externalReference',
            'parcel_items' => fn(Parcel $parcel) => $parcel->getParcelItems(),
            'total_order_value_currency' => 'totalOrderValueCurrency',
            'total_order_value' => 'totalOrderValue',
            'insured_value' => 'insuredValue',
            'total_insured_value' => 'totalInsuredValue',
            'shipment' => fn(Parcel $parcel) => ['id' => $parcel->getShippingMethod()->getId()],
            'shipping_method_checkout_name' => 'shippingMethodCheckoutName',
            'request_label' => 'requestLabel',
            'request_label_async' => 'requestLabelAsync',
            'apply_shipping_rules' => 'applyShippingRules',
        ];
    }
}
