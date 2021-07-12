<?php

namespace white\commerce\sendcloud\client;

use craft\base\Element;
use craft\commerce\base\PurchasableInterface;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use GuzzleHttp\Exception\RequestException;
use JouwWeb\SendCloud\Client;
use JouwWeb\SendCloud\Model\Parcel;
use JouwWeb\SendCloud\Model\Address;
use JouwWeb\SendCloud\Exception\SendCloudRequestException;
use white\commerce\sendcloud\models\ParcelItem;
use white\commerce\sendcloud\SendcloudPlugin;

/**
 * Client to perform calls on the Sendcloud API.
 */
class SendcloudClient extends Client
{
}
