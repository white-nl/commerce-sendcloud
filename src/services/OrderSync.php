<?php


namespace white\commerce\sendcloud\services;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\commerce\elements\Order;
use craft\errors\SiteNotFoundException;
use craft\events\ModelEvent;
use craft\helpers\Queue;
use Exception;
use JouwWeb\Sendcloud\Exception\SendcloudRequestException;
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
    private ?SendcloudApi $sendcloudApi = null;

    public function init(): void
    {
        parent::init();
        
        $this->sendcloudApi = SendcloudPlugin::getInstance()->sendcloudApi;
    }

    /**
     * Gets order synchronization status based on Craft order ID.
     * @param int $orderId
     * @return OrderSyncStatus|null
     */
    public function getOrderSyncStatusByOrderId(int $orderId): ?OrderSyncStatus
    {
        $record = OrderSyncStatusRecord::findOne([
            'orderId' => $orderId,
        ]);
        if (!$record instanceof \white\commerce\sendcloud\records\OrderSyncStatus) {
            return null;
        }

        return new OrderSyncStatus($record->toArray());
    }

    /**
     * Gets order synchronization status based on Sendcloud parcel ID.
     * @param int $parcelId
     * @return OrderSyncStatus|null
     */
    public function getOrderSyncStatusByParcelId(int $parcelId): ?OrderSyncStatus
    {
        $record = OrderSyncStatusRecord::findOne([
            'parcelId' => $parcelId,
        ]);
        if (!$record instanceof \white\commerce\sendcloud\records\OrderSyncStatus) {
            return null;
        }

        return new OrderSyncStatus($record->toArray());
    }

    /**
     * Gets the order synchronization status or creates a new status if couldn't find any existing one.
     * @param Order $order
     * @return OrderSyncStatus
     */
    public function getOrCreateOrderSyncStatus(Order $order): OrderSyncStatus
    {
        $model = $this->getOrderSyncStatusByOrderId($order->getId());
        if (!$model instanceof \white\commerce\sendcloud\models\OrderSyncStatus) {
            return new OrderSyncStatus(['orderId' => $order->getId()]);
        }

        return $model;
    }

    /**
     * Saves the order synchronization status.
     * @param OrderSyncStatus $model
     * @param bool $runValidation
     * @return bool
     * @throws Exception
     */
    public function saveOrderSyncStatus(OrderSyncStatus $model, bool $runValidation = true): bool
    {
        if (isset($model->id)) {
            $record = OrderSyncStatusRecord::findOne($model->id);
            if (!$record instanceof \white\commerce\sendcloud\records\OrderSyncStatus) {
                throw new InvalidArgumentException('No order sync status exists with the ID “' . $model->id . '”');
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
        $record->statusId = $model->parcelStatus?->value;
        $record->statusMessage = $model->parcelStatus?->getMessage();
        $record->carrier = $model->carrier;
        $record->trackingNumber = $model->trackingNumber;
        $record->trackingUrl = $model->trackingUrl;
        $record->servicePoint = $model->servicePoint;
        $record->lastError = $model->lastError;
        $record->lastWebhookTimestamp = $model->lastWebhookTimestamp;

        $record->save(false);
        $model->id = $record->getAttribute('id');
        $model->dateCreated = new \DateTime($record->dateCreated);

        return true;
    }

    /**
     * Deletes order status.
     * @param int $id
     * @return bool
     */
    public function deleteOrderSyncStatusById(int $id): bool
    {
        return OrderSyncStatusRecord::deleteAll(['id' => $id]) > 0;
    }

    /**
     * Registers Craft event listeners required for order synchronization.
     * @return void
     */
    public function registerEventListeners(): void
    {
        Event::on(
            Order::class,
            Element::EVENT_AFTER_SAVE,
            function(ModelEvent $event): void {
                if ($event->sender->propagating) {
                    return;
                }

                try {
                    $this->syncOrder($event->sender);
                } catch (Exception $exception) {
                    SendcloudPlugin::error("Could not synchronize an order with Sendcloud.", $exception);
                }
            }
        );

        // Check if the chosen method shipping method should have servicePoint info
        Event::on(
            Order::class,
            Order::EVENT_BEFORE_COMPLETE_ORDER,
            function(Event $event): void {
                if ($event->sender->propagating) {
                    return;
                }

                /** @var Order $order */
                $order = $event->sender;
                $store = $order->getStore();
                $status = $this->getOrderSyncStatusByOrderId($order->getId());
                $isSendcloudShipping = false;

                if ($status && $status->servicePoint) {
                    foreach ($this->sendcloudApi->getClient()->getShippingMethods($store->id) as $method) {
                        // Find the matching sendcloud shipping
                        if ($method->getName() == $order->shippingMethodName) {
                            $isSendcloudShipping = true;
                            if (!$method->isServicePointInputRequired()) {
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
     * @param Order $order
     * @return void
     * @throws Exception
     */
    public function syncOrder(Order $order): void
    {
        if (!$order->isCompleted) {
            return;
        }
        
        $orderStatus = $order->getOrderStatus();
        if (!$orderStatus instanceof \craft\commerce\models\OrderStatus) {
            return;
        }

        $settings = SendcloudPlugin::getInstance()->getSettings();
        $statusMapping = SendcloudPlugin::getInstance()->statusMapping->getStatusMappingByStoreId($order->getStore()->id);
        
        if (!in_array($orderStatus->handle, $statusMapping->orderStatusesToPush, true) && !in_array($orderStatus->handle, $statusMapping->orderStatusesToCreateLabel, true)) {
            return;
        }
        
        if (!$this->validateOrder($order)) {
            return;
        }
        
        $createLabel = in_array($orderStatus->handle, $statusMapping->orderStatusesToCreateLabel, true);

        $job = new PushOrder([
            'orderId' => $order->getId(),
            'createLabel' => $createLabel,
        ]);

        Queue::push($job, $settings->pushOrderJobPriority);
    }

    /**
     * Pushes the order to Sendcloud that hasn't been pushed yet.
     * @param Order $order
     * @param bool $force
     * @return bool
     * @throws SiteNotFoundException
     */
    public function pushOrder(Order $order, bool $force = false): bool
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

            $store = $order->getStore();
            $client = $this->sendcloudApi->getClient($store->id);

            $parcel = null;
            if ($status->isPushed()) {
                try {
                    $parcel = $client->updateParcel($status, $order);
                } catch (SendcloudRequestException $sendcloudRequestException) {
                    if ($sendcloudRequestException->getSendCloudCode() != 404) {
                        throw $sendcloudRequestException;
                    }
                }
            }
            
            if ($parcel === null) {
                $parcel = $client->createParcel($order, $status->getServicePointId());
            }
    
            $status->fillFromParcel($parcel);
            $status->lastError = null;
            if (!$this->saveOrderSyncStatus($status)) {
                throw new \RuntimeException("Could not save order sync status: " . VarDumper::dumpAsString($status->getErrors()));
            }
        } catch (Exception $exception) {
            $status->lastError = $exception instanceof SendCloudRequestException ? $exception->getSendCloudMessage() : $exception->getMessage();
            $this->saveOrderSyncStatus($status);

            return false;
        } finally {
            $mutex->release($lockName);
        }

        return true;
    }

    /**
     * @param Order $order
     * @return bool
     * @throws SiteNotFoundException
     */
    public function createLabel(Order $order): bool
    {
        $lockName = 'sendcloud:createLabel:' . $order->getId();
        $mutex = Craft::$app->getMutex();
        if (!$mutex->acquire($lockName, 5)) {
            return false;
        }
        $status = $this->getOrCreateOrderSyncStatus($order);

        try {
            $store = $order->getStore();
            $client = $this->sendcloudApi->getClient($store->id);
            if (!$status->isPushed() || $status->isLabelCreated()) {
                return false;
            }

            $parcel = $client->createLabel($order, $status->parcelId);

            $status->fillFromParcel($parcel);
            $status->lastError = null;
            if (!$this->saveOrderSyncStatus($status)) {
                throw new \RuntimeException("Could not save order sync status: " . VarDumper::dumpAsString($status->getErrors()));
            }
        } catch (Exception $exception) {
            $status->lastError = $exception instanceof SendCloudRequestException ? $exception->getSendCloudMessage() : $exception->getMessage();
            $this->saveOrderSyncStatus($status);
            
            return false;
        } finally {
            $mutex->release($lockName);
        }

        return true;
    }

    /**
     * @param Order $order
     * @return bool
     * @throws SiteNotFoundException
     */
    protected function validateOrder(Order $order): bool
    {
        if ($order->getShippingAddress() === null) {
            SendcloudPlugin::getInstance()->log("Shipping address not found", Logger::LEVEL_WARNING);
            return false;
        }

        if ($order->getShippingMethod() === null) {
            SendcloudPlugin::getInstance()->log("Order shipping method not found", Logger::LEVEL_WARNING);
            return false;
        }

        $store = $order->getStore();
        $client = $this->sendcloudApi->getClient($store->id);
        if (!isset($client->getShippingMethods($store->id)[$order->getShippingMethod()->getName()])) {
            SendcloudPlugin::getInstance()->log("Sendcloud shipping method not found", Logger::LEVEL_WARNING);
            return false;
        }
        
        return true;
    }
}
