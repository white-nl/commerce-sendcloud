<?php

namespace white\commerce\sendcloud\controllers\cp;

use Craft;
use craft\commerce\base\HasStoreInterface;
use craft\commerce\Plugin as Commerce;
use craft\commerce\web\assets\commercecp\CommerceCpAsset;
use craft\helpers\Cp;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use white\commerce\sendcloud\models\Integration;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class StoreSettingsController extends Controller
{
    public const DEFAULT_SENDCLOUD_CONNECT_URL = 'https://panel.sendcloud.sc/shops/craft-commerce/connect/';

    public string $sendcloudConnectUrl = self::DEFAULT_SENDCLOUD_CONNECT_URL;

    public function init(): void
    {
        parent::init();

        $this->getView()->registerAssetBundle(CommerceCpAsset::class);
    }

    public function actionIndex(): Response
    {
        $user = Craft::$app->getUser();
        /** @var Site|HasStoreInterface $site */
        $site = Cp::requestedSite();

        if ($user->checkPermission('commerce-sendcloud-manageStoreSettings')) {
            $store = $site->getStore();
            return $this->redirect("commerce-sendcloud/store-settings/$store->handle/shipping-methods");
        }

        throw new ForbiddenHttpException();
    }

    public function actionIntegration(?string $storeHandle = null): Response
    {
        $this->requirePermission('commerce-sendcloud-manageStoreSettings');

        $variables = compact('storeHandle');

        if ($variables['storeHandle']) {
            $variables['store'] = Commerce::getInstance()->getStores()->getStoreByHandle($storeHandle);

            if (!$variables['store']) {
                throw new HttpException(404);
            }
        } else {
            /** @var Site|HasStoreInterface $site */
            $site = Cp::requestedSite();
            $variables['store'] = $site->getStore();
            $variables['storeHandle'] = $variables['store']->handle;
        }

        return $this->renderTemplate('commerce-sendcloud/store-settings/_integration', $variables);
    }

    public function actionConnect(): Response
    {
        $storeId = Craft::$app->getRequest()->getRequiredBodyParam('storeId');
        $store = Commerce::getInstance()->getStores()->getStoreById($storeId);
        if (!$store) {
            throw new NotFoundHttpException('Store not found.');
        }

        $integrationService = SendcloudPlugin::getInstance()->integrations;

        if ($integrationService->getIntegrationByStoreId($storeId)) {
            Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', 'Integration for the chosen store already exists.'));

            return $this->redirectToPostedUrl();
        }

        $integration = new Integration();
        $integration->storeId = $storeId;
        $integration->token = StringHelper::randomString(16);

        if (!$integrationService->saveIntegration($integration)) {
            throw new \Exception('Could not save the integration.');
        }

        $webhookUrl = $this->_createWebhookUrl($integration);
        $url = $this->sendcloudConnectUrl;
        $url .= '?' . http_build_query([
            'url_webshop' => $store->getSites()->first()?->getBaseUrl() ?? Craft::$app->getSites()->getPrimarySite()->getBaseUrl(),
            'webhook_url' => $webhookUrl,
            'shop_name' => sprintf('%s: %s', Craft::$app->getSystemName(), $store->getName()),
        ]);

        return $this->redirect($url);
    }
    
    public function actionRefresh(): Response
    {
        $storeId = Craft::$app->getRequest()->getRequiredBodyParam('storeId');
        $store = Commerce::getInstance()->getStores()->getStoreById($storeId);
        if (!$store) {
            throw new NotFoundHttpException('Store not found.');
        }
        
        $integrationService = SendcloudPlugin::getInstance()->integrations;
        
        $integration = $integrationService->getIntegrationByStoreId($storeId);
        if (!$integration) {
            Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', 'Integration not found.'));
        }
        
        $webhookUrl = $this->_createWebhookUrl($integration);
        $integration->shopUrl = $store->getSites()->first()?->getBaseUrl() ?? Craft::$app->getSites()->getPrimarySite()->getBaseUrl();
        $integration->webhookUrl = $webhookUrl;

        $shopName = sprintf('%s: %s', Craft::$app->getSystemName(), $store->getName());

        $client = SendcloudPlugin::getInstance()->sendcloudApi->getClient($storeId);
        if ($client->updateIntegration($integration, $shopName)) {
            Craft::$app->getSession()->setNotice(Craft::t('commerce-sendcloud', 'Integration updated.'));
        }

        return $this->redirectToPostedUrl();
    }

    public function actionDisconnect(): Response
    {
        $storeId = $this->request->getRequiredBodyParam('storeId');

        $integrationService = SendcloudPlugin::getInstance()->integrations;

        $integration = $integrationService->getIntegrationByStoreId($storeId);
        if (!$integration) {
            Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', "Integration not found."));

            return $this->redirectToPostedUrl();
        }

        $client = SendcloudPlugin::getInstance()->sendcloudApi->getClient($storeId);
        if ($client->removeIntegration($integration->externalId)) {
            $integrationService->deleteIntegrationById($integration->id);

            Craft::$app->getSession()->setNotice(Craft::t('commerce-sendcloud', "Integration removed."));
        }

        return $this->redirectToPostedUrl();
    }

    public function actionGetIntegrationStatus(int $storeId): Response
    {
        $integrationService = SendcloudPlugin::getInstance()->integrations;

        $integration = $integrationService->getIntegrationByStoreId($storeId);
        if (!$integration) {
            return $this->asJson([
                'status' => 'none',
                'statusText' => Craft::t('commerce-sendcloud', 'Not registered'),
            ]);
        }

        if (!$integration->getIsActive()) {
            return $this->asJson([
                'status' => 'pening',
                'statusText' => Craft::t('commerce-sendcloud', 'Pending'),
            ]);
        }

        return $this->asJson([
            'status' => 'active',
            'statusText' => Craft::t('commerce-sendcloud', 'Active'),
            'integration' => $integration->toArray([
                'id',
                'storeId',
                'publicKey',
                'shopUrl',
                'servicePointEnabled',
                'servicePointCarriers',
            ]),
        ]);
    }

    public function actionShippingMethods(?string $storeHandle = null): Response
    {
        $variables = compact('storeHandle');
        $store = Commerce::getInstance()->getStores()->getStoreByHandle($storeHandle);
        $variables['store'] = $store;

        $integrationService = SendcloudPlugin::getInstance()->integrations;
        $sendcloudApiService = SendcloudPlugin::getInstance()->sendcloudApi;

        $integration = $integrationService->getIntegrationByStoreId($store->id);
        if (!$integration || !$integration->getIsActive()) {
            Craft::$app->getSession()->setNotice(Craft::t('commerce-sendcloud', 'Unable to load shipping methods. Check your integration settings.'));
            return $this->redirect("commerce-sendcloud/store-settings/{$storeHandle}");
        }

        $client = $sendcloudApiService->getClient($store->id);
        $shippingMethods = $client->getShippingMethods($store->id);

        $craftShippingMethods = Commerce::getInstance()->getShippingMethods()->getAllShippingMethods($store->id);
        $craftShippingMethods = $craftShippingMethods->keyBy('name');

        $craftCountries = $store->getSettings()->getCountriesList();

        $result = [];
        foreach ($shippingMethods as $shippingMethod) {
            $methodData = $shippingMethod;

            if (!array_intersect(array_keys($craftCountries), array_values($shippingMethod->getCountries()))) {
                continue;
            }

            if ($craftShippingMethods->has($methodData->getName())) {
                $methodData->setCraftMethodId($craftShippingMethods[$methodData->getName()]->id);
            }

            $result[] = $methodData;
        }

        $variables['shippingMethods'] = $result;

        return $this->renderTemplate('commerce-sendcloud/store-settings/_shipping-methods', $variables);
    }

    /**
     * @param Integration $integration
     * @return string
     * @throws \craft\errors\SiteNotFoundException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    private function _createWebhookUrl(Integration $integration): string
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $webhookPath = 'commerce-sendcloud/webhook';
        $webhookArgs = [
            'id' => $integration->id,
            'token' => $integration->token,
        ];
        return $generalConfig->pathParam
            ? UrlHelper::cpUrl('', array_merge([$generalConfig->pathParam => $webhookPath], $webhookArgs))
            : UrlHelper::cpUrl($webhookPath, $webhookArgs);
    }
}
