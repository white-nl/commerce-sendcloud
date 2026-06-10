<?php

namespace white\commerce\sendcloud\events;

use craft\commerce\elements\Order;
use yii\base\Event;

class ValidateOrderEvent extends Event
{
    /**
     * @var Order The order
     */
    public Order $order;

    /**
     * @var bool
     */
    public bool $isValid;
}
