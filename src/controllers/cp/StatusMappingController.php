<?php

namespace white\commerce\sendcloud\controllers\cp;

use craft\commerce\base\HasStoreInterface;
use craft\commerce\Plugin;
use craft\commerce\Plugin as Commerce;
use craft\commerce\web\assets\commercecp\CommerceCpAsset;
use craft\helpers\Cp;
use craft\models\Site;
use craft\web\Controller;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\web\HttpException;
use yii\web\Response;

class StatusMappingController extends Controller
{
    public function init(): void
    {
        parent::init();

        $this->getView()->registerAssetBundle(CommerceCpAsset::class);
    }

    public function actionIndex(): Response
    {
        /** @var Site|HasStoreInterface $site */
        $site = Cp::requestedSite();
        $store = $site->getStore();

        return $this->redirect("commerce-sendcloud/settings/{$store->handle}/status-mapping");
    }

    public function actionStatusMapping(string $storeHandle): Response
    {
        $variables = compact('storeHandle');

        if ($variables['storeHandle']) {
            $store = Commerce::getInstance()->getStores()->getStoreByHandle($storeHandle);
            $variables['store'] = $store;

            if (!$variables['store']) {
                throw new HttpException(404);
            }
        } else {
            /** @var Site|HasStoreInterface $site */
            $site = Cp::requestedSite();
            $store = $site->getStore();
            $variables['store'] = $store;
            $variables['storeHandle'] = $variables['store']->handle;
        }

        $storeOrderStatuses = Commerce::getInstance()->getOrderStatuses()->getAllOrderStatuses($store->id);
        $orderStatuses = [];
        foreach ($storeOrderStatuses as $storeOrderStatus) {
            $orderStatuses[] = ['value' => $storeOrderStatus->handle, 'label' => $storeOrderStatus->name];
        }

        $variables['orderStatuses'] = $orderStatuses;
        $statusMapping = SendcloudPlugin::getInstance()->statusMapping->getStatusMappingByStoreId($store->id);
        $variables['statusMapping'] = $statusMapping;

        return $this->renderTemplate('commerce-sendcloud/settings/status-mapping', $variables);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $params = $this->request->getBodyParams();

        $storeId = $params['storeId'];
        $store = Commerce::getInstance()->getStores()->getStoreById($storeId);

        $statusMapping = SendcloudPlugin::getInstance()->statusMapping->getStatusMappingByStoreId($store->id);
        $statusMapping->orderStatusesToPush = !empty($params['orderStatusesToPush']) ? $params['orderStatusesToPush'] : [];
        $statusMapping->orderStatusesToCreateLabel = !empty($params['orderStatusesToCreateLabel']) ? $params['orderStatusesToCreateLabel'] : [];
        $statusMapping->orderStatusMapping = !empty($params['orderStatusMapping']) ? $params['orderStatusMapping'] : [];
        $statusMapping->orderNumberFormat = $params['orderNumberFormat'] ?? $statusMapping->orderNumberFormat;

        if (!$statusMapping->validate()) {
            $this->setFailFlash(\Craft::t('commerce-sendcloud', 'Couldn`t save settings.'));
            return $this->redirectToPostedUrl();
        }

        SendcloudPlugin::getInstance()->statusMapping->saveStatusMapping($statusMapping);
        return $this->redirectToPostedUrl();
    }
}