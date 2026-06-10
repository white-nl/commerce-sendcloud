<?php


namespace white\commerce\sendcloud\controllers\cp;

use Craft;
use craft\commerce\Plugin as CommercePlugin;
use craft\errors\MissingComponentException;
use craft\helpers\Queue;
use craft\web\Controller;
use white\commerce\sendcloud\models\Integration;
use white\commerce\sendcloud\models\OrderSyncStatus;
use white\commerce\sendcloud\queue\jobs\PushOrder;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class OrderController extends Controller
{
    /**
     * @return \yii\web\Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws MissingComponentException
     * @throws NotFoundHttpException
     */
    public function actionPush(): Response
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
            SendcloudPlugin::getInstance()->error("Could not push the order to Sendcloud.", $e);
        }

        if ($success) {
            Craft::$app->getSession()->setNotice(Craft::t('commerce-sendcloud', "The order has been successfully pushed to Sendcloud."));
        } else {
            Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', "Could not push the order to Sendcloud. Please check the error logs for more details."));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * @return \yii\web\Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws MissingComponentException
     */
    public function actionBulkPush()
    {
        $this->requirePermission('commerce-sendcloud-pushOrders');

        $orderIds = Craft::$app->getRequest()->getParam('orderIds');

        foreach ($orderIds as $orderId) {
            $order = CommercePlugin::getInstance()->getOrders()->getOrderById($orderId);
            if (!$order || !$order->isCompleted) {
                continue;
            }

            $job = new PushOrder([
                'orderId' => $order->getId(),
                'force' => true,
            ]);

            $settings = SendcloudPlugin::getInstance()->getSettings();
            Queue::push($job, $settings->pushOrderJobPriority);
        }

        Craft::$app->getSession()->setNotice(Craft::t('commerce-sendcloud', "Trying to push {count} orders to Sendcloud.", ['count' => count($orderIds)]));

        return $this->redirectToPostedUrl();
    }

    public function actionPrintLabel(): \yii\web\Response
    {
        $this->requirePermission('commerce-sendcloud-printLabels');

        $orderId = Craft::$app->getRequest()->getRequiredBodyParam('orderId');

        $order = CommercePlugin::getInstance()->getOrders()->getOrderById($orderId);
        if (!$order || !$order->isCompleted) {
            throw new NotFoundHttpException();
        }

        $status = SendcloudPlugin::getInstance()->orderSync->getOrderSyncStatusByOrderId($orderId);
        if (!$status) {
            Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', "Order isn't pushed to sendcloud. Please push the order before trying to print the label."));
            return $this->redirectToPostedUrl();
        }

        if (!$status->isLabelCreated()) {
            if (!SendcloudPlugin::getInstance()->orderSync->createLabel($order)) {
                Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', "Could not get Sendcloud label. Please check the error logs for more details."));
                return $this->redirectToPostedUrl();
            }
            $status = SendcloudPlugin::getInstance()->orderSync->getOrderSyncStatusByOrderId($orderId);
        }
        $label = SendcloudPlugin::getInstance()->orderSync->getLabel($status);
        return Craft::$app->getResponse()->sendContentAsFile(
            $label,
            "sendcloud-label-$order->reference.pdf",
            ['inline' => true, 'mimeType' => 'application/pdf']
        );
    }

    public function actionBulkPrintLabels()
    {
        $this->requirePermission('commerce-sendcloud-printLabels');

        $plugin = SendcloudPlugin::getInstance();
        $orderIds = Craft::$app->getRequest()->getParam('orderIds');
        $storeId = null;
        $parcelIds = [];
        $statusesByOrderNumber = [];

        foreach ($orderIds as $orderId) {
            $status = $plugin->orderSync->getOrderSyncStatusByOrderId($orderId);
            if ($status) {
                $order = $status->getOrder();
                if ($order === null) {
                    continue;
                }
                $storeId ??= $order->storeId;

                if (!$status->isLabelCreated()) {
                    $statusesByOrderNumber[$order->id] = $status;
                    continue;
                }

                $parcelIds[] = $status->parcelId;
            }
        }

        if ($storeId === null) {
            Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', "Could not get Sendcloud label. Please check the error logs for more details."));
            return $this->redirectToPostedUrl();
        }

        try {
            if (!empty($statusesByOrderNumber)) {
                $integration = $plugin->integrations->getIntegrationByStoreId($storeId);
                if (!$integration instanceof Integration) {
                    throw new \RuntimeException(sprintf('Integration not found for store #%s.', $storeId));
                }

                $client = $plugin->sendcloudApi->getClient($storeId);
                foreach (array_chunk(array_keys($statusesByOrderNumber), 20) as $orderNumberChunk) {
                    $response = $client->createLabels(
                        $orderNumberChunk,
                        $integration->externalId,
                        $plugin->getSettings()->isApplyShippingRules(),
                    );
                    foreach ($response['data'] as $labelData) {
                        $orderNumber = (string)($labelData['order_number'] ?? '');
                        if ($orderNumber === '' || !isset($statusesByOrderNumber[$orderNumber])) {
                            continue;
                        }

                        $status = $statusesByOrderNumber[$orderNumber];
                        $newParcelIds = $labelData['parcels_ids'] ?? [];
                        if (empty($newParcelIds) && isset($labelData['parcel_id'])) {
                            $newParcelIds = [(int)$labelData['parcel_id']];
                        }
                        if (empty($newParcelIds)) {
                            continue;
                        }

                        /** @var OrderSyncStatus $status */
                        $status->parcelId = (int)$newParcelIds[0];
                        $status->lastError = null;
                        $plugin->orderSync->saveOrderSyncStatus($status);
                        array_push($parcelIds, ...$newParcelIds);
                    }
                }
            }

            $parcelIds = array_values(array_unique(array_map('intval', $parcelIds)));
            if (empty($parcelIds)) {
                Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', "Could not get Sendcloud label. Please check the error logs for more details."));
                return $this->redirectToPostedUrl();
            }

            $client = $plugin->sendcloudApi->getClient($storeId);
            $labels = $client->getLabelsPdf($parcelIds);
        } catch (\Exception $e) {
            $plugin->error("Could not print Sendcloud labels.", $e);
            Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', "Could not get Sendcloud label. Please check the error logs for more details."));

            return $this->redirectToPostedUrl();
        }

        return Craft::$app->getResponse()->sendContentAsFile(
            $labels,
            'labels.pdf',
            ['inline' => true, 'mimeType' => 'application/pdf']
        );
    }
}
