<?php


namespace white\commerce\sendcloud\models;


use craft\base\Model;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as CommercePlugin;
use craft\helpers\ArrayHelper;

class OrderSyncStatus extends Model
{
    const STATUS_ANNOUNCED = 1;
    const STATUS_EN_ROUTE_TO_SORTING_CENTER = 3;
    const STATUS_DELIVERY_DELAYED = 4;
    const STATUS_SORTED = 5;
    const STATUS_NOT_SORTED = 6;
    const STATUS_BEING_SORTED = 7;
    const STATUS_DELIVERY_ATTEMPT_FAILED = 8;
    const STATUS_DELIVERED = 11;
    const STATUS_AWAITING_CUSTOMER_PICKUP = 12;
    const STATUS_ANNOUNCED_NOT_COLLECTED = 13;
    const STATUS_ERROR_COLLECTING = 15;
    const STATUS_SHIPMENT_PICKED_UP_BY_DRIVER = 22;
    const STATUS_UNABLE_TO_DELIVER = 80;
    const STATUS_PARCEL_EN_ROUTE = 91;
    const STATUS_DRIVER_EN_ROUTE = 92;
    const STATUS_SHIPMENT_COLLECTED_BY_CUSTOMER = 93;
    const STATUS_NO_LABEL = 999;
    const STATUS_READY_TO_SEND = 1000;
    const STATUS_BEING_ANNOUNCED = 1001;
    const STATUS_ANNOUNCEMENT_FAILED = 1002;
    const STATUS_UNKNOWN = 1337;
    const STATUS_CANCELLED_UPSTREAM = 1998;
    const STATUS_CANCELLATION_REQUESTED = 1999;
    const STATUS_CANCELLED = 2000;
    const STATUS_SUBMITTING_CANCELLATION_REQUEST = 2001;
    
    const STATUSES = [
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
        self::STATUS_NO_LABEL => "No label",
        self::STATUS_READY_TO_SEND => "Ready to send",
        self::STATUS_BEING_ANNOUNCED => "Being announced",
        self::STATUS_ANNOUNCEMENT_FAILED => "Announcement failed",
        self::STATUS_UNKNOWN => "Unknown status - check carrier track & trace page for more insights",
        self::STATUS_CANCELLED_UPSTREAM => "Cancelled upstream",
        self::STATUS_CANCELLATION_REQUESTED => "Cancellation requested",
        self::STATUS_CANCELLED => "Cancelled",
        self::STATUS_SUBMITTING_CANCELLATION_REQUEST => "Submitting cancellation request",
    ];
    
    /** @var integer */
    public $id;

    /** @var integer */
    public $orderId;

    /** @var integer|null */
    public $parcelId;

    /** @var integer|null */
    public $statusId;

    /** @var string|null */
    public $statusMessage;

    /** @var string|null */
    public $carrier;

    /** @var string|null */
    public $trackingNumber;

    /** @var string|null */
    public $trackingUrl;

    /** @var array|null */
    public $servicePoint;

    /** @var array|null */
    public $lastError;
    
    public $dateCreated;
    public $dateUpdated;
    public $uid;

    /**
     * @var Order Order
     */
    private $_order;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['orderId'], 'required'],
        ];
    }

    /**
     * @return Order|null
     */
    public function getOrder()
    {
        if ($this->_order === null && $this->orderId !== null) {
            $this->_order = CommercePlugin::getInstance()->getOrders()->getOrderById($this->orderId);
        }

        return $this->_order;
    }

    public function getServicePointId()
    {
        if (!$this->servicePoint) {
            return null;
        }

        return ArrayHelper::getValue($this->servicePoint, 'id');
    }

    public function fillFromParcel(Parcel $parcel)
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

    public function isPushed()
    {
        return $this->parcelId;
    }

    public function isLabelCreated()
    {
        return $this->parcelId && $this->statusId != self::STATUS_NO_LABEL;
    }
}
