<?php

namespace white\commerce\sendcloud;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\commerce\elements\Order;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\log\MonologTarget;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use JouwWeb\SendCloud\Exception\SendCloudRequestException;
use Psr\Log\LogLevel;
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
 * @property-read mixed $settingsResponse
 * @property-read null|array $cpNavItem
 * @property-read Settings $settings
 * @property SendcloudApi $sendcloudApi
 * @method Settings getSettings()
 */
class SendcloudPlugin extends Plugin
{
    public const LOG_CATEGORY = 'commerce-sendcloud';

    public string $schemaVersion = '1.0.3';

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
        $this->_registerLogTarget();
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
    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('commerce-sendcloud/settings'));
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['subnav'] = [
            'settings' => [
                'label' => Craft::t('commerce-sendcloud', 'Settings'),
                'url' => 'commerce-sendcloud/settings',
            ],
        ];
        return $item;
    }

    /**
     * @return void
     */
    protected function registerServices(): void
    {
        $this->setComponents([
            'integrations' => Integrations::class,
            'orderSync' => OrderSync::class,
            'sendcloudApi' => SendcloudApi::class,
        ]);
    }

    /**
     * @return void
     */
    protected function registerNameOverride(): void
    {
        $name = $this->getSettings()->pluginNameOverride;
        if (empty($name)) {
            $name = Craft::t('commerce-sendcloud', "Sendcloud");
        }

        $this->name = $name;
    }

    /**
     * @return void
     */
    protected function registerCpUrls(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event): void {
                $event->rules['commerce-sendcloud'] = 'commerce-sendcloud/cp/settings/index';
                $event->rules['commerce-sendcloud/settings'] = 'commerce-sendcloud/cp/settings/index';
            }
        );
    }

    /**
     * @return void
     */
    protected function registerSiteUrls(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event): void {
                $event->rules['commerce-sendcloud/webhook'] = 'commerce-sendcloud/webhook/handle';
            }
        );
    }

    /**
     * @return void
     */
    protected function registerEventListeners(): void
    {
        $this->orderSync->registerEventListeners();

        Event::on(
            Order::class,
            Element::EVENT_REGISTER_ACTIONS,
            function(RegisterElementActionsEvent $event): void {
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
            $status = $this->orderSync->getOrderSyncStatusByOrderId($order->getId());
            
            return Craft::$app->getView()->renderTemplate('commerce-sendcloud/_order-details-panel', [
                'plugin' => $this,
                'order' => $order,
                'status' => $status,
            ]);
        });
    }

    /**
     * @return void
     */
    protected function registerVariables(): void
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            static function(Event $event): void {
                $event->sender->set('commercesendcloud', SendcloudVariable::class);
            }
        );
    }

    /**
     * @return void
     */
    protected function registerPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event): void {
                $event->permissions[] = [
                'heading' => Craft::t('commerce-sendcloud', 'Sendcloud'),
                'permissions' => [
                    'commerce-sendcloud-pushOrders' => [
                        'label' => Craft::t('commerce-sendcloud', 'Manually push orders to Sendcloud'),
                    ],
                    'commerce-sendcloud-printLabels' => [
                        'label' => Craft::t('commerce-sendcloud', 'Print labels'),
                    ],
                ],
            ];
            });
    }

    /**
     * @param string $message
     * @param int $logLevel
     * @return void
     */
    public static function log(string $message, int $logLevel = Logger::LEVEL_INFO): void
    {
        Craft::getLogger()->log($message, $logLevel, 'commerce-sendcloud');
    }

    /**
     * @param string $message
     * @param \Exception|null $exception
     * @return void
     */
    public static function error(string $message, \Exception $exception = null): void
    {
        while ($exception !== null) {
            $message .= "\n  " . $exception::class . ": " . $exception->getMessage();
            if ($exception instanceof SendCloudRequestException) {
                $message .= "  " . $exception->getSendCloudMessage();
            }
            
            $exception = $exception->getPrevious();
        }
        
        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'commerce-sendcloud');
    }

    private function _registerLogTarget(): void
    {
        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'commerce-sendcloud',
            'categories' => [self::LOG_CATEGORY],
            'level' => LogLevel::INFO,
            'logContext' => false,
            'allowLineBreaks' => true,
        ]);
    }
}
