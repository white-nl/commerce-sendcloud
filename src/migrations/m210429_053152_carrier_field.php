<?php

namespace white\commerce\sendcloud\migrations;

use Craft;
use craft\db\Migration;

/**
 * m210429_053152_carrier_field migration.
 */
class m210429_053152_carrier_field extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%sendcloud_ordersyncstatus}}', 'carrier')) {
            $this->addColumn('{{%sendcloud_ordersyncstatus}}', 'carrier', $this->string(64)->null()->after('statusMessage'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        if ($this->db->columnExists('{{%sendcloud_ordersyncstatus}}', 'carrier')) {
            $this->dropColumn('{{%sendcloud_ordersyncstatus}}', 'carrier');
        }
    }
}
