<?php


namespace white\commerce\sendcloud\controllers\cp;

use Craft;
use craft\commerce\Plugin as CommercePlugin;
use craft\errors\MissingComponentException;
use craft\errors\SiteNotFoundException;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use white\commerce\sendcloud\models\Integration;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\StaleObjectException;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SettingsController extends Controller
{
    public const DEFAULT_SENDCLOUD_CONNECT_URL = 'https://panel.sendcloud.sc/shops/craft-commerce/connect/';
    
    public string $sendcloudConnectUrl = self::DEFAULT_SENDCLOUD_CONNECT_URL;
    
    public function init(): void
    {
        parent::init();

        $this->requirePermission('accessPlugin-commerce-sendcloud');
    }

    /**
     * @return Response
     */
    public function actionIndex(): Response
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

    /**
     * @return Response
     * @throws NotFoundHttpException
     * @throws MissingComponentException
     * @throws Exception
     * @throws BadRequestHttpException
     * @throws \Exception
     */
    public function actionConnect(): Response
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
            ? UrlHelper::cpUrl('', array_merge([$generalConfig->pathParam => $webhookPath], $webhookArgs))
            : UrlHelper::cpUrl($webhookPath, $webhookArgs);
        
        
        $url = $this->sendcloudConnectUrl;
        $url .= '?' . http_build_query([
            'url_webshop' => $site->getBaseUrl(),
            'webhook_url' => $webhookUrl,
            'shop_name' => Craft::$app->getSystemName() === $site->name ? $site->name : sprintf('%s: %s', Craft::$app->getSystemName(), $site->name),
        ]);

        return $this->redirect($url);
    }

    /**
     * @param int $siteId
     * @return Response
     */
    public function actionGetIntegrationStatus(int $siteId): Response
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

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionDisconnect(): Response
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

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws SiteNotFoundException
     * @throws InvalidConfigException
     */
    public function actionGetShippingMethods(): Response
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

        $craftCountries = CommercePlugin::getInstance()->getStore()->getStore()->getCountriesList();

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
