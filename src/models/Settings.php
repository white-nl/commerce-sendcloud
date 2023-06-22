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
     * @var string The plugin name as you'd like it to be displayed in the Control Panel.
     */
    public string $pluginNameOverride = '';

    /**
     * @var array Orders with these statuses will be pushed automatically to Sendcloud.
     */
    public array $orderStatusesToPush = [];

    /**
     * @var array Automatically create labels in Sendcloud for orders with these statuses.
     */
    public array $orderStatusesToCreateLabel = [];

    /**
     * @var array You can map specific Sendcloud parcel status to a craft order status. Order status will be updated automatically when the parcel status changes.
     */
    public array $orderStatusMapping = [];

    /**
     * @var string|null Select the Craft Commerce product field containing the HS product codes. HS codes are required for shipping outside the EU.
     */
    public ?string $hsCodeFieldHandle = null;

    /**
     * @var string|null Select the Craft Commerce product field containing the country of Origin. Use only ISO2 country codes!
     */
    public ?string $originCountryFieldHandle = null;

    /**
     * @var string|null Select the Craft field linked to the Address element containing the phone number
     */
    public ?string $phoneNumberFieldHandle = null;

    /**
     * @var string A friendly order number will be generated when the order is pushed to Sendcloud. For example '{{ order.id }}', or '{{ order.reference }}'
     *
     * @since 2.2.0
     */
    public string $orderNumberFormat = '{{ order.id }}';

    /**
     * @var int The priority to give the push order job (the lower the number, the higher the priority). Set to `null` to inherit the default priority.
     *
     * @since 2.2.0
     */
    public int $pushOrderJobPriority = 1024;

    /**
     * @var int The priority to give the create label job (the lower the number, the higher the priority). Set to `null` to inherit the default priority.
     *
     * @since 2.2.0
     */
    public int $createLabelJobPriority = 1024;

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
            ['orderNumberFormat', 'required'],
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
