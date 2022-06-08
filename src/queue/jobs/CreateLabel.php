<?php

namespace white\commerce\sendcloud\queue\jobs;

use craft\commerce\elements\Order;
use craft\queue\BaseJob;
use white\commerce\sendcloud\SendcloudPlugin;

class CreateLabel extends BaseJob
{
    public int $orderId;

    public function execute($queue): void
    {
        /** @var Order $order */
        $order = Order::find()->id($this->orderId)->status(null)->one();
        SendcloudPlugin::getInstance()->orderSync->createLabel($order);
    }

    protected function defaultDescription(): ?string
    {
        return 'Creating Sendcloud label for order #' . $this->orderId;
    }
}
