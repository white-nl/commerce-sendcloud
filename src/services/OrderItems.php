<?php

namespace white\commerce\sendcloud\services;

use craft\commerce\models\LineItem;
use craft\commerce\Plugin;
use white\commerce\sendcloud\events\OrderItemEvent;
use white\commerce\sendcloud\models\OrderItem;
use white\commerce\sendcloud\models\Price;
use yii\base\Component;

class OrderItems extends Component
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
    public const EVENT_CREATE_ORDER_ITEM = 'createOrderItem';

    public function createFromLineItem(LineItem $lineItem, array $params = []): OrderItem
    {
        $totalPrice = new Price($lineItem->getTotal(), $lineItem->getOrder()->getPaymentCurrency());
        $params = array_merge($params, [
            'name' => $lineItem->getDescription(),
            'quantity' => $lineItem->qty,
            'totalPrice' => $totalPrice,
            'sku' => $lineItem->getSku(),
        ]);
        $measurements = null;
        if ($lineItem->weight) {
            $measurements['weight'] = [
                'weight' => $lineItem->weight,
                'unit' => Plugin::getInstance()->getSettings()->weightUnits,
            ];
        }
        if ($lineItem->length) {
            $measurements['dimension'] = [
                'length' => $lineItem->length,
                'width' => $lineItem->width,
                'height' => $lineItem->height,
                'unit' => Plugin::getInstance()->getSettings()->dimensionUnits,
            ];
        }
        $orderItem = \Craft::createObject(OrderItem::class, $params);
        $orderItem->setMeasurement($measurements);

        if ($this->hasEventHandlers(self::EVENT_CREATE_ORDER_ITEM)) {
            $this->trigger(self::EVENT_CREATE_ORDER_ITEM, new OrderItemEvent([
                'orderItem' => $orderItem,
                'lineItem' => $lineItem,
            ]));
        }

        return $orderItem;
    }
}
