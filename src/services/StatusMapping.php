<?php

namespace white\commerce\sendcloud\services;

use Craft;
use craft\commerce\Plugin;
use white\commerce\sendcloud\models\StatusMapping as StatusMappingModel;
use yii\base\Component;

class StatusMapping extends Component
{
    public const CONFIG_STATUS_MAPPING_KEY = 'commerceSendcloud.statusMapping';

    public function getStatusMappingByStoreId(int $storeId): StatusMappingModel
    {
        $store = Plugin::getInstance()->getStores()->getStoreById($storeId);

        $config = Craft::$app->getProjectConfig()->get(self::CONFIG_STATUS_MAPPING_KEY . '.' . $store->uid) ?? [];
        $statusMapping = Craft::createObject([
            'class' => StatusMappingModel::class,
            'attributes' => $config,
        ]);
        $statusMapping->storeId = $store->id;
        return $statusMapping;
    }

    public function saveStatusMapping(StatusMappingModel $statusMapping): bool
    {
        if (!$statusMapping->validate()) {
            Craft::info(Craft::t('commerce-sendcloud', 'Status mapping not saved due to validation error.'), __METHOD__);
            return false;
        }

        $projectConfig = Craft::$app->getProjectConfig();

        $store = $statusMapping->getStore();

        $configPath = self::CONFIG_STATUS_MAPPING_KEY . '.' . $store->uid;
        $projectConfig->set($configPath, $statusMapping->getConfig());
        return true;
    }
}
