<?php

namespace white\commerce\sendcloud\services;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use craft\events\ModelEvent;
use craft\helpers\Queue;
use JouwWeb\SendCloud\Exception\SendCloudRequestException;
use white\commerce\sendcloud\models\OrderSyncStatus;
use white\commerce\sendcloud\queue\jobs\PushOrder;
use white\commerce\sendcloud\records\OrderSyncStatus as OrderSyncStatusRecord;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\base\Event;
use yii\base\InvalidArgumentException;
use yii\helpers\VarDumper;
use yii\log\Logger;

class OrderSync extends Component
{
    /** @var SendcloudApi */
    private $sendcloudApi;

    public function init()
    {
        parent::init();
        
        $this->sendcloudApi = SendcloudPlugin::getInstance()->sendcloudApi;
    }

    /**
     * Gets order synchronization status based on Craft order ID.
     * 
     * @param $orderId
     * @return OrderSyncStatus|null
     */
    public function getOrderSyncStatusByOrderId($orderId): ?OrderSyncStatus
    {
        $record = OrderSyncStatusRecord::findOne([
            'orderId' => $orderId,
        ]);
        if (!$record) {
            return null;
        }

        return new OrderSyncStatus($record);
    }

    /**
     * Gets order synchronization status based on Sendcloud parcel ID.
     * 
     * @param integer $parcelId
     * @return OrderSyncStatus|null
     */
    public function getOrderSyncStatusByParcelId($parcelId): ?OrderSyncStatus
    {
        $record = OrderSyncStatusRecord::findOne([
            'parcelId' => $parcelId,
        ]);
        if (!$record) {
            return null;
        }

        return new OrderSyncStatus($record);
    }

    /**
     * Gets the order synchronization status or creates a new status if couldn't find any existing one.
     * 
     * @param Order $order
     * @return OrderSyncStatus
     */
    public function getOrCreateOrderSyncStatus(Order $order): OrderSyncStatus
    {
        $model = $this->getOrderSyncStatusByOrderId($order->id);
        if (!$model) {
            return new OrderSyncStatus(['orderId' => $order->id]);
        }

        return $model;
    }

    /**
     * Saves the order synchronization status.
     * 
     * @param OrderSyncStatus $model
     * @param bool $runValidation
     * @return bool
     */
    public function saveOrderSyncStatus(OrderSyncStatus $model, bool $runValidation = true): bool
    {
        if ($model->id) {
            $record = OrderSyncStatusRecord::findOne($model->id);
            if (!$record) {
                throw new InvalidArgumentException('No order sync status exists with the ID “{id}”', ['id' => $model->id]);
            }
        } else {
            $record = new OrderSyncStatusRecord([
                'orderId' => $model->orderId,
            ]);
        }

        if ($runValidation && !$model->validate()) {
            return false;
        }

        $record->parcelId = $model->parcelId;
        $record->statusId = $model->statusId;
        $record->statusMessage = $model->statusMessage;
        $record->carrier = $model->carrier;
        $record->trackingNumber = $model->trackingNumber;
        $record->trackingUrl = $model->trackingUrl;
        $record->servicePoint = $model->servicePoint;
        $record->lastError = $model->lastError;
        $record->lastWebhookTimestamp = $model->lastWebhookTimestamp;

        $record->save(false);
        $model->id = $record->id;
        $model->dateCreated = $record->dateCreated;

        return true;
    }

    /**
     * Deletes order status.
     * 
     * @param integer $id
     * @return bool
     */
    public function deleteOrderSyncStatusById($id): bool
    {
        return OrderSyncStatusRecord::deleteAll(['id' => $id]) > 0;
    }

    /**
     * Registers Craft event listeners required for order synchronization.
     */
    public function registerEventListeners()
    {
        Event::on(
            Order::class,
            Order::EVENT_AFTER_SAVE,
            function (ModelEvent $event) {
                if ($event->sender->propagating) {
                    return;
                }

                try {
                    $this->syncOrder($event->sender);
                } catch (\Exception $e) {
                    SendcloudPlugin::error("Could not synchronize an order with Sendcloud.", $e);
                }
            }
        );

        // Check if the chosen method shipping method should have servicePoint info
        Event::on(
            Order::class,
            Order::EVENT_BEFORE_COMPLETE_ORDER,
            function (Event $event) {
                if ($event->sender->propagating) {
                    return;
                }
                /** @var Order $order */
                $order = $event->sender;
                $status = $this->getOrderSyncStatusByOrderId($order->id);
                $isSendcloudShipping = false;

                if ($status && $status->servicePoint) {
                    foreach ($this->sendcloudApi->getClient()->getShippingMethods() as $method) {
                        // Find the matching sendcloud shipping
                        if ($method->getName() == $order->shippingMethodName) {
                            $isSendcloudShipping = true;
                            if (!$method->getAllowsServicePoints()) {
                                // remove the servicePoint info
                                $status->servicePoint = null;
                                $this->saveOrderSyncStatus($status);
                            }
                            break;
                        }
                    }
                    if (!$isSendcloudShipping) {
                        // remove the servicePoint info
                        $status->servicePoint = null;
                        $this->saveOrderSyncStatus($status);
                    }
                }
            }
        );
    }

