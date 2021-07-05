<?php

namespace white\commerce\sendcloud\migrations;

use Craft;
use craft\db\Migration;

/**
 * m210607_083715_lasterror_field migration.
 */
class m210607_083715_lasterror_field extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%sendcloud_ordersyncstatus}}', 'lastError')) {
            $this->addColumn('{{%sendcloud_ordersyncstatus}}', 'lastError', $this->text()->null()->after('servicePoint'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        if ($this->db->columnExists('{{%sendcloud_ordersyncstatus}}', 'lastError')) {
            $this->dropColumn('{{%sendcloud_ordersyncstatus}}', 'lastError');
        }
    }
}
