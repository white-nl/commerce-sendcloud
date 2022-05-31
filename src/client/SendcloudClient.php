<?php

namespace white\commerce\sendcloud\client;

use craft\commerce\elements\Order;
use craft\commerce\errors\CurrencyException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use JouwWeb\SendCloud\Client;
use JouwWeb\SendCloud\Exception\SendCloudRequestException;
use JouwWeb\SendCloud\Model\Address;
use JouwWeb\SendCloud\Model\Parcel;
use yii\base\InvalidConfigException;

/**
 * Client to perform calls on the Sendcloud API.
 */
class SendcloudClient extends Client
{
    /**
     * @param Address $shippingAddress
     * @param int|null $servicePointId
     * @param string|null $orderNumber
     * @param int|null $weight
     * @param string|null $customsInvoiceNumber
     * @param int|null $customsShipmentType
     * @param array|null $items
     * @param string|null $postNumber
     * @param Order|null $order
     * @return Parcel
     * @throws GuzzleException
     * @throws SendCloudRequestException
     * @throws \JsonException
     * @throws CurrencyException
     * @throws InvalidConfigException
     */
    public function createParcel(
        Address $shippingAddress,
        ?int $servicePointId,
        ?string $orderNumber = null,
        ?int $weight = null,
        ?string $customsInvoiceNumber = null,
        ?int $customsShipmentType = null,
        ?array $items = null,
        ?string $postNumber = null,
        ?Order $order = null,
    ): Parcel {
        $parcelData = $this->getParcelData(
            null,
            $shippingAddress,
            (string)$servicePointId,
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

            return new Parcel(json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR)['parcel']);
        } catch (TransferException $exception) {
            throw $this->parseGuzzleException($exception, 'Could not create parcel in Sendcloud.');
        }
    }
}
