<?php

namespace white\commerce\sendcloud\events;

use craft\commerce\elements\Order;
use white\commerce\sendcloud\models\OrderDetails;
use yii\base\Event;

class OrderDetailsEvent extends Event
{
    /**
     * @var OrderDetails The order details model.
     */
    public OrderDetails $orderDetails;

    /**
     * @var Order The Craft Commerce order that is used to create the order details.
     */
    public Order $order;
}
