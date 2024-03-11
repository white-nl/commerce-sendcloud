<?php


namespace white\commerce\sendcloud\models;

use craft\base\Model;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as CommercePlugin;
use craft\helpers\ArrayHelper;
use DateTime;
use yii\base\InvalidConfigException;

/**
 *
 * @property-read null $servicePointId
 * @property-read Order|null $order
 */
class OrderSyncStatus extends Model
{
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
    public const STATUS_PARCEL_CANCELLATION_FAILED = 94;

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
    public const STATUS_UNKNOWN = 1337;

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
     * @var int
     */
    public const STATUS_EXCEPTION = 62996;

    /**
     * @var int
     */
    public const STATUS_AT_CUSTOMS = 62989;

    /**
     * @var int
     */
    public const STATUS_DELIVERY_METHOD_CHANGED = 62993;

    /**
     * @var int
     */
    public const STATUS_AT_SORTING_CENTRE = 62990;

    /**
     * @var int
     */
    public const STATUS_REFUSED_BY_RECIPIENT = 62991;

    /**
     * @var int
     */
    public const STATUS_RETURNED_TO_SENDER = 62992;

    /**
     * @var int
     */
    public const STATUS_DELIVERY_DATE_CHANGED = 62994;

    /**
     * @var int
     */
    public const STATUS_DELIVERY_ADDRESS_CHANGED = 62995;

    /**
     * @var int
     */
    public const STATUS_ADDRESS_INVALID = 62997;

    /**
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_ANNOUNCED => "Announced",
        self::STATUS_EN_ROUTE_TO_SORTING_CENTER => "En route to sorting center",
        self::STATUS_DELIVERY_DELAYED => "Delivery delayed",
        self::STATUS_SORTED => "Sorted",
        self::STATUS_NOT_SORTED => "Not sorted",
        self::STATUS_BEING_SORTED => "Being sorted",
        self::STATUS_DELIVERY_ATTEMPT_FAILED => "Delivery attempt failed",
        self::STATUS_DELIVERED => "Delivered",
        self::STATUS_AWAITING_CUSTOMER_PICKUP => "Awaiting customer pickup",
        self::STATUS_ANNOUNCED_NOT_COLLECTED => "Announced: not collected",
        self::STATUS_ERROR_COLLECTING => "Error collecting",
        self::STATUS_SHIPMENT_PICKED_UP_BY_DRIVER => "Shipment picked up by driver",
        self::STATUS_UNABLE_TO_DELIVER => "Unable to deliver",
        self::STATUS_PARCEL_EN_ROUTE => "Parcel en route",
        self::STATUS_DRIVER_EN_ROUTE => "Driver en route",
        self::STATUS_SHIPMENT_COLLECTED_BY_CUSTOMER => "Shipment collected by customer",
        self::STATUS_PARCEL_CANCELLATION_FAILED => "Parcel cancellation failed.",
        self::STATUS_NO_LABEL => "No label",
        self::STATUS_READY_TO_SEND => "Ready to send",
        self::STATUS_BEING_ANNOUNCED => "Being announced",
        self::STATUS_ANNOUNCEMENT_FAILED => "Announcement failed",
        self::STATUS_UNKNOWN => "Unknown status - check carrier track & trace page for more insights",
        self::STATUS_CANCELLED_UPSTREAM => "Cancelled upstream",
        self::STATUS_CANCELLATION_REQUESTED => "Cancellation requested",
        self::STATUS_CANCELLED => "Cancelled",
        self::STATUS_SUBMITTING_CANCELLATION_REQUEST => "Submitting cancellation request",
        self::STATUS_EXCEPTION => "Exception",
        self::STATUS_AT_CUSTOMS => "At Customs",
        self::STATUS_AT_SORTING_CENTRE => "At sorting centre",
        self::STATUS_REFUSED_BY_RECIPIENT => "Refused by recipient",
        self::STATUS_RETURNED_TO_SENDER => "Returned to sender",
        self::STATUS_DELIVERY_METHOD_CHANGED => "Delivery method changed",
        self::STATUS_DELIVERY_DATE_CHANGED => "Delivery date changed",
        self::STATUS_DELIVERY_ADDRESS_CHANGED => "Delivery address changed",
        self::STATUS_ADDRESS_INVALID => "Address invalid",
    ];

    /** @var integer */
    public int $id;

    /** @var integer */
    public int $orderId;

    /** @var integer|null */
    public ?int $parcelId = null;

    /** @var integer|null */
    public ?int $statusId = null;

    /** @var string|null */
    public ?string $statusMessage = null;

    /** @var string|null */
    public ?string $carrier = null;

    /** @var string|null */
    public ?string $trackingNumber = null;

    /** @var string|null */
    public ?string $trackingUrl = null;

    /** @var array|null */
    public ?array $servicePoint = [];

    /** @var array|string|null */
    public array|string|null $lastError = null;

    /** @var integer|null */
    public ?int $lastWebhookTimestamp = null;

    public DateTime $dateCreated;

    public DateTime $dateUpdated;

    public string $uid;

    /**
     * @var ?Order Order
     */
    private ?Order $_order = null;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['orderId'], 'required'],
        ];
    }

    /**
     * @return Order|null
     * @throws InvalidConfigException
     */
    public function getOrder(): ?Order
    {
        if (!$this->_order instanceof Order) {
            $this->_order = CommercePlugin::getInstance()->getOrders()->getOrderById($this->orderId);
        }

        return $this->_order;
    }

    /**
     * @return mixed|null
     * @throws \Exception
     */
    public function getServicePointId()
    {
        if (!$this->servicePoint) {
            return null;
        }

        return ArrayHelper::getValue($this->servicePoint, 'id');
    }

    /**
     * @param Parcel $parcel
     * @return void
     */
    public function fillFromParcel(Parcel $parcel): void
    {
        $this->parcelId = $parcel->getId();
        $this->statusId = $parcel->getStatusId();
        $this->statusMessage = $parcel->getStatusMessage();

        if (!empty($parcel->getCarrier())) {
            $this->carrier = $parcel->getCarrier();
        }

        if ($parcel->getTrackingNumber()) {
            $this->trackingNumber = $parcel->getTrackingNumber();
            $this->trackingUrl = $parcel->getTrackingUrl();
        }
    }

    /**
     * @return int|null
     */
    public function isPushed(): ?int
    {
        return $this->parcelId ?? null;
    }

    /**
     * @return bool
     */
    public function isLabelCreated(): bool
    {
        return $this->parcelId && $this->statusId != self::STATUS_NO_LABEL;
    }
}
