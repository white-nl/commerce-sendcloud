<?php

namespace white\commerce\sendcloud\events;

use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use white\commerce\sendcloud\models\Parcel;
use white\commerce\sendcloud\models\ParcelItem;
use yii\base\Event;

class ParcelEvent extends Event
{
    /**
     * @var Parcel The parcel model.
     */
    public Parcel $parcel;

    /**
     * @var Order The Craft Commerce order that is used to create the parcel.
     */
    public Order $order;
}