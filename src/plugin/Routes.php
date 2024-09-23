<?php

namespace white\commerce\sendcloud\plugin;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

trait Routes
{
    private function _registerSiteRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            static function(RegisterUrlRulesEvent $event): void {
                $event->rules['commerce-sendcloud/webhook'] = 'commerce-sendcloud/webhook/handle';
            }
        );
    }

    private function _registerCpRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            static function(RegisterUrlRulesEvent $event): void {
                $event->rules['commerce-sendcloud'] = 'commerce-sendcloud/cp/store-settings/index'; // Redirects to the first store
                // Settings
                $event->rules['commerce-sendcloud/settings'] = 'commerce-sendcloud/cp/settings';
                $event->rules['commerce-sendcloud/settings/settings'] = 'commerce-sendcloud/cp/settings';
                $event->rules['commerce-sendcloud/settings/orders'] = 'commerce-sendcloud/cp/settings/orders'; // Redirects to the first store
                $event->rules['commerce-sendcloud/settings/<storeHandle:{handle}>/orders'] = 'commerce-sendcloud/cp/settings/orders';

                // Store settings
                $event->rules['commerce-sendcloud/store-settings'] = 'commerce-sendcloud/cp/store-settings/index'; // Redirects to the first store
                $event->rules['commerce-sendcloud/store-settings/<storeHandle:{handle}>'] = 'commerce-sendcloud/cp/store-settings/integration';
                $event->rules['commerce-sendcloud/store-settings/<storeHandle:{handle}>/shipping-methods'] = 'commerce-sendcloud/cp/store-settings/shipping-methods';
            }
        );
    }
}
