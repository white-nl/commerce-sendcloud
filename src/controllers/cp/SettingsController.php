<?php


namespace white\commerce\sendcloud\controllers\cp;

use Craft;
use craft\commerce\base\HasStoreInterface;
use craft\commerce\Plugin as CommercePlugin;
use craft\commerce\web\assets\commercecp\CommerceCpAsset;
use craft\helpers\Cp;
use craft\models\Site;
use craft\web\Controller;
use white\commerce\sendcloud\enums\LabelFormat;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\web\HttpException;
use yii\web\Response;

class SettingsController extends Controller
{
    public const DEFAULT_SENDCLOUD_CONNECT_URL = 'https://panel.sendcloud.sc/shops/craft-commerce/connect/';
    
    public string $sendcloudConnectUrl = self::DEFAULT_SENDCLOUD_CONNECT_URL;
    
    public function init(): void
    {
        parent::init();

        $this->requirePermission('accessPlugin-commerce-sendcloud');

        $this->getView()->registerAssetBundle(CommerceCpAsset::class);
    }

    /**
     * @return Response
     */
    public function actionIndex(): Response
    {
        $settings = SendcloudPlugin::getInstance()->getSettings();
        $settings->validate();

        $labelFormats = LabelFormat::getOptions();

        $variables = compact('settings', 'labelFormats');

        return $this->renderTemplate('commerce-sendcloud/settings/index', $variables);
    }

    public function actionSaveSettings(): ?Response
    {
        $this->requirePostRequest();

        $params = $this->request->getBodyParams();
        $data = $params['settings'];

        $settings = SendcloudPlugin::getInstance()->getSettings();
        $settings->hsCodeFieldHandle = $data['hsCodeFieldHandle'] ?? $settings->hsCodeFieldHandle;
        $settings->originCountryFieldHandle = $data['originCountryFieldHandle'] ?? $settings->originCountryFieldHandle;
        $settings->phoneNumberFieldHandle = $data['phoneNumberFieldHandle'] ?? $settings->phoneNumberFieldHandle;
        $settings->labelFormat = $data['labelFormat'] ?? $settings->labelFormat;
        $settings->setApplyShippingRules($data['applyShippingRules'] ?? $settings->isApplyShippingRules(false));

        if (!$settings->validate()) {
            $this->setFailFlash(Craft::t('commerce-sendcloud', 'Couldn`t save settings.'));
            $labelFormats = LabelFormat::getOptions();

            $variables = compact('settings', 'labelFormats');
            return $this->renderTemplate('commerce-sendcloud/settings/field-mapping', $variables);
        }

        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(SendcloudPlugin::getInstance(), $settings->toArray());

        if (!$pluginSettingsSaved) {
            $this->setFailFlash(Craft::t('commerce-sendcloud', 'Couldn`t save settings.'));
            $labelFormats = LabelFormat::getOptions();

            $variables = compact('settings', 'labelFormats');
            return $this->renderTemplate('commerce-sendcloud/settings/index', $variables);
        }

        $this->setSuccessFlash(Craft::t('commerce-sendcloud', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionOrders(?string $storeHandle = null): Response
    {
        if ($storeHandle === null) {
            /** @var Site|HasStoreInterface $ste */
            $site = Cp::requestedSite();
            $store = $site->getStore();

            return $this->redirect("commerce-sendcloud/settings/$store->handle/orders");
        }

        $settings = SendcloudPlugin::getInstance()->getSettings();
        $settings->validate();
        $labelFormats = LabelFormat::getOptions();
        $variables = compact('settings', 'storeHandle', 'labelFormats');

        if ($variables['storeHandle']) {
            $store = CommercePlugin::getInstance()->getStores()->getStoreByHandle($storeHandle);
            $variables['store'] = $store;

            if (!$variables['store']) {
                throw new HttpException(404, Craft::t('commerce-sendcloud', 'Store not found'));
            }

            $storeOrderStatuses = CommercePlugin::getInstance()->getOrderStatuses()->getAllOrderStatuses($store->id);
            $orderStatuses = [];
            foreach ($storeOrderStatuses as $storeOrderStatus) {
                $orderStatuses[] = ['value' => $storeOrderStatus->handle, 'label' => $storeOrderStatus->name];
            }

            $variables['orderStatuses'] = $orderStatuses;
            $statusMapping = SendcloudPlugin::getInstance()->statusMapping->getStatusMappingByStoreId($store->id);
            $variables['statusMapping'] = $statusMapping;

        }

        return $this->renderTemplate('commerce-sendcloud/settings/orders', $variables);
    }

    public function actionSaveOrderSettings(): ?Response
    {
        $this->requirePostRequest();

        $params = $this->request->getBodyParams();

        $storeId = $params['storeId'];
        $store = CommercePlugin::getInstance()->getStores()->getStoreById($storeId);

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

    public function actionOrderSync(): Response
    {
        $stores = CommercePlugin::getInstance()->getStores()->getAllStores();

        return $this->renderTemplate('commerce-sendcloud/settings/order-sync', compact('stores'));
    }
}
