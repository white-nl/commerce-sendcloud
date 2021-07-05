<?php


namespace white\commerce\sendcloud\records;


use craft\commerce\elements\Order;
use craft\db\ActiveRecord;
use yii\db\ActiveQuery;

class OrderSyncStatus extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%sendcloud_ordersyncstatus}}';
    }

    public function getOrder(): ActiveQuery
    {
        return $this->hasOne(Order::class, ['id' => 'orderId']);
    }
}
