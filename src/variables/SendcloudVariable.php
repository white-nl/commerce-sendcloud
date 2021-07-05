<?php


namespace white\commerce\sendcloud\variables;


use craft\base\Component;
use craft\commerce\elements\Order;
use craft\helpers\ArrayHelper;
use white\commerce\sendcloud\models\OrderSyncStatus;
use white\commerce\sendcloud\SendcloudPlugin;
use white\commerce\sendcloud\services\Integrations;
use white\commerce\sendcloud\services\OrderSync;
use white\commerce\sendcloud\services\SendcloudApi;

class SendcloudVariable extends Component
{
    /**
     * @var OrderSync
     */
    private $orderSync;
    
    /**
     * @var SendcloudApi
     */
    private $sendcloudApi;
    
    /**
     * @var Integrations
     */
    private $integrations;

    public function init()
    {
        parent::init();
        
        $this->orderSync = SendcloudPlugin::getInstance()->orderSync;
        $this->sendcloudApi = SendcloudPlugin::getInstance()->sendcloudApi;
        $this->integrations = SendcloudPlugin::getInstance()->integrations;
    }

    /**
     * Gets the public key for Sendcloud API.
     * 
     * @return string|null
     * @throws \craft\errors\SiteNotFoundException
     */
    public function getIntegrationPublicKey()
    {
        $integration = $this->integrations->getIntegrationBySiteId(\Craft::$app->getSites()->getPrimarySite()->id);
        if (!$integration) {
            return null;
        }
        
        return $integration->publicKey;
    }

    /**
     * Gets all available Sendcloud shipping methods.
     * 
     * @return array|\JouwWeb\SendCloud\Model\ShippingMethod[]
     * @throws \JouwWeb\SendCloud\Exception\SendCloudClientException
     */
    public function getShippingMethods()
    {
        return $this->sendcloudApi->getClient()->getShippingMethods();
    }

    /**
     * Gets order synchronization status for given order or a cart.
     * 
     * @param Order $order
     * @return OrderSyncStatus|null
     */
    public function getOrderSyncStatus(Order $order)
    {
        return $this->orderSync->getOrderSyncStatusByOrderId($order->id);
    }

    /**
     * Gets service point chosen for the given order or a cart.
     *
     * @param Order $order
     * @param string|null $carrier
     * @return array|null
     */
    public function getServicePoint(Order $order, $carrier = null): ?array
    {
        $status = $this->getOrderSyncStatus($order);
        if (!$status) {
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
    public function getTrackingNumber(Order $order)
    {
        $status = $this->getOrderSyncStatus($order);
        if (!$status) {
            return null;
        }

        return $status->trackingNumber;
    }

    /**
     * Gets the order tracking url (if available).
     *
     * @param Order $order
     * @return string|null
     */
    public function getTrackingUrl(Order $order)
    {
        $status = $this->getOrderSyncStatus($order);
        if (!$status) {
            return null;
        }

        return $status->trackingUrl;
    }

    /**
     * Gets the return portal URL for the given order (if available).
     * 
     * @param Order $order
     * @return string|null
     * @throws \Exception
     */
    public function getReturnPortalUrl(Order $order)
    {
        $status = $this->getOrderSyncStatus($order);
        if (!$status || !$status->isPushed()) {
            return null;
        }
        
        return $this->sendcloudApi->getClient()->getReturnPortalUrl($status->parcelId);
    }
}
