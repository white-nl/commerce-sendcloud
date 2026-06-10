<?php


namespace white\commerce\sendcloud\services;

use CommerceGuys\Addressing\Country\CountryRepository;
use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\commerce\base\Purchasable;
use craft\commerce\base\PurchasableInterface;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\commerce\models\OrderStatus;
use craft\commerce\Plugin as Commerce;
use craft\elements\Address;
use craft\errors\SiteNotFoundException;
use craft\events\ModelEvent;
use craft\helpers\Queue;
use Exception;
use white\commerce\sendcloud\events\AddressEvent;
use white\commerce\sendcloud\events\OrderDetailsEvent;
use white\commerce\sendcloud\events\ValidateOrderEvent;
use white\commerce\sendcloud\exception\SendcloudRequestException;
use white\commerce\sendcloud\exception\SendcloudStateException;
use white\commerce\sendcloud\models\Order as SendcloudOrder;
use white\commerce\sendcloud\models\OrderDetails;
use white\commerce\sendcloud\models\OrderSyncStatus;
use white\commerce\sendcloud\models\Price;
use white\commerce\sendcloud\queue\jobs\PushOrder;
use white\commerce\sendcloud\records\OrderSyncStatus as OrderSyncStatusRecord;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\base\Event;
use yii\base\InvalidArgumentException;
use yii\helpers\VarDumper;
use yii\log\Logger;

class OrderSync extends Component
{
    public const EVENT_CREATE_ORDER_DETAILS = 'createOrderDetails';

    /**
     * @var string Event emitted before the Sendcloud address is created
     */
    public const EVENT_AFTER_CREATE_ADDRESS = 'afterCreateAddress';

