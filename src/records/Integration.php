<?php

namespace white\commerce\sendcloud\records;

use craft\db\ActiveRecord;

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
