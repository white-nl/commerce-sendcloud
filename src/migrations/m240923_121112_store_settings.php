<?php

namespace white\commerce\sendcloud\migrations;

use Craft;
use craft\commerce\db\Table as CommerceTable;
use craft\db\Migration;
use craft\db\Query;
use white\commerce\sendcloud\enums\LabelFormat;
use white\commerce\sendcloud\models\StatusMapping;
use white\commerce\sendcloud\SendcloudPlugin;

/**
 * m240923_121112_store_settings migration.
 */
class m240923_121112_store_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Drop siteId from sendcloud_integrations
        if ($this->db->columnExists('{{%sendcloud_integrations}}', 'siteId')) {
            $this->dropIndexIfExists('{{%sendcloud_integrations}}', 'siteId');
            $this->dropColumn('{{%sendcloud_integrations}}', 'siteId');
        }

        // Add storeId to sendcloud_integrations
        if (!$this->db->columnExists('{{%sendcloud_integrations}}', 'storeId')) {
            $this->addColumn('{{%sendcloud_integrations}}', 'storeId', $this->integer()->notNull()->after('id'));
            $this->createIndex(null, '{{%sendcloud_integrations}}', ['storeId'], true);
        }

        $integrations = (new Query())
            ->select(['id', 'token', 'externalId', 'publicKey', 'secretKey', 'system', 'shopUrl', 'webhookUrl', 'servicePointEnabled', 'servicePointCarriers'])
            ->from(['{{%sendcloud_integrations}}'])
            ->one();

        $primaryStore = (new Query())
            ->select(['id', 'uid'])
            ->from(CommerceTable::STORES)
            ->where(['primary' => true])
            ->one();

        if ($integrations) {
            $integrations['storeId'] = $primaryStore['id'];

            $this->update(
                table: '{{%sendcloud_integrations}}',
                columns: $integrations,
                condition: ['id' => $integrations['id']],
                updateTimestamp: false,
            );
        }

        $projectConfig = Craft::$app->getProjectConfig();

        $originalValue = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        $oldSettings = $projectConfig->get('plugins.commerce-sendcloud.settings', true);
        $sendcloudFileConfig = Craft::$app->getConfig()->getConfigFromFile('commerce-sendcloud');
        $statusMappingService = SendcloudPlugin::getInstance()->statusMapping;
        $configPath = $statusMappingService::CONFIG_STATUS_MAPPING_KEY . '.' . $primaryStore['uid'];
        if ($projectConfig->get($configPath, true) === null) {
            $statusMapping = new StatusMapping();
            $statusMapping->orderStatusesToPush = $oldSettings['orderStatusesToPush'] ?? $sendcloudFileConfig['orderStatusesToPush'] ?? [];
            $statusMapping->orderStatusesToCreateLabel = $oldSettings['orderStatusesToCreateLabel'] ?? $sendcloudFileConfig['orderStatusesToCreateLabel'] ?? [];
            $statusMapping->orderStatusMapping = $oldSettings['orderStatusMapping'] ?? $sendcloudFileConfig['orderStatusMapping'] ?? [];
            $statusMapping->orderNumberFormat = $oldSettings['orderNumberFormat'] ?? $sendcloudFileConfig['orderNumberFormat'] ?? '{{ order.id }}';
            $statusMapping->storeId = $primaryStore['id'];
            $projectConfig->set($configPath, $statusMapping->getConfig());
        }

        $newSettings = [
            'hsCodeFieldHandle' => $sendcloudFileConfig['hsCodeFieldHandle'] ?? $oldSettings['hsCodeFieldHandle'] ?? null,
            'originCountryFieldHandle' => $sendcloudFileConfig['originCountryFieldHandle'] ?? $oldSettings['originCountryFieldHandle'] ?? null,
            'phoneNumberFieldHandle' => $sendcloudFileConfig['phoneNumberFieldHandle'] ?? $oldSettings['phoneNumberFieldHandle'] ?? null,
            'pushOrderJobPriority' => $sendcloudFileConfig['pushOrderJobPriority'] ?? $oldSettings['pushOrderJobPriority'] ?? 1024,
            'createLabelJobPriority' => $sendcloudFileConfig['createLabelJobPriority'] ?? $oldSettings['createLabelJobPriority'] ?? 1024,
            'labelFormat' => $sendcloudFileConfig['labelFormat'] ?? LabelFormat::FORMAT_A6->value,
            'applyShippingRules' => $sendcloudFileConfig['applyShippingRules'] ?? true,
        ];

        $projectConfig->set('plugins.commerce-sendcloud.settings', $newSettings);

        $projectConfig->muteEvents = $originalValue;
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240923_121112_store_settings cannot be reverted.\n";
        return false;
    }
}
