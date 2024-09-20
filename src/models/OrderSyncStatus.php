<?php


namespace white\commerce\sendcloud\models;

use craft\base\Model;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as CommercePlugin;
use craft\helpers\ArrayHelper;
use DateTime;
use white\commerce\sendcloud\enums\ParcelStatus;
use yii\base\InvalidConfigException;

/**
 *
 * @property-read null $servicePointId
 * @property-read Order|null $order
 */
class OrderSyncStatus extends Model
{
    /** @var integer */
    public int $id;

    /** @var integer */
    public int $orderId;

    /** @var integer|null */
    public ?int $parcelId = null;

    public ?int $statusId = null;

    public ?string $statusMessage = null;

    public ?ParcelStatus $parcelStatus = null;

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


    public function __construct($config = [])
    {
        parent::__construct($config);

        if ($this->statusId) {
            $this->parcelStatus = ParcelStatus::tryFrom($this->statusId);
        }
    }

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
        $this->parcelStatus = $parcel->getParcelStatus();

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
        return $this->parcelId && $this->parcelStatus !== ParcelStatus::NO_LABEL;
    }
}
