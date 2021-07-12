<?php

namespace white\commerce\sendcloud\models;

use Craft;
use craft\base\Model;
use craft\commerce\Plugin as CommercePlugin;

class Settings extends Model
{
    /**
     * @var string
     */
    public $pluginNameOverride;

    /**
     * @var array
     */
    public $orderStatusesToPush = [];

    /**
     * @var array
     */
    public $orderStatusesToCreateLabel = [];

    /**
     * @var array
     */
    public $orderStatusMapping = [];
    
    public $hsCodeFieldHandle;
    
    public $originCountryFieldHandle;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    public function rules()
    {
        return [
            ['pluginNameOverride', 'default', 'value' => Craft::t('commerce-sendcloud', "Sendcloud")],
            ['orderStatusesToPush', 'default', 'value' => []],
            ['orderStatusesToCreateLabel', 'default', 'value' => []],
            ['orderStatusMapping', 'default', 'value' => []],
        ];
    }

    /**
     * Get all Craft Commerce order statuses
     *
     * @return array
     */
    public function getOrderStatuses(): array
    {
        $orderStatuses = CommercePlugin::getInstance()->getOrderStatuses()->getAllOrderStatuses();
        $options = [];
        foreach ($orderStatuses as $orderStatus) {
            $options[] = ['label' => $orderStatus->name, 'value' => $orderStatus->handle];
        }
        
        return $options;
    }

    /**
     * Get all SendCloud statuses
     *
     * @return array
     */
    public function getSendCloudStatuses(): array
    {
        $options = [];
        foreach (OrderSyncStatus::STATUSES as $value => $label) {
            $options[] = ['value' => $value, 'label' => sprintf('%d: %s', $value, $label)];
        }
        
        return $options;
    }

    public function getAvailableTextFields()
    {
        $options = [
            ['label' => null, 'value' => null]
        ];
        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            $options[] = ['label' => $field->name, 'value' => $field->handle];
        }
        
        return $options;
    }

    /**
     * Check if any order status change mapping is configured
     *
     * @return bool
     */
    public function canChangeOrderStatus(): bool
    {
        return sizeof($this->orderStatusMapping);
    }
}
