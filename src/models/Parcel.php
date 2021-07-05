<?php

namespace white\commerce\sendcloud\models;

use DateTimeImmutable;

final class Parcel
{
    public const LABEL_FORMAT_A6 = 1;
    public const LABEL_FORMAT_A4_TOP_LEFT = 2;
    public const LABEL_FORMAT_A4_TOP_RIGHT = 3;
    public const LABEL_FORMAT_A4_BOTTOM_LEFT = 4;
    public const LABEL_FORMAT_A4_BOTTOM_RIGHT = 5;
    public const LABEL_FORMATS = [
        self::LABEL_FORMAT_A6,
        self::LABEL_FORMAT_A4_TOP_LEFT,
        self::LABEL_FORMAT_A4_TOP_RIGHT,
        self::LABEL_FORMAT_A4_BOTTOM_LEFT,
        self::LABEL_FORMAT_A4_BOTTOM_RIGHT,
    ];

    // Obtained from https://panel.sendcloud.sc/api/v2/parcels/statuses (with API auth)
    public const STATUS_ANNOUNCED = 1;
    public const STATUS_EN_ROUTE_TO_SORTING_CENTER = 3;
    public const STATUS_DELIVERY_DELAYED = 4;
    public const STATUS_SORTED = 5;
    public const STATUS_NOT_SORTED = 6;
    public const STATUS_BEING_SORTED = 7;
    public const STATUS_DELIVERY_ATTEMPT_FAILED = 8;
    public const STATUS_DELIVERED = 11;
    public const STATUS_AWAITING_CUSTOMER_PICKUP = 12;
    public const STATUS_ANNOUNCED_NOT_COLLECTED = 13;
    public const STATUS_ERROR_COLLECTING = 15;
    public const STATUS_SHIPMENT_PICKED_UP_BY_DRIVER = 22;
    public const STATUS_UNABLE_TO_DELIVER = 80;
    public const STATUS_PARCEL_EN_ROUTE = 91;
    public const STATUS_DRIVER_EN_ROUTE = 92;
    public const STATUS_SHIPMENT_COLLECTED_BY_CUSTOMER = 93;
    public const STATUS_NO_LABEL = 999;
    public const STATUS_READY_TO_SEND = 1000;
    public const STATUS_BEING_ANNOUNCED = 1001;
    public const STATUS_ANNOUNCEMENT_FAILED = 1002;
    public const STATUS_UNKNOWN_STATUS = 1337;
    public const STATUS_CANCELLED_UPSTREAM = 1998;
    public const STATUS_CANCELLATION_REQUESTED = 1999;
    public const STATUS_CANCELLED = 2000;
    public const STATUS_SUBMITTING_CANCELLATION_REQUEST = 2001;
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

    /** @var DateTimeImmutable */
    private $created;

    /** @var string */
    private $trackingNumber;

    /** @var string */
    private $statusMessage;

    /** @var int */
    private $statusId;

    /** @var int */
    private $id;

    /** @var string[]|null */
    public $labelUrls;

    /** @var string|null */
    private $trackingUrl;

    /** @var Address */
    private $address;

    /** @var int */
    private $weight;

    /** @var string|null */
    private $carrier;

    /** @var string|null */
    private $orderNumber;

    /** @var int|null */
    private $shippingMethodId;

    /** @var int|null */
    private $servicePointId;

    /**
     * @return DateTimeImmutable
     */
    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * @param DateTimeImmutable $created
     */
    public function setCreated(DateTimeImmutable $created): void
    {
        $this->created = $created;
    }

    /**
     * @return string
     */
    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    /**
     * @param string $trackingNumber
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
     * @param string|null $orderNumber
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
            'labels' => array_map(function ($format) {
                return $this->getLabelUrl($format);
            }, self::LABEL_FORMATS),
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
