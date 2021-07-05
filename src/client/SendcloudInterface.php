<?php

namespace white\commerce\sendcloud\client;

use craft\commerce\elements\Order;
use white\commerce\sendcloud\models\Parcel;

interface SendcloudInterface
{
    public function getShippingMethods(): array;

    public function getParcel(int $parcelId): Parcel;

    public function getLabelPdf(int $parcelId, int $format): string;

    public function updateParcel(int $parcelId, Order $order): Parcel;

    public function createParcel(Order $order, ?int $servicePointId = null, ?int $weight = null): Parcel;

    public function createLabel(Order $order, int $parcelId): Parcel;

    public function getReturnPortalUrl(int $parcelId): ?string;
}
