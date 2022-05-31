<?php

namespace white\commerce\sendcloud\client;

use InvalidArgumentException;
use white\commerce\sendcloud\models\Parcel;
use yii\helpers\ArrayHelper;

final class WebhookParcelNormalizer
{
    private int $parcelId;
    private string $orderNumber;
    private int $statusId;
    private string $statusMessage;
    private string|null $carrier;
    private string $trackingNumber;
    private string|null  $trackingUrl;

    /**
     * @param array $params
     * @throws \Exception
     */
    public function __construct(array $params)
    {
        if (null === $this->statusId = ArrayHelper::getValue($params, 'status.id')) {
            throw new InvalidArgumentException('Key "status" not found');
        }

        if (null === $this->statusMessage = ArrayHelper::getValue($params, 'status.message')) {
            throw new InvalidArgumentException('Key "status" not found');
        }

        if (null === $this->parcelId = ArrayHelper::getValue($params, 'id')) {
            throw new InvalidArgumentException('Key "id" not found');
        }

        $this->orderNumber = ArrayHelper::getValue($params, 'order_number');
        $this->carrier = ArrayHelper::getValue($params, 'carrier.code');
        $this->trackingNumber = ArrayHelper::getValue($params, 'tracking_number');
        $this->trackingUrl = ArrayHelper::getValue($params, 'tracking_url');
    }

    /**
     * @return Parcel
     */
    public function getParcel(): Parcel
    {
        $parcel = new Parcel();
        $parcel->setId($this->parcelId);
        $parcel->setStatusId((int)$this->statusId);
        $parcel->setStatusMessage($this->statusMessage);
        $parcel->setOrderNumber($this->orderNumber);
        $parcel->setCarrier($this->carrier);
        $parcel->setTrackingNumber($this->trackingNumber);
        $parcel->setTrackingUrl($this->trackingUrl);

        return $parcel;
    }
}
