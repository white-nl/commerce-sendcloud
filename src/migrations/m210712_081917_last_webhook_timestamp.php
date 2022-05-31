<?php

namespace white\commerce\sendcloud\migrations;

use craft\db\Migration;

/**
 * m210712_081917_last_webhook_timestamp migration.
 */
class m210712_081917_last_webhook_timestamp extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%sendcloud_ordersyncstatus}}', 'lastWebhookTimestamp')) {
            $this->addColumn('{{%sendcloud_ordersyncstatus}}', 'lastWebhookTimestamp', $this->bigInteger()->null()->after('lastError'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        if ($this->db->columnExists('{{%sendcloud_ordersyncstatus}}', 'lastWebhookTimestamp')) {
            $this->dropColumn('{{%sendcloud_ordersyncstatus}}', 'lastWebhookTimestamp');
        }
    }
}
