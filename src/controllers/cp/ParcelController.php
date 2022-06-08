<?php

namespace white\commerce\sendcloud\controllers\cp;

use Craft;
use craft\commerce\Plugin as CommercePlugin;
use craft\helpers\Queue;
use craft\web\Controller;
use JouwWeb\SendCloud\Exception\SendCloudRequestException;
use white\commerce\sendcloud\models\Parcel;
use white\commerce\sendcloud\queue\jobs\PushOrder;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\web\NotFoundHttpException;

class ParcelController extends Controller
{
    public function init()
    {
        parent::init();
    }

    public function actionPrintLabel()
    {
        $this->requirePermission('commerce-sendcloud-printLabels');
        
        $orderId = Craft::$app->getRequest()->getParam('orderId');
        
        $client = SendcloudPlugin::getInstance()->sendcloudApi->getClient();
        $status = SendcloudPlugin::getInstance()->orderSync->getOrderSyncStatusByOrderId($orderId);
        if (!$status || !$status->isPushed()) {
            throw new NotFoundHttpException();
        }

        try {
            if (!$status->isLabelCreated()) {
                if (!SendcloudPlugin::getInstance()->orderSync->createLabel($status->getOrder())) {
                    Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', "Could not get Sendcloud label. Please check the error logs for more details."));
                    return;
                }
            }
            
            $label = $client->getLabelPdf($status->parcelId, Parcel::LABEL_FORMAT_A6);
        } catch (\Exception $e) {
            SendcloudPlugin::error("Could not print a Sendcloud label.", $e);
            Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', "Could not get Sendcloud label. Please check the error logs for more details."));

            $status->lastError = $e instanceof SendCloudRequestException ? $e->getSendCloudMessage() : $e->getMessage();
            SendcloudPlugin::getInstance()->orderSync->saveOrderSyncStatus($status);
            
            return $this->redirectToPostedUrl();
        }

        return Craft::$app->getResponse()->sendContentAsFile(
            $label,
            'labels.pdf',
            ['inline' => true, 'mimeType' => 'application/pdf']
        );
    }

    public function actionBulkPrintLabels()
    {
        $this->requirePermission('commerce-sendcloud-printLabels');

        $orderIds = Craft::$app->getRequest()->getParam('orderIds');
        $parcelIds = [];
        foreach ($orderIds as $orderId) {
            $status = SendcloudPlugin::getInstance()->orderSync->getOrderSyncStatusByOrderId($orderId);
            if ($status && $status->isPushed()) {
                if (!$status->isLabelCreated()) {
                    if (!SendcloudPlugin::getInstance()->orderSync->createLabel($status->getOrder())) {
                        continue;
                    }
                }

                $parcelIds[] = $status->parcelId;
            }
        }

        $client = SendcloudPlugin::getInstance()->sendcloudApi->getClient();

        try {
            $labels = $client->getLabelsPdf($parcelIds, Parcel::LABEL_FORMAT_A6);
        } catch (\Exception $e) {
            SendcloudPlugin::error("Could not print Sendcloud labels.", $e);
            Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', "Could not get Sendcloud label. Please check the error logs for more details."));

            return $this->redirectToPostedUrl();
        }

        return Craft::$app->getResponse()->sendContentAsFile(
            $labels,
            'labels.pdf',
            ['inline' => true, 'mimeType' => 'application/pdf']
        );
    }

    public function actionPush()
    {
        $this->requirePermission('commerce-sendcloud-pushOrders');

        $orderId = Craft::$app->getRequest()->getParam('orderId');
        
        $order = CommercePlugin::getInstance()->getOrders()->getOrderById($orderId);
        if (!$order || !$order->isCompleted) {
            throw new NotFoundHttpException();
        }

        $success = false;
        try {
            $success = SendcloudPlugin::getInstance()->orderSync->pushOrder($order, true);
        } catch (\Exception $e) {
            SendcloudPlugin::error("Could not push the order to Sendcloud.", $e);
        }
        
        if ($success) {
            Craft::$app->getSession()->setNotice(Craft::t('commerce-sendcloud', "The order has been successfully pushed to Sendcloud."));
        } else {
            Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', "Could not push the order to Sendcloud. Please check the error logs for more details."));
        }

        return $this->redirectToPostedUrl();
    }

    public function actionBulkPush()
    {
        $this->requirePermission('commerce-sendcloud-pushOrders');

        $orderIds = Craft::$app->getRequest()->getParam('orderIds');

        foreach ($orderIds as $orderId) {
            $order = CommercePlugin::getInstance()->getOrders()->getOrderById($orderId);
            if (!$order || !$order->isCompleted) {
                continue;
            }

            Queue::push(new PushOrder([
                'orderId' => $orderId,
                'force' => true
            ]));
        }
        
        Craft::$app->getSession()->setNotice(Craft::t('commerce-sendcloud', "Trying to push {count} orders to Sendcloud.", ['count' => count($orderIds)]));

        return $this->redirectToPostedUrl();
    }
}
