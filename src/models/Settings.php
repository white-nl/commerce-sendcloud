<?php

namespace white\commerce\sendcloud\models;

use Craft;
use craft\base\Model;
use craft\commerce\Plugin as CommercePlugin;
use yii\base\InvalidConfigException;

/**
 *
 * @property-read array $orderStatuses
 * @property-read array $sendCloudStatuses
 * @property-read null[][] $availableTextFields
 */
class Settings extends Model
{
    /**
     * @var string
     */
    public string $pluginNameOverride = '';

    /**
     * @var array
     */
    public array $orderStatusesToPush = [];

    /**
     * @var array
     */
    public array $orderStatusesToCreateLabel = [];

    /**
     * @var array
     */
    public array $orderStatusMapping = [];
    
    public ?string $hsCodeFieldHandle = null;
    
    public ?string $originCountryFieldHandle = null;

    public ?string $phoneNumberFieldHandle = null;

    public string $orderReferenceFormat = '{{ id }}';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
    }

    public function rules(): array
    {
        return [
            ['pluginNameOverride', 'default', 'value' => Craft::t('commerce-sendcloud', "Sendcloud")],
            ['orderStatusesToPush', 'default', 'value' => []],
            ['orderStatusesToCreateLabel', 'default', 'value' => []],
            ['orderStatusMapping', 'default', 'value' => []],
            ['orderReferenceFormat', 'required'],
        ];
    }

    /**
     * Get all Craft Commerce order statuses
     * @return array
     * @throws InvalidConfigException
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

    /**
     * @return array
     */
    public function getAvailableTextFields(): array
    {
        $options = [
            ['label' => null, 'value' => null],
        ];
        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            $options[] = ['label' => $field->name, 'value' => $field->handle];
        }
        
        return $options;
    }

    /**
     * Check if any order status change mapping is configured
     * @return bool
     */
    public function canChangeOrderStatus(): bool
    {
        return !empty($this->orderStatusMapping);
    }
}
