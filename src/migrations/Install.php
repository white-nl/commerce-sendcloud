<?php

namespace white\commerce\sendcloud\migrations;

use craft\db\Migration;

class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->tableExists('{{%sendcloud_integrations}}')) {
            $this->createTable('{{%sendcloud_integrations}}', [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->notNull(),
                'token' => $this->string(16)->notNull(),
                'externalId' => $this->integer(),
                'publicKey' => $this->text(),
                'secretKey' => $this->text(),
                'system' => $this->string(255),
                'shopUrl' => $this->text(),
                'webhookUrl' => $this->text(),
                'servicePointEnabled' => $this->boolean()->notNull(),
                'servicePointCarriers' => $this->json(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, '{{%sendcloud_integrations}}', ['siteId'], true);
        }
        
        if (!$this->db->tableExists('{{%sendcloud_ordersyncstatus}}')) {
            $this->createTable('{{%sendcloud_ordersyncstatus}}', [
                'id' => $this->bigPrimaryKey(),
                'orderId' => $this->integer()->notNull(),
                'parcelId' => $this->integer()->null(),
                'statusId' => $this->integer()->null(),
                'statusMessage' => $this->string(255),
                'carrier' => $this->string(64)->null(),
                'trackingNumber' => $this->string(255)->null(),
                'trackingUrl' => $this->text()->null(),
                'servicePoint' => $this->json(),
                'lastError' => $this->text()->null(),
                'lastWebhookTimestamp' => $this->bigInteger()->null(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->addForeignKey(null, '{{%sendcloud_ordersyncstatus}}', 'orderId', '{{%commerce_orders}}', 'id', 'CASCADE', null);
            $this->createIndex(null, '{{%sendcloud_ordersyncstatus}}', ['orderId'], true);
            $this->createIndex(null, '{{%sendcloud_ordersyncstatus}}', ['parcelId']);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists('{{%sendcloud_ordersyncstatus}}');
        $this->dropTableIfExists('{{%sendcloud_integrations}}');
    }
}
