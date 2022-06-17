<?php

namespace white\commerce\sendcloud\queue\jobs;

use craft\commerce\elements\Order;
use craft\queue\BaseJob;
use white\commerce\sendcloud\SendcloudPlugin;

class CreateLabel extends BaseJob
{
    public $orderId;

    public function execute($queue): void
    {
        $order = Order::find()->id($this->orderId)->anyStatus()->one();
        $labelCreated = SendcloudPlugin::getInstance()->orderSync->createLabel($order);

        if (!$labelCreated) {
            throw new \Exception('Failed to create Sendcloud label');
        }
    }

    protected function defaultDescription(): ?string
    {
        return 'Creating SendCloud label for order #' . $this->orderId;
    }
}