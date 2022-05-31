<?php


namespace white\commerce\sendcloud\records;

use craft\commerce\elements\Order;
use craft\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * @property int|null $parcelId
 * @property int|null $statusId
 * @property string|null $statusMessage
 * @property string|null $carrier
 * @property string|null $trackingNumber
 * @property string|null $trackingUrl
 * @property array|null $servicePoint
 * @property array|string|null $lastError
 * @property int|null $lastWebhookTimestamp
 * @property-read ActiveQuery $order
 */
class OrderSyncStatus extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%sendcloud_ordersyncstatus}}';
    }

    /**
     * @return ActiveQuery
     */
    public function getOrder(): ActiveQuery
    {
        return $this->hasOne(Order::class, ['id' => 'orderId']);
    }
}
