<?php

namespace white\commerce\sendcloud\client;

use craft\base\Element;
use craft\commerce\base\PurchasableInterface;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use JouwWeb\SendCloud\Client;
use JouwWeb\SendCloud\Model\Parcel;
use JouwWeb\SendCloud\Model\Address;
use JouwWeb\SendCloud\Exception\SendCloudRequestException;
use white\commerce\sendcloud\models\ParcelItem;
use white\commerce\sendcloud\SendcloudPlugin;

/**
 * Client to perform calls on the Sendcloud API.
 */
class SendcloudClient extends Client
{
    public function createParcel(
        Address $shippingAddress,
        ?int $servicePointId,
        ?string $orderNumber = null,
        ?int $weight = null,
        ?string $customsInvoiceNumber = null,
        ?int $customsShipmentType = null,
        ?array $items = null,
        ?string $postNumber = null,
        Order $order = null
    ): Parcel {
        $parcelData = $this->getParcelData(
            null,
            $shippingAddress,
            $servicePointId,
            $orderNumber,
            $weight,
            false,
            null,
            null,
            $customsInvoiceNumber,
            $customsShipmentType,
            $items,
            $postNumber
        );

        if ($order) {
            $parcelData['total_order_value'] = $order->getTotalPrice();
            $parcelData['total_order_value_currency'] = $order->getPaymentCurrency();
        }

        try {
            $response = $this->guzzleClient->post('parcels', [
                'json' => [
                    'parcel' => $parcelData,
                ],
            ]);

            return new Parcel(json_decode((string)$response->getBody(), true)['parcel']);
        } catch (TransferException $exception) {
            throw $this->parseGuzzleException($exception, 'Could not create parcel in Sendcloud.');
        }
    }

}
