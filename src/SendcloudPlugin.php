<?php

namespace white\commerce\sendcloud;

use Craft;
use craft\base\Plugin;
use craft\commerce\elements\Order;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use JouwWeb\SendCloud\Exception\SendCloudRequestException;
use white\commerce\sendcloud\elements\actions\BulkPrintSendcloudLabelsAction;
use white\commerce\sendcloud\elements\actions\BulkPushToSendcloudAction;
use white\commerce\sendcloud\models\Settings;
use white\commerce\sendcloud\services\Integrations;
use white\commerce\sendcloud\services\OrderSync;
use white\commerce\sendcloud\services\SendcloudApi;
use white\commerce\sendcloud\variables\SendcloudVariable;
use yii\base\Event;
use yii\log\Logger;

/**
 * @property Integrations $integrations
 * @property OrderSync $orderSync
 * @property SendcloudApi $sendcloudApi
 * @method Settings getSettings()
 */
class SendcloudPlugin extends Plugin
{
    public const LOG_CATEGORY = 'commerce-sendcloud';

    public $schemaVersion = '1.0.3';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->registerServices();
        $this->registerNameOverride();
        $this->registerCpUrls();
        $this->registerSiteUrls();
        $this->registerEventListeners();
        $this->registerVariables();
        $this->registerPermissions();
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse()
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('commerce-sendcloud/settings'));
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        $item = parent::getCpNavItem();
        $item['subnav'] = [
            'settings' => [
                'label' => Craft::t('commerce-sendcloud', 'Settings'),
                'url' => 'commerce-sendcloud/settings'
            ],
        ];
        return $item;
    }

    protected function registerServices()
    {
        $this->setComponents([
            'integrations' => Integrations::class,
            'orderSync' => OrderSync::class,
            'sendcloudApi' => SendcloudApi::class,
        ]);
    }

    protected function registerNameOverride()
    {
        $name = $this->getSettings()->pluginNameOverride;
        if (empty($name)) {
            $name = Craft::t('commerce-sendcloud', "Sendcloud");
        }

        $this->name = $name;
    }

    protected function registerCpUrls()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['commerce-sendcloud'] = 'commerce-sendcloud/cp/settings/index';
                $event->rules['commerce-sendcloud/settings'] = 'commerce-sendcloud/cp/settings/index';
            }
        );
    }

    protected function registerSiteUrls()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['commerce-sendcloud/webhook'] = 'commerce-sendcloud/webhook/handle';
            }
        );
    }

    protected function registerEventListeners()
    {
        $this->orderSync->registerEventListeners();

        Event::on(Order::class, Order::EVENT_REGISTER_ACTIONS, function(RegisterElementActionsEvent $event) {
            $user = Craft::$app->getUser()->getIdentity();
            if ($user->can('commerce-sendcloud-pushOrders')) {
                $event->actions[] = BulkPushToSendcloudAction::class;
            }
            if ($user->can('commerce-sendcloud-printLabels')) {
                $event->actions[] = BulkPrintSendcloudLabelsAction::class;
            }
        });

        Craft::$app->getView()->hook('cp.commerce.order.edit.details', function(array &$context) { // Commerce 3.2.0
            /** @var Order $order */
            $order = $context['order'];
            $status = $this->orderSync->getOrderSyncStatusByOrderId($order->id);
            
            return Craft::$app->getView()->renderTemplate('commerce-sendcloud/_order-details-panel', [
                'plugin' => $this,
                'order' => $order,
                'status' => $status,
            ]);
        });
    }

    protected function registerVariables(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, static function (Event $event) {
            $event->sender->set('commercesendcloud', SendcloudVariable::class);
        });
    }

    protected function registerPermissions()
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $event->permissions[Craft::t('commerce-sendcloud', 'Sendcloud')] = [
                'commerce-sendcloud-pushOrders' => ['label' => Craft::t('commerce-sendcloud', 'Manually push orders to Sendcloud')],
                'commerce-sendcloud-printLabels' => ['label' => Craft::t('commerce-sendcloud', 'Print labels')],
            ];
        });
    }

    /**
     * @param string $message
     * @param int $logLevel
     */
    public static function log(string $message, $logLevel = Logger::LEVEL_INFO): void
    {
        Craft::getLogger()->log($message, $logLevel, self::LOG_CATEGORY);
    }

    /**
     * @param string $message
     * @param \Exception|null $exception
     */
    public static function error(string $message, \Exception $exception = null): void
    {
        while ($exception !== null) {
            $message .= "\n  " . get_class($exception) . ": " . $exception->getMessage();
            if ($exception instanceof SendCloudRequestException) {
                $message .= "  " . $exception->getSendCloudMessage();
            }
            
            $exception = $exception->getPrevious();
        }
        
        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, self::LOG_CATEGORY);
    }
}
