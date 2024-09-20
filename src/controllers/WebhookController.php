<?php


namespace white\commerce\sendcloud\controllers;

use craft\commerce\Plugin as CommercePlugin;
use craft\errors\ElementNotFoundException;
use craft\web\Controller;
use white\commerce\sendcloud\client\WebhookParcelNormalizer;
use white\commerce\sendcloud\enums\ParcelStatus;
use white\commerce\sendcloud\models\OrderSyncStatus;
use white\commerce\sendcloud\models\Parcel;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\StaleObjectException;
use yii\helpers\VarDumper;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;

class WebhookController extends Controller
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;
    public $enableCsrfValidation = false;
    
    public function init(): void
    {
        parent::init();
    }

    /**
     * @param int $id
     * @param string $token
     * @return void
     * @throws MethodNotAllowedHttpException
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws StaleObjectException
     */
    public function actionHandle(int $id, string $token): void
    {
        $request = \Craft::$app->getRequest();
        if (!$request->getIsPost()) {
            throw new MethodNotAllowedHttpException();
        }
        
        SendcloudPlugin::log(VarDumper::dumpAsString($request->getBodyParams()));

        $integrationService = SendcloudPlugin::getInstance()->integrations;
        
        $integration = $integrationService->getIntegrationById($id);
        if (!$integration || $integration->token != $token) {
            throw new NotFoundHttpException('Integration not found.');
        }

        $storeId = $integration->storeId;

        switch ($request->getBodyParam('action')) {
            case 'integration_credentials':
                {
                    if (empty($integration->publicKey)) {
                        $integration->publicKey = $request->getBodyParam('public_key');
                        $integration->secretKey = $request->getBodyParam('secret_key');
                        if (empty($integration->externalId)) {
                            $integration->externalId = $request->getBodyParam('integration_id');
                        }
                        
                        if (!$integrationService->saveIntegration($integration)) {
                            throw new \RuntimeException("Couldn't save the integration.");
                        }
                    }
                }
                break;
            case 'integration_connected':
            case 'integration_updated':
                {
                    if (empty($integration->system)) {
                        $integration->externalId = $request->getBodyParam('integration.id');
                    }
                    $integration->system = $request->getBodyParam('integration.system');
                    $integration->shopUrl = $request->getBodyParam('integration.shop_url');
                    $integration->webhookUrl = $request->getBodyParam('integration.webhook_url');
                    $integration->servicePointEnabled = (bool)$request->getBodyParam('integration.service_point_enabled', false);
                    $integration->servicePointCarriers = $request->getBodyParam('integration.service_point_carriers', []);
                    if (!$integrationService->saveIntegration($integration)) {
                        throw new \RuntimeException("Couldn't save the integration.");
                    }
                }
                break;
            case 'integration_deleted':
                {
                    if (!$integrationService->deleteIntegrationById($integration->id)) {
                        throw new \RuntimeException("Couldn't delete the integration.");
                    }
                }
                break;
            case 'parcel_status_changed':
                {
                    $parcelData = $request->getBodyParam('parcel');
                    $timestamp = $request->getBodyParam('timestamp');
                    if (empty($parcelData) || !empty($parcelData['is_return'])) {
                        SendcloudPlugin::getInstance()->log("Not a status change or is return: skipped");
                        return;
                    }
                    $parcel = Parcel::fromData($parcelData);

                    $mutex = \Craft::$app->getMutex();
                    $lockName = 'sendcloud:orderWebhook:' . $parcel->getOrderNumber();
                    if (!$mutex->acquire($lockName, 5)) {
                        throw new \RuntimeException("Unable to acquire a lock for Sendcloud webhook: '{$lockName}'.");
                    }

                    try {
                        $status = SendcloudPlugin::getInstance()->orderSync->getOrderSyncStatusByParcelId($parcel->getId());
                        if (!$status) {
                            SendcloudPlugin::getInstance()->log("Parcel #{$parcel->getId()} not found. Trying to find by order #{$parcel->getOrderNumber()}");
                            $status = SendcloudPlugin::getInstance()->orderSync->getOrderSyncStatusByOrderId((int)$parcel->getOrderNumber());
                            if (!$status) {
                                SendcloudPlugin::getInstance()->log("Order status change skipped: parcel #{$parcel->getId()} not found.");
                                return;
                            }
                        }
                        
                        if ($timestamp < $status->lastWebhookTimestamp) {
                            SendcloudPlugin::getInstance()->log("Received late webhook for parcel #{$parcel->getId()}. Ignoring.");
                            return;
                        }

                        $status->fillFromParcel($parcel);
                        $status->lastWebhookTimestamp = $timestamp;
                        if (!SendcloudPlugin::getInstance()->orderSync->saveOrderSyncStatus($status)) {
                            throw new \RuntimeException("Could not save order sync status: " . VarDumper::dumpAsString($status->errors));
                        }

                        $settings = SendcloudPlugin::getInstance()->getSettings();
                        $statusMapping = SendcloudPlugin::getInstance()->statusMapping->getStatusMappingByStoreId($storeId);
                        if ($statusMapping->canChangeOrderStatus()) {
                            foreach ($statusMapping->orderStatusMapping as $mapping) {
                                if ($mapping['sendcloud'] == $parcel->getParcelStatus()->value) {
                                    $order = $status->getOrder();
                                    if ($order) {
                                        $orderStatus = CommercePlugin::getInstance()->getOrderStatuses()->getOrderStatusByHandle($mapping['craft'], $storeId);
                                        if (!$orderStatus) {
                                            throw new \RuntimeException("Order status '{$mapping['craft']}' not found in Craft.");
                                        }

                                        $order->orderStatusId = $orderStatus->id;
                                        $order->message = \Craft::t('commerce-sendcloud',"[Sendcloud] Status updated via webhook ({statusId}: {statusMessage})",['statusId' => $status->statusId, 'statusMessage' => $status->statusMessage]);
                                        if (!\Craft::$app->getElements()->saveElement($order)) {
                                            SendcloudPlugin::getInstance()->error("Could not save Sendcloud order sync status.\n  " . VarDumper::dumpAsString($order->errors));
                                        }
                                    }
                                }
                            }
                        }

                        if ($status->parcelStatus === ParcelStatus::CANCELLED) {
                            SendcloudPlugin::getInstance()->orderSync->deleteOrderSyncStatusById($status->id);
                            SendcloudPlugin::getInstance()->log("Order status for order#{$status->orderId} has been deleted because status #{$status->statusId} received from Sendcloud.");
                        }
                    } finally {
                        $mutex->release($lockName);
                    }
                }
                break;
            default:
        }
    }
}