    public const EVENT_AFTER_VALIDATE_ORDER = 'afterValidateOrder';

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
        if (!$record instanceof OrderSyncStatusRecord) {
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
        if (!$record instanceof OrderSyncStatusRecord) {
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
        if (!$model instanceof OrderSyncStatus) {
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
            if (!$record instanceof OrderSyncStatusRecord) {
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
                    /** @var Order $order */
                    $order = $event->sender;
                    $this->syncOrder($order);
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
                    foreach ($this->sendcloudApi->getClient()->getShippingOptions($store) as $method) {
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
        if (!$orderStatus instanceof OrderStatus) {
            return;
        }

        $settings = SendcloudPlugin::getInstance()->getSettings();
        $statusMapping = SendcloudPlugin::getInstance()->statusMapping->getStatusMappingByStoreId($order->getStore()->id);

        if (!in_array($orderStatus->handle, $statusMapping->orderStatusesToPush, true) && !in_array($orderStatus->handle, $statusMapping->orderStatusesToCreateLabel, true)) {
            return;
        }

        $isOrderValid = $this->validateOrder($order);

        $validateOrderEvent = new ValidateOrderEvent([
            'order' => $order,
            'isValid' => $isOrderValid,
        ]);
        if ($this->hasEventHandlers(self::EVENT_AFTER_VALIDATE_ORDER)) {
            $this->trigger(self::EVENT_AFTER_VALIDATE_ORDER, $validateOrderEvent);
        }

        if (!$isOrderValid) {
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

            $orderData = $this->_createOrderData($order, $status->getServicePointId());
            $client->pushOrder($orderData);

            $status->lastError = null;
            if (!$this->saveOrderSyncStatus($status)) {
                throw new \RuntimeException("Could not save order sync status: " . VarDumper::dumpAsString($status->getErrors()));
            }
        } catch (Exception $exception) {
            $status->lastError = $exception instanceof SendcloudRequestException ? $exception->getSendCloudMessage() : $exception->getMessage();
            $this->saveOrderSyncStatus($status);

            return false;
        } finally {
            $mutex->release($lockName);
        }

        return true;
    }

    public function getLabel(OrderSyncStatus $status): string
    {
        $client = $this->sendcloudApi->getClient($status->getOrder()->getStore()->id);
        return $client->getLabelPdf($status);
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
        /** @var OrderSyncStatus|null $status */
        $status = $this->getOrderSyncStatusByOrderId($order->getId());

        try {
            $store = $order->getStore();
            $client = $this->sendcloudApi->getClient($store->id);
            if (!$status || $status->isLabelCreated()) {
                return false;
            }

            $response = $client->createLabel($order);

            $status->parcelId = $response['parcel_id'];
            if (array_key_exists('tracking_number', $response)) {
                $status->trackingNumber = $response['tracking_number'];
                $status->trackingUrl = $response['tracking_url'];
            }
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

    private function _createOrderData(Order $order, ?string $servicePointId = null): SendcloudOrder
    {
        $store = $order->getStore();
        $integration = SendcloudPlugin::getInstance()->integrations->getIntegrationByStoreId($store->id);
        if ($integration === null) {
            throw new SendcloudStateException(Craft::t('commerce-sendcloud', "No integration found for store: $store->id"));
        }
        $settings = SendcloudPlugin::getInstance()->getSettings();

        $orderItemService = SendcloudPlugin::getInstance()->orderItems;
        $orderItems = [];
        foreach ($order->getLineItems() as $lineItem) {
            /** @var Purchasable $purchasable */
            $purchasable = $lineItem->getPurchasable();

            if ($settings->useInventoryItemCodes) {
                $inventoryItem = Commerce::getInstance()->getInventory()->getInventoryItemByPurchasable($purchasable);
                $hsSystemCode = $inventoryItem->harmonizedSystemCode;
                $originCountryCode = $inventoryItem->countryCodeOfOrigin;
            } else {
                if ($settings->hsCodeFieldHandle) {
                    $hsSystemCode = $this->_tryGetProductField($purchasable, $settings->hsCodeFieldHandle);
                }
                if ($settings->originCountryFieldHandle) {
                    $originCountryCode = $this->_tryGetProductField($purchasable, $settings->originCountryFieldHandle);
                }
            }

            $params = [
                'hsCode' => $hsSystemCode ?? null,
                'originCountry' => $originCountryCode ?? null,
            ];
            $orderItems[] = $orderItemService->createFromLineItem($lineItem, $params);
        }

        $orderStatus = $order->getOrderStatus();
        $orderDetails = Craft::createObject(OrderDetails::class);
        $orderDetails->setIntegrationId($integration->externalId);
        $orderDetails->setStatus([
            'code' => $orderStatus->handle,
            'message' => $orderStatus->description,
        ]);
        $orderDetails->setCreatedAt($order->dateOrdered);
        $orderDetails->setUpdatedAt($order->dateUpdated);
        $orderDetails->setOrderItems($orderItems);

        if ($this->hasEventHandlers(self::EVENT_CREATE_ORDER_DETAILS)) {
            $this->trigger(self::EVENT_CREATE_ORDER_DETAILS, new OrderDetailsEvent([
                'orderDetails' => $orderDetails,
                'order' => $order,
            ]));
        }

        $statusMapping = SendcloudPlugin::getInstance()->statusMapping->getStatusMappingByStoreId($store->id);
        $orderNumberTemplate = $statusMapping->orderNumberFormat;

        try {
            $vars = ['order' => $order];
            $orderNumber = Craft::$app->getView()->renderString($orderNumberTemplate, $vars);
        } catch (\Throwable $exception) {
            Craft::error('Unable to generate Sendcloud order reference for Order ID: ' . $order->getId() . ', with format: ' . $orderNumberTemplate . ', error: ' . $exception->getMessage());
            throw $exception;
        }

        $totalPrice = new Price($order->getTotalPrice(), $order->getPaymentCurrency());

        $sendcloudOrder = Craft::createObject(SendcloudOrder::class);
        $sendcloudOrder->setOrderId($order->number);
        $sendcloudOrder->setOrderNumber($orderNumber);
        $sendcloudOrder->setOrderDetails($orderDetails);
        $sendcloudOrder->setPaymentDetails([
            'total_price' => $totalPrice->toArray(),
            'status' => [
                'code' => $order->getPaidStatus(),
            ],
        ]);

        $shippingAddress = $this->_createAddress($order->getShippingAddress(), $order->getEmail());
        $sendcloudOrder->setShippingAddress($shippingAddress);
        if ($order->getBillingAddress()) {
            $billingAddress = $this->_createAddress($order->getBillingAddress(), $order->getEmail());
            $sendcloudOrder->setBillingAddress($billingAddress);
        }

        $shippingDetails['delivery_indicator'] = $order->shippingMethodHandle;
        $totalWeight = $order->getTotalWeight();
        if ($totalWeight > 0) {
            $shippingDetails = [
                'measurement' => [
                    'weight' => [
                        'value' => $totalWeight,
                        'unit' => Commerce::getInstance()->getSettings()->weightUnits,
                    ],
                ],
            ];
        }

        $sendcloudShippingOption = $this->sendcloudApi->getClient($store->id)->getShippingOptions($store)[$order->shippingMethodName] ?? null;
        if ($sendcloudShippingOption) {
            $shippingDetails['ship_with'] = [
                'type' => 'shipping_option_code',
                'properties' => [
                    'shipping_option_code' => $sendcloudShippingOption->getCode(),
                ],
            ];
            if ($sendcloudShippingOption->isServicePointInputRequired()) {
                $sendcloudOrder->setServicePointDetails([
                    'id' => $servicePointId,
                ]);
            }
        }
        $sendcloudOrder->setShippingDetails($shippingDetails);

        return $sendcloudOrder;
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

        if ($order->shippingMethodHandle === null) {
            SendcloudPlugin::getInstance()->log("Order shipping method not found", Logger::LEVEL_WARNING);
            return false;
        }

        $store = $order->getStore();
        $client = $this->sendcloudApi->getClient($store->id);
        $settings = SendcloudPlugin::getInstance()->getSettings();
        if ($settings->isSkipUnmappedShippingMethods() && !isset($client->getShippingOptions($store)[$order->shippingMethodName])) {
            SendcloudPlugin::getInstance()->log("Sendcloud shipping method not found", Logger::LEVEL_WARNING);
            return false;
        }

        return true;
    }

    private function _createAddress(Address $address, string $email): \white\commerce\sendcloud\models\Address
    {
        $settings = SendcloudPlugin::getInstance()->getSettings();
        if ($settings->phoneNumberFieldHandle) {
            $phoneNumber = $address->getFieldValue($settings->phoneNumberFieldHandle);
        }

        $locality = $address->getLocality();
        $countryCode = $address->getCountryCode();
        if ($locality === null) {
            $countryRepository = new CountryRepository();
            $country = $countryRepository->get($countryCode);
            $locality = $country->getName();
        }
        $sendcloudAddress = new \white\commerce\sendcloud\models\Address(
            name: $address->fullName ?: $address->getGivenName() . ' ' . $address->getFamilyName(),
            addressLine1: $address->getAddressLine1(),
            postalCode: $address->getPostalCode(),
            city: $locality,
            countryCode: $countryCode,
            companyName: $address->getOrganization(),
            houseNumber: null,
            addressLine2: $address->getAddressLine2(),
            poBox: null,
            stateProvinceCode: $address->getAdministrativeArea(),
            email: $email,
            phoneNumber: $phoneNumber ?? null,
        );

        $addressEvent = new AddressEvent([
            'sendcloudAddress' => $sendcloudAddress,
            'craftAddress' => $address,
        ]);

        if ($this->hasEventHandlers(self::EVENT_AFTER_CREATE_ADDRESS)) {
            $this->trigger(self::EVENT_AFTER_CREATE_ADDRESS, $addressEvent);
        }

        return $sendcloudAddress;
    }

    private function _tryGetProductField(PurchasableInterface $purchasable, string $fieldHandle): ?string
    {
        if ($purchasable instanceof Element) {
            if ($purchasable->getFieldLayout()->isFieldIncluded($fieldHandle)) {
                return $purchasable->getFieldValue($fieldHandle);
            }

            if ($purchasable instanceof Variant) {
                $product = $purchasable->getOwner();
                if ($product?->getFieldLayout()->isFieldIncluded($fieldHandle)) {
                    return $product->getFieldValue($fieldHandle);
                }
            }
        }

        return null;
    }
}
