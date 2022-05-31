<?php

namespace white\commerce\sendcloud\client;

use craft\commerce\elements\Order;
use white\commerce\sendcloud\models\Parcel;

interface SendcloudInterface
{
    /**
     * @return array
     */
    public function getShippingMethods(): array;

    /**
     * @param int $parcelId
     * @return Parcel
     */
    public function getParcel(int $parcelId): Parcel;

    /**
     * @param int $parcelId
     * @param int $format
     * @return string
     */
    public function getLabelPdf(int $parcelId, int $format): string;

    /**
     * @param array $parcelIds
     * @param int $format
     * @return string
     */
    public function getLabelsPdf(array $parcelIds, int $format): string;

    /**
     * @param int $parcelId
     * @param Order $order
     * @return Parcel
     */
    public function updateParcel(int $parcelId, Order $order): Parcel;

    /**
     * @param Order $order
     * @param int|null $servicePointId
     * @param int|null $weight
     * @return Parcel
     */
    public function createParcel(Order $order, ?int $servicePointId = null, ?int $weight = null): Parcel;

    /**
     * @param Order $order
     * @param int $parcelId
     * @return Parcel
     */
    public function createLabel(Order $order, int $parcelId): Parcel;

    /**
     * @param int $parcelId
     * @return string|null
     */
    public function getReturnPortalUrl(int $parcelId): ?string;
}