    /**
     * Synchronizes the order with Sendcloud according to the mapping defined in the plugin settings.
     * 
     * @param Order $order
     * @throws \Exception
     */
    public function syncOrder(Order $order)
    {
        if (!$order->isCompleted) {
            return;
        }
        
        $orderStatus = $order->getOrderStatus();
        if (!$orderStatus) {
            return;
        }
        
        $settings = SendcloudPlugin::getInstance()->getSettings();
        
        if (!in_array($orderStatus->handle, $settings->orderStatusesToPush) && !in_array($orderStatus->handle, $settings->orderStatusesToCreateLabel)) {
            return;
        }
        
        if (!$this->validateOrder($order)) {
            return;
        }

        $createLabel = in_array($orderStatus->handle, $settings->orderStatusesToCreateLabel);

        Queue::push(new PushOrder([
            'orderId' => $order->getId(),
            'createLabel' => $createLabel
        ]));
    }

    /**
     * Pushes the order to Sendcloud that hasn't been pushed yet.
     * 
     * @param Order $order
     * @param bool $force
     * @return bool
     * @throws \craft\errors\SiteNotFoundException
     */
    public function pushOrder(Order $order, $force = false)
    {
        $lockName = 'sendcloud:pushOrder:' . $order->getId();
        $mutex = Craft::$app->getMutex();
        if (!$mutex->acquire($lockName, 5)) {
            return false;
        }
        $status = $this->getOrCreateOrderSyncStatus($order);
        try {
            if ($status->isPushed() && !$force) {
                return false;
            }

            $client = $this->sendcloudApi->getClient();
            $parcel = null;
            if ($status->isPushed()) {
                try {
                    $parcel = $client->updateParcel($status->parcelId, $order);
                } catch (SendCloudRequestException $e) {
                    if ($e->getSendCloudCode() != 404) {
                        throw $e;
                    }
                }
            }
            
            if (!$parcel) {
                $parcel = $client->createParcel($order, $status->getServicePointId());
            }
    
            $status->fillFromParcel($parcel);
            $status->lastError = null;
            if (!$this->saveOrderSyncStatus($status)) {
                throw new \Exception("Could not save order sync status: " . VarDumper::dumpAsString($status->errors));
            }
        } catch (\Exception $e) {
            $status->lastError = $e instanceof SendCloudRequestException ? $e->getSendCloudMessage() : $e->getMessage();
            $this->saveOrderSyncStatus($status);

            return false;
        } finally {
            $mutex->release($lockName);
        }

        return true;
    }

    public function createLabel(Order $order)
    {
        $lockName = 'sendcloud:createLabel:' . $order->getId();
        $mutex = Craft::$app->getMutex();
        if (!$mutex->acquire($lockName, 5)) {
            return false;
        }
        $status = $this->getOrCreateOrderSyncStatus($order);

        try {
            $client = $this->sendcloudApi->getClient();

            if (!$status->isPushed() || $status->isLabelCreated()) {
                return false;
            }

            $parcel = $client->createLabel($order, $status->parcelId);

            $status->fillFromParcel($parcel);
            $status->lastError = null;
            if (!$this->saveOrderSyncStatus($status)) {
                throw new \Exception("Could not save order sync status: " . VarDumper::dumpAsString($status->errors));
            }
        } catch (\Exception $e) {
            $status->lastError = $e instanceof SendCloudRequestException ? $e->getSendCloudMessage() : $e->getMessage();
            $this->saveOrderSyncStatus($status);
            
            return false;
        } finally {
            $mutex->release($lockName);
        }

        return true;
    }

    protected function validateOrder(Order $order): bool
    {
        if (!$order->shippingAddress) {
            SendcloudPlugin::log("Shipping address not found", Logger::LEVEL_WARNING);
            return false;
        }
        
        if (!$order->shippingMethod) {
            SendcloudPlugin::log("Order shipping method not found", Logger::LEVEL_WARNING);
            return false;
        }

        $client = $this->sendcloudApi->getClient();
        if (!isset($client->getShippingMethods()[$order->shippingMethodName])) {
            SendcloudPlugin::log("Sendcloud shipping method not found", Logger::LEVEL_WARNING);
            return false;
        }
        
        return true;
    }
}
