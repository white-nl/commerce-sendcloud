<?php

namespace white\commerce\sendcloud\events;

use craft\commerce\elements\Order;
use yii\base\Event;

class ParcelWeightEvent extends Event
{
    public ?int $weight;

    public Order $order;
}