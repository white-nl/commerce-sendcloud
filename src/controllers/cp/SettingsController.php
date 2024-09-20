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
    public function actionFieldMapping(): Response
    {
        $settings = SendcloudPlugin::getInstance()->getSettings();
        $settings->validate();

        return $this->renderTemplate('commerce-sendcloud/settings/field-mapping', [
            'settings' => $settings,
        ]);
    }

    public function actionSaveFieldMappingSettings(): ?Response
    {
        $this->requirePostRequest();

        $params = $this->request->getBodyParams();
        $data = $params['settings'];

        $settings = SendcloudPlugin::getInstance()->getSettings();
        $settings->hsCodeFieldHandle = $data['hsCodeFieldHandle'] ?? $settings->hsCodeFieldHandle;
        $settings->originCountryFieldHandle = $data['originCountryFieldHandle'] ?? $settings->originCountryFieldHandle;
        $settings->phoneNumberFieldHandle = $data['phoneNumberFieldHandle'] ?? $settings->phoneNumberFieldHandle;

        if (!$settings->validate()) {
            $this->setFailFlash(Craft::t('commerce-sendcloud', 'Couldn`t save settings.'));
            return $this->renderTemplate('commerce-sendcloud/settings/field-mapping', compact('settings'));
        }

        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(SendcloudPlugin::getInstance(), $settings->toArray());

        if (!$pluginSettingsSaved) {
            $this->setFailFlash(Craft::t('commerce-sendcloud', 'Couldn`t save settings.'));
            return $this->renderTemplate('commerce-sendcloud/settings/field-mapping', compact('settings'));
        }

        $this->setSuccessFlash(Craft::t('commerce-sendcloud', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionOrderSync(): Response
    {
        $stores = CommercePlugin::getInstance()->getStores()->getAllStores();

        return $this->renderTemplate('commerce-sendcloud/settings/order-sync', compact('stores'));
    }
}
