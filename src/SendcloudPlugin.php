<?php

namespace white\commerce\sendcloud;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\commerce\elements\Order;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\log\MonologTarget;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use nystudio107\codeeditor\autocompletes\CraftApiAutocomplete;
use nystudio107\codeeditor\autocompletes\TwigLanguageAutocomplete;
use nystudio107\codeeditor\events\RegisterCodeEditorAutocompletesEvent;
use nystudio107\codeeditor\services\AutocompleteService;
use Psr\Log\LogLevel;
use white\commerce\sendcloud\elements\actions\BulkPrintSendcloudLabelsAction;
use white\commerce\sendcloud\elements\actions\BulkPushToSendcloudAction;
use white\commerce\sendcloud\exception\SendcloudRequestException;
use white\commerce\sendcloud\models\Settings;
use white\commerce\sendcloud\plugin\Routes;
use white\commerce\sendcloud\services\Integrations;
use white\commerce\sendcloud\services\OrderSync;
use white\commerce\sendcloud\services\ParcelItems;
use white\commerce\sendcloud\services\SendcloudApi;
use white\commerce\sendcloud\services\StatusMapping;
use white\commerce\sendcloud\variables\SendcloudVariable;
use yii\base\Event;
use yii\log\Logger;

/**
 * @property Integrations $integrations
 * @property OrderSync $orderSync
 * @property ParcelItems $parcelItems
 * @property StatusMapping $statusMapping
 * @property-read mixed $settingsResponse
 * @property-read null|array $cpNavItem
 * @property-read Settings $settings
 * @property SendcloudApi $sendcloudApi
 * @method Settings getSettings()
 */
class SendcloudPlugin extends Plugin
{
    public const LOG_CATEGORY = 'commerce-sendcloud';

    public static function config(): array
    {
        return [
            'components' => [
                'integrations' => ['class' => Integrations::class],
                'orderSync' => ['class' => OrderSync::class],
                'parcelItems' => ['class' => ParcelItems::class],
                'sendcloudApi' => ['class' => SendcloudApi::class],
                'statusMapping' => ['class' => StatusMapping::class],
            ],
        ];
    }

    public string $schemaVersion = '3.0.0';

    use Routes;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        $request = Craft::$app->getRequest();

        $this->registerEventListeners();
        $this->registerVariables();
        $this->registerPermissions();
        $this->_registerLogTarget();

        if ($request->getIsCpRequest()) {
            $this->_registerCpRoutes();
        } else {
            $this->_registerSiteRoutes();
        }
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
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('commerce-sendcloud/settings/field-mapping'));
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();

        if (Craft::$app->getUser()->checkPermission('commerce-sendcloud-manageStoreSettings')) {
            $item['subnav']['store-settings'] = [
                'label' => Craft::t('commerce-sendcloud', 'Store Settings'),
                'url' => 'commerce-sendcloud/store-settings',
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $item['subnav']['settings'] = [
                'label' => Craft::t('commerce-sendcloud', 'Settings'),
                'url' => 'commerce-sendcloud/settings',
            ];
        }

        return $item;
    }

    /**
     * @return void
     */
    protected function registerEventListeners(): void
    {
        $this->orderSync->registerEventListeners();

        Event::on(
            AutocompleteService::class,
            AutocompleteService::EVENT_REGISTER_CODEEDITOR_AUTOCOMPLETES,
            function(RegisterCodeEditorAutocompletesEvent $event) {
                if ($event->fieldType === 'SendcloudOrderNumber') {
                    $config = [
                        'elementRouteGlobals' => [
                            'order' => new Order(),
                        ],
                    ];
                    $event->types = [];
                    $event->types[] = [CraftApiAutocomplete::class => $config];
                    $event->types[] = TwigLanguageAutocomplete::class;
                }
            }
        );

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
                        'commerce-sendcloud-manageStoreSettings' => [
                            'label' => Craft::t('commerce-sendcloud', 'Manage sendcloud store settings'),
                        ],
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
            if ($exception instanceof SendcloudRequestException) {
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
