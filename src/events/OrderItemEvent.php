<?php

namespace white\commerce\sendcloud\events;

use craft\commerce\models\LineItem;
use white\commerce\sendcloud\models\OrderItem;
use yii\base\Event;

class OrderItemEvent extends Event
{
    /**
     * @var OrderItem The order item model.
     */
    public OrderItem $orderItem;

    /**
     * @var LineItem The Craft Commerce line item model that is used to create the parcel item.
     */
    public LineItem $lineItem;
}
