<?php


namespace white\commerce\sendcloud\controllers;


use craft\web\Controller;
use white\commerce\sendcloud\models\OrderSyncStatus;
use white\commerce\sendcloud\SendcloudPlugin;
use white\commerce\sendcloud\client\WebhookParcelNormalizer;
use yii\helpers\VarDumper;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;

class WebhookController extends Controller
{
    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;
    public $enableCsrfValidation = false;
    
    public function init()
    {
        parent::init();
    }

    public function actionHandle($id, $token)
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
                            throw new \Exception("Couldn't save the integration.");
                        }
                    }
                }
                break;
            case 'integration_connected':
            case 'integration_updated':
                {
                    if (empty($integration->system)) {
                        $integration->externalId = $request->getBodyParam('integration.id');
                        $integration->system = $request->getBodyParam('integration.system');
                        $integration->shopUrl = $request->getBodyParam('integration.shop_url');
                        $integration->webhookUrl = $request->getBodyParam('integration.webhook_url');
                    }
                    $integration->servicePointEnabled = (bool)$request->getBodyParam('integration.service_point_enabled', false);
                    $integration->servicePointCarriers = $request->getBodyParam('integration.service_point_carriers', []);
                    if (!$integrationService->saveIntegration($integration)) {
                        throw new \Exception("Couldn't save the integration.");
                    }
                }
                break;
            case 'integration_deleted':
                {
                    if (!$integrationService->deleteIntegrationById($integration->id)) {
                        throw new \Exception("Couldn't delete the integration.");
                    }
                }
                break;
            case 'parcel_status_changed':
                {
                    $parcelData = $request->getBodyParam('parcel');
                    if (empty($parcelData) || !empty($parcelData['is_return'])) {
                        SendcloudPlugin::log("Not a status change or is return: skipped");
                        return;
                    }
                    $parcel = (new WebhookParcelNormalizer($parcelData))->getParcel();

//                    $client = SendcloudPlugin::getInstance()->sendcloudApi->getClient();
//                    $parcel = $client->getParcel($parcel->getId());
//                    if (!$parcel) {
//                        SendcloudPlugin::log("Parcel #{$parcel->getId()} not found via the Sendcloud API.");
//                        return;
//                    }
                    
                    $status = SendcloudPlugin::getInstance()->orderSync->getOrderSyncStatusByParcelId($parcel->getId());
                    if (!$status) {
                        SendcloudPlugin::log("Parcel #{$parcel->getId()} not found. Trying to find by order #{$parcel->getOrderNumber()}");
                        $status = SendcloudPlugin::getInstance()->orderSync->getOrderSyncStatusByOrderId($parcel->getOrderNumber());
                        if (!$status) {
                            SendcloudPlugin::log("Order status change skipped: parcel #{$parcel->getId()} not found.");
                            return;
                        }
                    }
                    
                    $status->fillFromParcel($parcel);
                    if (!SendcloudPlugin::getInstance()->orderSync->saveOrderSyncStatus($status)) {
                        throw new \Exception("Could not save order sync status: " . VarDumper::dumpAsString($status->errors));
                    }
                    
                    $settings = SendcloudPlugin::getInstance()->getSettings();
                    if ($settings->canChangeOrderStatus()) {
                        foreach ($settings->orderStatusMapping as $mapping) {
                            if ($mapping['sendcloud'] == $parcel->getStatusId()){
                                $order = $status->getOrder();
                                if ($order) {
                                    $order->orderStatusId = $mapping['craft'];
                                    $order->message = \Craft::t('commerce-sendcloud',"[Sendcloud] Status updated via webhook ({statusId}: {statusMessage})",['statusId' => $status->statusId, 'statusMessage' => $status->statusMessage]);
                                    if (!\Craft::$app->getElements()->saveElement($order)) {
                                        SendcloudPlugin::error("Could not save Sendcloud order sync status.\n  " . VarDumper::dumpAsString($order->errors));
                                    }
                                }
                            }
                        }
                    }

                    if ($status->statusId == OrderSyncStatus::STATUS_CANCELLED) {
                        SendcloudPlugin::getInstance()->orderSync->deleteOrderSyncStatusById($status->id);
                        SendcloudPlugin::log("Order status for order#{$status->orderId} has been deleted because status #{$status->statusId} received from Sendcloud.");
                    }
                }
                break;
            default:
                return;
        }
    }
}
