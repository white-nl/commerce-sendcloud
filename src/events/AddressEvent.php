<?php

namespace white\commerce\sendcloud\events;

use craft\elements\Address as CraftAddress;
use white\commerce\sendcloud\models\Address;
use yii\base\Event;

class AddressEvent extends Event
{
    public CraftAddress $shippingAddress;
    public Address $address;
}