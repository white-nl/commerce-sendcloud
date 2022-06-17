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
        $orderedPushed = SendcloudPlugin::getInstance()->orderSync->pushOrder($order, $this->force);

        if (!$orderedPushed) {
            throw new \Exception('Failed to push order to Sendcloud');
        }

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