<?php

namespace white\commerce\sendcloud\models;

use DateTimeImmutable;

final class Parcel
{
    /**
     * @var int
     */
    public const LABEL_FORMAT_A6 = 1;

    /**
     * @var int
     */
    public const LABEL_FORMAT_A4_TOP_LEFT = 2;

    /**
     * @var int
     */
    public const LABEL_FORMAT_A4_TOP_RIGHT = 3;

    /**
     * @var int
     */
    public const LABEL_FORMAT_A4_BOTTOM_LEFT = 4;

    /**
     * @var int
     */
    public const LABEL_FORMAT_A4_BOTTOM_RIGHT = 5;

    /**
     * @var int[]
     */
    public const LABEL_FORMATS = [
        self::LABEL_FORMAT_A6,
        self::LABEL_FORMAT_A4_TOP_LEFT,
        self::LABEL_FORMAT_A4_TOP_RIGHT,
        self::LABEL_FORMAT_A4_BOTTOM_LEFT,
        self::LABEL_FORMAT_A4_BOTTOM_RIGHT,
    ];

    // Obtained from https://panel.sendcloud.sc/api/v2/parcels/statuses (with API auth)
    /**
     * @var int
     */
    public const STATUS_ANNOUNCED = 1;

    /**
     * @var int
     */
    public const STATUS_EN_ROUTE_TO_SORTING_CENTER = 3;

    /**
     * @var int
     */
    public const STATUS_DELIVERY_DELAYED = 4;

    /**
     * @var int
     */
    public const STATUS_SORTED = 5;

    /**
     * @var int
     */
    public const STATUS_NOT_SORTED = 6;

    /**
     * @var int
     */
    public const STATUS_BEING_SORTED = 7;

    /**
     * @var int
     */
    public const STATUS_DELIVERY_ATTEMPT_FAILED = 8;

    /**
     * @var int
     */
    public const STATUS_DELIVERED = 11;

    /**
     * @var int
     */
    public const STATUS_AWAITING_CUSTOMER_PICKUP = 12;

    /**
     * @var int
     */
    public const STATUS_ANNOUNCED_NOT_COLLECTED = 13;

    /**
     * @var int
     */
    public const STATUS_ERROR_COLLECTING = 15;

    /**
     * @var int
     */
    public const STATUS_SHIPMENT_PICKED_UP_BY_DRIVER = 22;

    /**
     * @var int
     */
    public const STATUS_UNABLE_TO_DELIVER = 80;

    /**
     * @var int
     */
    public const STATUS_PARCEL_EN_ROUTE = 91;

    /**
     * @var int
     */
    public const STATUS_DRIVER_EN_ROUTE = 92;

    /**
     * @var int
     */
    public const STATUS_SHIPMENT_COLLECTED_BY_CUSTOMER = 93;

    /**
     * @var int
     */
    public const STATUS_NO_LABEL = 999;

    /**
     * @var int
     */
    public const STATUS_READY_TO_SEND = 1000;

    /**
     * @var int
     */
    public const STATUS_BEING_ANNOUNCED = 1001;

    /**
     * @var int
     */
    public const STATUS_ANNOUNCEMENT_FAILED = 1002;

    /**
     * @var int
     */
    public const STATUS_UNKNOWN_STATUS = 1337;

    /**
     * @var int
     */
    public const STATUS_CANCELLED_UPSTREAM = 1998;

    /**
     * @var int
     */
    public const STATUS_CANCELLATION_REQUESTED = 1999;

    /**
     * @var int
     */
    public const STATUS_CANCELLED = 2000;

    /**
     * @var int
     */
    public const STATUS_SUBMITTING_CANCELLATION_REQUEST = 2001;

    /**
     * @var int[]
     */
    public const STATUSES = [
        self::STATUS_ANNOUNCED,
        self::STATUS_EN_ROUTE_TO_SORTING_CENTER,
        self::STATUS_DELIVERY_DELAYED,
        self::STATUS_SORTED,
        self::STATUS_NOT_SORTED,
        self::STATUS_BEING_SORTED,
        self::STATUS_DELIVERY_ATTEMPT_FAILED,
        self::STATUS_DELIVERED,
        self::STATUS_AWAITING_CUSTOMER_PICKUP,
        self::STATUS_ANNOUNCED_NOT_COLLECTED,
        self::STATUS_ERROR_COLLECTING,
        self::STATUS_SHIPMENT_PICKED_UP_BY_DRIVER,
        self::STATUS_UNABLE_TO_DELIVER,
        self::STATUS_PARCEL_EN_ROUTE,
        self::STATUS_DRIVER_EN_ROUTE,
        self::STATUS_SHIPMENT_COLLECTED_BY_CUSTOMER,
        self::STATUS_NO_LABEL,
        self::STATUS_READY_TO_SEND,
        self::STATUS_BEING_ANNOUNCED,
        self::STATUS_ANNOUNCEMENT_FAILED,
        self::STATUS_UNKNOWN_STATUS,
        self::STATUS_CANCELLED_UPSTREAM,
        self::STATUS_CANCELLATION_REQUESTED,
        self::STATUS_CANCELLED,
        self::STATUS_SUBMITTING_CANCELLATION_REQUEST,
    ];

