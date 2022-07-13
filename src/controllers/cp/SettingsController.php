<?php


namespace white\commerce\sendcloud\controllers\cp;


use Craft;
use craft\commerce\Plugin as CommercePlugin;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use white\commerce\sendcloud\models\Integration;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\web\NotFoundHttpException;

class SettingsController extends Controller
{
    const DEFAULT_SENDCLOUD_CONNECT_URL = 'https://panel.sendcloud.sc/shops/craft-commerce/connect/';
    
    public $sendcloudConnectUrl = self::DEFAULT_SENDCLOUD_CONNECT_URL;
    
    public function init()
    {
        parent::init();

        $this->requirePermission('accessPlugin-commerce-sendcloud');
    }

    public function actionIndex()
    {
        $integrationService = SendcloudPlugin::getInstance()->integrations;
        
        $settings = SendcloudPlugin::getInstance()->getSettings();
        $settings->validate();

        return $this->renderTemplate('commerce-sendcloud/_settings', [
            'plugin' => SendcloudPlugin::getInstance(),
            'settings' => $settings,
            'allowAdminChanges' => Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            'integrationsBySiteId' => ArrayHelper::index($integrationService->getAllIntegrations(), 'siteId'),
        ]);
    }

    public function actionConnect()
    {
        $siteId = Craft::$app->getRequest()->getRequiredBodyParam('siteId');
        $site = Craft::$app->getSites()->getSiteById($siteId);
        if (!$site) {
            throw new NotFoundHttpException('Site not found.');
        }
        
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $integrationService = SendcloudPlugin::getInstance()->integrations;
        
        if ($integrationService->getIntegrationBySiteId($siteId)) {
            Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', "Integration for the chosen website already exists."));
            
            return $this->redirectToPostedUrl();
        }
        
        $integration = new Integration();
        $integration->siteId = $siteId;
        $integration->token = StringHelper::randomString(16);
        
        if (!$integrationService->saveIntegration($integration)) {
            throw new \Exception("Could not save the integration.");
        }
        
        // Build the webhook URL, preferring the path param to avoid potential webserver misconfiguration problems
        $webhookPath = 'commerce-sendcloud/webhook';
        $webhookArgs = [
            'id' => $integration->id,
            'token' => $integration->token,
        ];
        $webhookUrl = $generalConfig->pathParam
            ? UrlHelper::cpUrl('', array_merge([$generalConfig->pathParam => $webhookPath], $webhookArgs), null, $siteId)
            : UrlHelper::cpUrl($webhookPath, $webhookArgs, null, $siteId);
        
        
        $url = $this->sendcloudConnectUrl;
        $url .= '?' . http_build_query([
            'url_webshop' => $site->getBaseUrl(),
            'webhook_url' => $webhookUrl,
            'shop_name' => Craft::$app->getSystemName() === $site->name ? $site->name : sprintf('%s: %s', Craft::$app->getSystemName(), $site->name),
        ]);

        return $this->redirect($url);
    }

    public function actionGetIntegrationStatus($siteId)
    {
        $integrationService = SendcloudPlugin::getInstance()->integrations;
        
        $integration = $integrationService->getIntegrationBySiteId($siteId);
        if (!$integration) {
            return $this->asJson([
                'status' => 'none',
                'statusText' => \Craft::t('commerce-sendcloud', 'Not registered'),
            ]);
        }
        
        if (!$integration->getIsActive()) {
            return $this->asJson([
                'status' => 'pending',
                'statusText' => \Craft::t('commerce-sendcloud', 'Pending'),
            ]);
        }
        
        // TODO: Ping the webhook
        
        return $this->asJson([
            'status' => 'active',
            'statusText' => \Craft::t('commerce-sendcloud', 'Active'),
            'integration' => $integration->toArray(['id', 'siteId', 'publicKey', 'shopUrl', 'servicePointEnabled', 'servicePointCarriers']),
        ]);
    }

    public function actionDisconnect()
    {
        $siteId = $this->request->getRequiredBodyParam('siteId');

        $integrationService = SendcloudPlugin::getInstance()->integrations;
        
        $integration = $integrationService->getIntegrationBySiteId($siteId);
        if (!$integration) {
            Craft::$app->getSession()->setError(Craft::t('commerce-sendcloud', "Integration not found."));
            
            return $this->redirectToPostedUrl();
        }
        
        // TODO: Remove integration on the Sendcloud side
        
        $integrationService->deleteIntegrationById($integration->id);

        Craft::$app->getSession()->setNotice(Craft::t('commerce-sendcloud', "Integration removed."));
        
        return $this->redirectToPostedUrl();
    }

    public function actionGetShippingMethods()
    {
        $siteId = $this->request->getRequiredParam('siteId');

        $integrationService = SendcloudPlugin::getInstance()->integrations;
        $sendcloudApiService = SendcloudPlugin::getInstance()->sendcloudApi;

        $integration = $integrationService->getIntegrationBySiteId($siteId);
        if (!$integration || !$integration->getIsActive()) {
            return $this->asJson([
                'shippingMethods' => [],
            ]);
        }
        
        $client = $sendcloudApiService->getClient($siteId);
        $shippingMethods = $client->getShippingMethods();
        
        $craftShippingMethods = CommercePlugin::getInstance()->getShippingMethods()->getAllShippingMethods();
        $craftShippingMethods = ArrayHelper::index($craftShippingMethods, 'name');

        $craftCountries = CommercePlugin::getInstance()->getCountries()->getAllEnabledCountries();
        $craftCountries = ArrayHelper::index($craftCountries, 'iso');

        $result = [];
        foreach ($shippingMethods as $shippingMethod) {
            $methodData = $shippingMethod->toArray();
            
            if ($integration->servicePointEnabled) {
                $methodData['allowsServicePoints'] = (bool)$shippingMethod->getAllowsServicePoints();
            } elseif ($shippingMethod->getAllowsServicePoints()) {
                continue;
            }
            
            if (!array_intersect(array_keys($craftCountries), array_keys($methodData['prices']))) {
                continue;
            }
            
            if (array_key_exists($methodData['name'], $craftShippingMethods)) {
                $methodData['craftId'] = $craftShippingMethods[$methodData['name']]->id;
            }
            
            $result[] = $methodData;
        }
        
        return $this->asJson([
            'shippingMethods' => $result,
        ]);
    }
}