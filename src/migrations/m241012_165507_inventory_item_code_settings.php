<?php

namespace white\commerce\sendcloud\migrations;

use Craft;
use craft\db\Migration;

/**
 * m241012_165507_inventory_item_code_settings migration.
 */
class m241012_165507_inventory_item_code_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Place migration code here...
        $projectConfig = Craft::$app->getProjectConfig();

        $hsCodeFieldHandle = $projectConfig->get('plugins.commerce-sendcloud.settings.hsCodeFieldHandle') ?? null;
        $originCountryFieldHandle = $projectConfig->get('plugins.commerce-sendcloud.settings.originCountryFieldHandle') ?? null;
        if ($hsCodeFieldHandle !== null || $originCountryFieldHandle !== null) {
            $projectConfig->set('plugins.commerce-sendcloud.settings.useInventoryItemCodes', true);
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";
        return false;
    }
}
