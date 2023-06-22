<?php

namespace white\commerce\sendcloud\queue\jobs;

use craft\commerce\elements\Order;
use craft\helpers\Queue;
use craft\queue\BaseJob;
use white\commerce\sendcloud\SendcloudPlugin;

class PushOrder extends BaseJob
{
    public int $orderId;

    public bool $createLabel = false;

    public bool $force = false;

    public function execute($queue): void
    {
        /** @var Order $order */
        $order = Order::find()->id($this->orderId)->status(null)->one();
        SendcloudPlugin::getInstance()->orderSync->pushOrder($order, $this->force);

        if ($this->createLabel) {
            $job = new CreateLabel([
                'orderId' => $this->orderId,
            ]);

            $settings = SendcloudPlugin::getInstance()->getSettings();
            Queue::push($job, $settings->createLabelJobPriority);
        }
    }

    protected function defaultDescription(): ?string
    {
        return 'Creating Sendcloud parcel for order #' . $this->orderId;
    }
}
