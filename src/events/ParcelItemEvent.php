<?php

namespace white\commerce\sendcloud\events;

use craft\commerce\models\LineItem;
use white\commerce\sendcloud\models\ParcelItem;
use yii\base\Event;

class ParcelItemEvent extends Event
{
    /**
     * @var ParcelItem The parcel item model.
     */
    public ParcelItem $parcelItem;

    /**
     * @var LineItem The Craft Commerce line item model that is used to create the parcel item.
     */
    public LineItem $lineItem;
}