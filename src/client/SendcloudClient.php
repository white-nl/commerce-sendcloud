<?php

namespace white\commerce\sendcloud\client;

use craft\commerce\elements\Order;
use craft\commerce\errors\CurrencyException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use JouwWeb\Sendcloud\Client;
use JouwWeb\Sendcloud\Exception\SendcloudRequestException;
use JouwWeb\Sendcloud\Model\Address;
use JouwWeb\Sendcloud\Model\Parcel;
use JouwWeb\Sendcloud\Model\ShippingMethod;
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
     * @param ShippingMethod|null $shippingMethod
     * @param string|null $errors
     * @param Order|null $order
     * @return Parcel
     * @throws GuzzleException
     * @throws SendcloudRequestException
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
        ShippingMethod|int|null $shippingMethod = null,
        ?string $errors = null,
        ?Order $order = null,
    ): Parcel {
        $requestLabel = $shippingMethod !== null;

        $parcelData = $this->getParcelData(
            null,
            $shippingAddress,
            (string)$servicePointId,
            $orderNumber,
            $weight,
            $requestLabel, // true to set the shipping method only if a shipping method is passed,
            $shippingMethod,
            null,
            $customsInvoiceNumber,
            $customsShipmentType,
            $items,
            $postNumber
        );

        // set back to false
        if ($requestLabel) {
            $parcelData['request_label'] = false;
        }

        if ($order) {
            $parcelData['total_order_value'] = $order->getTotalPrice();
            $parcelData['total_order_value_currency'] = $order->getPaymentCurrency();
            $parcelData['shipping_method_checkout_name'] = $order->getShippingMethod()->name;
        }

        try {
            $response = $this->guzzleClient->post('parcels', [
                'json' => [
                    'parcel' => $parcelData,
                ],
            ]);

            $parcel = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR)['parcel'];
            return Parcel::fromData($parcel);
        } catch (TransferException $exception) {
            throw $this->parseGuzzleException($exception, 'Could not create parcel in Sendcloud.');
        }
    }
}
