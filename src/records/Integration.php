<?php

namespace white\commerce\sendcloud\records;

use craft\db\ActiveRecord;

/**
 * @property int $siteId
 * @property string $token
 * @property int $externalId
 * @property string $publicKey
 * @property string $secretKey
 * @property string $system
 * @property string $shopUrl
 * @property string $webhookUrl
 * @property bool $servicePointEnabled
 * @property array $servicePointCarriers
 */
class Integration extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%sendcloud_integrations}}';
    }
}