    private ?\DateTimeImmutable $created = null;

    private ?string $trackingNumber = null;

    private ?string $statusMessage = null;

    private ?int $statusId = null;

    private ?int $id = null;

    /** @var string[]|null */
    public ?array $labelUrls;

    private ?string $trackingUrl = null;

    private ?Address $address = null;

    private ?int $weight = null;

    private ?string $carrier = null;

    private ?string $orderNumber = null;

    private ?int $shippingMethodId = null;

    private ?int $servicePointId = null;

    /**
     * @return DateTimeImmutable
     */
    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * @param DateTimeImmutable $created
     * @return void
     */
    public function setCreated(DateTimeImmutable $created): void
    {
        $this->created = $created;
    }

    /**
     * @return string|null
     */
    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    /**
     * @param string $trackingNumber
     * @return void
     */
    public function setTrackingNumber(string $trackingNumber): void
    {
        $this->trackingNumber = $trackingNumber;
    }

    /**
     * @return string
     */
    public function getStatusMessage(): string
    {
        return $this->statusMessage;
    }

    /**
     * @param string $statusMessage
     * @return void
     */
    public function setStatusMessage(string $statusMessage): void
    {
        $this->statusMessage = $statusMessage;
    }

    /**
     * @return int
     */
    public function getStatusId(): int
    {
        return $this->statusId;
    }

    /**
     * @param int $statusId
     * @return void
     */
    public function setStatusId(int $statusId): void
    {
        $this->statusId = $statusId;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @param int $format
     * @return string|null
     */
    public function getLabelUrl(int $format): ?string
    {
        return $this->labelUrls[$format] ?? null;
    }

    /**
     * @return string|null
     */
    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    /**
     * @param string|null $trackingUrl
     * @return void
     */
    public function setTrackingUrl(?string $trackingUrl): void
    {
        $this->trackingUrl = $trackingUrl;
    }

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @param Address $address
     * @return void
     */
    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * @param int $weight
     * @return void
     */
    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * @return string|null
     */
    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    /**
     * @param string|null $carrier
     * @return void
     */
    public function setCarrier(?string $carrier): void
    {
        $this->carrier = $carrier;
    }

    /**
     * @return string|null
     */
    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    /**
     * @param string $orderNumber
     * @return void
     */
    public function setOrderNumber(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    /**
     * @return int|null
     */
    public function getShippingMethodId(): ?int
    {
        return $this->shippingMethodId;
    }

    /**
     * @param int|null $shippingMethodId
     * @return void
     */
    public function setShippingMethodId(?int $shippingMethodId): void
    {
        $this->shippingMethodId = $shippingMethodId;
    }

    /**
     * @return int|null
     */
    public function getServicePointId(): ?int
    {
        return $this->servicePointId;
    }

    /**
     * @param int|null $servicePointId
     * @return void
     */
    public function setServicePointId(?int $servicePointId): void
    {
        $this->servicePointId = $servicePointId;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->getStatusId() . ': ' . $this->getStatusMessage();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'address' => $this->getAddress()->toArray(),
            'carrier' => $this->getCarrier(),
            'created' => $this->getCreated()->format(DATE_ATOM),
            'id' => $this->getId(),
            'labels' => array_map(fn($format) => $this->getLabelUrl($format), self::LABEL_FORMATS),
            'orderNumber' => $this->getOrderNumber(),
            'servicePointId' => $this->getServicePointId(),
            'shippingMethodId' => $this->getShippingMethodId(),
            'statusId' => $this->getStatusId(),
            'statusMessage' => $this->getStatusMessage(),
            'trackingNumber' => $this->getTrackingNumber(),
            'trackingUrl' => $this->getTrackingUrl(),
            'weight' => $this->getWeight(),
        ];
    }
}
