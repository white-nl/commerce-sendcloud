<?php

namespace white\commerce\sendcloud\services;

use craft\commerce\models\LineItem;
use craft\commerce\Plugin;
use white\commerce\sendcloud\events\ParcelItemEvent;
use white\commerce\sendcloud\models\ParcelItem;
use yii\base\Component;

class ParcelItems extends Component
{
    /**
     * @event ParcelItemEvent The event that is triggered after a parcel item has been created from a line item
     *
     * ```php
     * use white\commerce\sendcloud\events\ParcelItemEvent;
     * use white\commerce\sendcloud\services\ParcelItems;
     * use yii\base\Event;
     *
     * Event::on(
     *     ParcelItems::class,
     *     ParcelItems:EVENT_CREATE_PARCEL_ITEM,
     *     function(ParcelItemEvent $event): void {
     *         // @var ParcelItem $parcelItem
     *         $parcelItem = $event->parcelItem;
     *         // @var LineItem $lineItem
     *         $lineItem = $event->lineItem;
     *
     *
     *     }
     * );
     * ```
     */
    public const EVENT_CREATE_PARCEL_ITEM = 'createParcelItem';

    public function createFromLineItem(LineItem $lineItem, array $params = []): ParcelItem
    {
        $params = array_merge([
            'weight' => $this->_getLineItemWeightInKg($lineItem),
            'description' => $lineItem->getDescription(),
            'quantity' => $lineItem->qty,
            'value' => $lineItem->getPrice(),
            'sku' => $lineItem->sku,
        ], $params);
        $parcelItem = \Craft::createObject(ParcelItem::class, $params);

        if ($this->hasEventHandlers(self::EVENT_CREATE_PARCEL_ITEM)) {
            $this->trigger(self::EVENT_CREATE_PARCEL_ITEM, new ParcelItemEvent([
                'parcelItem' => $parcelItem,
                'lineItem' => $lineItem,
            ]));
        }

        return $parcelItem;
    }

    private function _getLineItemWeightInKg(LineItem $lineItem): float
    {
        $weight = $lineItem->weight;
        if ($weight <= 0) {
            return 0.001;
        }

        $weightUnit = Plugin::getInstance()->getSettings()->weightUnits;
        return match ($weightUnit) {
            'g' => $weight * 1000,
            'lb' => $weight * 0.453,
            default => $weight,
        };
    }
}
