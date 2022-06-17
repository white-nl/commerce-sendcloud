<?php

namespace white\commerce\sendcloud\queue\jobs;

use craft\commerce\elements\Order;
use craft\helpers\Queue;
use craft\queue\BaseJob;
use white\commerce\sendcloud\SendcloudPlugin;

class PushOrder extends BaseJob
{
    public $orderId;

    public $createLabel = false;

    public $force = false;

    public function execute($queue): void
    {
        $order = Order::find()->id($this->orderId)->anyStatus()->one();
        SendcloudPlugin::getInstance()->orderSync->pushOrder($order, $this->force);

        if ($this->createLabel) {
            Queue::push(new CreateLabel([
                'orderId' => $this->orderId,
            ]));
        }
    }

    protected function defaultDescription(): ?string
    {
        return 'Creating SendCloud parcel for order #' . $this->orderId;
    }
}