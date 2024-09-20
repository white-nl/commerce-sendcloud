<?php

namespace white\commerce\sendcloud\models;

use craft\base\Model;
use craft\commerce\base\HasStoreInterface;
use craft\commerce\base\StoreTrait;
use craft\commerce\Plugin;
use white\commerce\sendcloud\enums\ParcelStatus;

class StatusMapping extends Model implements HasStoreInterface
{
    use StoreTrait;
    public ?int $id = null;

    public array $orderStatusesToPush = [];

    public array $orderStatusesToCreateLabel = [];

    public array $orderStatusMapping = [];

    public string $orderNumberFormat = '{{ order.id }}';

    public function getOrderStatuses(): array
    {
        $store = $this->getStore();
        $orderStatuses = Plugin::getInstance()->getOrderStatuses()->getAllOrderStatuses($store->id);
        $options = [];
        foreach ($orderStatuses as $orderStatus) {
            $options[] = ['label' => $orderStatus->name, 'value' => $orderStatus->handle];
        }

        return $options;
    }

    public function getSendcloudStatuses(): array
    {
        $options = [];
        foreach (ParcelStatus::cases() as $parcelStatus) {
            $options[] = ['label' => $parcelStatus->getLabel(), 'value' => $parcelStatus->value];
        }

        return $options;
    }

    protected function defineRules(): array
    {
        return [
            ['orderStatusesToPush', 'default', 'value' => []],
            ['orderStatusesToCreateLabel', 'default', 'value' => []],
            ['orderStatusMapping', 'default', 'value' => []],
            ['orderNumberFormat', 'required'],
            [['id', 'storeId'], 'safe'],
        ];
    }

    public function getConfig(): array
    {
        return [
            'orderStatusesToPush' => $this->orderStatusesToPush,
            'orderStatusesToCreateLabel' => $this->orderStatusesToCreateLabel,
            'orderStatusMapping' => $this->orderStatusMapping,
            'orderNumberFormat' => $this->orderNumberFormat,
            'store' => $this->getStore()->uid,
        ];
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