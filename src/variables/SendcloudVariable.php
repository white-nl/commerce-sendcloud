<?php

namespace white\commerce\sendcloud\variables;

use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\models\Store;
use craft\commerce\Plugin;
use craft\errors\SiteNotFoundException;
use craft\helpers\ArrayHelper;
use JouwWeb\Sendcloud\Model\ShippingMethod;
use white\commerce\sendcloud\models\OrderSyncStatus;
use white\commerce\sendcloud\SendcloudPlugin;
use white\commerce\sendcloud\services\Integrations;
use white\commerce\sendcloud\services\OrderSync;
use white\commerce\sendcloud\services\SendcloudApi;

/**
 *
 * @property-read null|string $integrationPublicKey
 * @property-read array|ShippingMethod[] $shippingMethods
 */
class SendcloudVariable extends Component
{
    private ?OrderSync $orderSync = null;
    
    private ?SendcloudApi $sendcloudApi = null;
    
    private ?Integrations $integrations = null;

    public function init(): void
    {
        parent::init();
        
        $this->orderSync = SendcloudPlugin::getInstance()->orderSync;
        $this->sendcloudApi = SendcloudPlugin::getInstance()->sendcloudApi;
        $this->integrations = SendcloudPlugin::getInstance()->integrations;
    }

    /**
     * Gets the public key for Sendcloud API.
     * @return string|null
     * @throws SiteNotFoundException
     */
    public function getIntegrationPublicKey(?Store $store = null): ?string
    {
        $store = $store ?? Plugin::getInstance()->getStores()->getPrimaryStore();
        $integration = $this->integrations->getIntegrationByStoreId($store->id);
        return $integration?->publicKey;
    }

    /**
     * Gets all available Sendcloud shipping methods.
     *
     * @return array|ShippingMethod[]
     * @throws SiteNotFoundException
     */
    public function getShippingMethods(?Store $store = null): array
    {
        $store = $store ?? Plugin::getInstance()->getStores()->getPrimaryStore();
        return $this->sendcloudApi->getClient()->getShippingMethods($store->id);
    }

    /**
     * Gets order synchronization status for given order or a cart.
     * @param Order $order
     * @return OrderSyncStatus|null
     */
    public function getOrderSyncStatus(Order $order): ?OrderSyncStatus
    {
        return $this->orderSync->getOrderSyncStatusByOrderId($order->getId());
    }

    /**
     * Gets service point chosen for the given order or a cart.
     *
     * @param Order $order
     * @param string|null $carrier
     * @return array|null
     * @throws \Exception
     */
    public function getServicePoint(Order $order, string $carrier = null): ?array
    {
        $status = $this->getOrderSyncStatus($order);
        if (!$status instanceof OrderSyncStatus) {
            return null;
        }
        
        if ($carrier !== null && ArrayHelper::getValue($status->servicePoint, 'carrier') != $carrier) {
            return null;
        }
        
        return $status->servicePoint;
    }

    /**
     * Gets the order tracking number (if available).
     *
     * @param Order $order
     * @return string|null
     */
    public function getTrackingNumber(Order $order): ?string
    {
        return $this->getOrderSyncStatus($order)?->trackingNumber;
    }

    /**
     * Gets the order tracking url (if available).
     *
     * @param Order $order
     * @return string|null
     */
    public function getTrackingUrl(Order $order): ?string
    {
        return $this->getOrderSyncStatus($order)?->trackingUrl;
    }

    /**
     * Gets the return portal URL for the given order (if available).
     *
     * @param Order $order
     * @return string|null
     * @throws SiteNotFoundException
     */
    public function getReturnPortalUrl(Order $order): ?string
    {
        $status = $this->getOrderSyncStatus($order);
        if (!$status || !$status->isPushed()) {
            return null;
        }
        
        return $this->sendcloudApi->getClient()->getReturnPortalUrl($status->parcelId);
    }
}
