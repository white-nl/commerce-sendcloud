<?php

namespace white\commerce\sendcloud\client;

use craft\base\Element;
use craft\commerce\base\PurchasableInterface;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use GuzzleHttp\Exception\RequestException;
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

    /**
     * Creates a parcel in Sendcloud.
     *
     * @param Address $shippingAddress Address to be shipped to.
     * @param int|null $servicePointId The order will be shipped to the service point if supplied. $shippingAddress is
     * still required as it will be printed on the label.
     * @param string|null $orderNumber
     * @param int|null $weight Weight of the parcel in grams. The default set in Sendcloud will be used if null or zero.
     * @param Order|null $order
     * @return Parcel
     * @throws SendCloudRequestException
     */
    public function createParcel(
        Address $shippingAddress,
        ?int $servicePointId,
        ?string $orderNumber = null,
        ?int $weight = null,
        ?Order $order = null
    ): Parcel {
        
        if ($order && $weight === null) {
            //$weight = $order->getTotalWeight();
            $weight = 0;
            foreach ($order->getLineItems() as $item) {
                $weight += $item->weight > 0 ? $item->weight : 1;
            }
        }
        
        $parcelData = $this->getParcelData(
            null,
            $shippingAddress,
            $servicePointId,
            $orderNumber,
            $weight,
            false,
            null,
            null
        );
        if ($order) {
            $parcelData['parcel_items'] = $this->getNormalizedOrderItems($order);
        }
        try {
            $response = $this->guzzleClient->post('parcels', [
                'json' => [
                    'parcel' => $parcelData,
                ],
            ]);

            return new Parcel(json_decode((string)$response->getBody(), true)['parcel']);
        } catch (RequestException $exception) {
            throw $this->parseRequestException($exception, 'Could not create parcel in Sendcloud.');
        }
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function getNormalizedOrderItems(Order $order)
    {
        $normalizedData = [];
        foreach($order->getLineItems() as $item) {
            $purchasable = $item->getPurchasable();
            
            $itemData = new ParcelItem([
                "description" => $item->getDescription() ?? $purchasable->getDescription(),
                "quantity" => $item->qty,
                "weight" => $item->weight > 0 ? $item->weight : 1,
                "sku" => $item->getSku() ?? $purchasable->getSku(),
                "value" => $item->getPrice(),
            ]);
            
            $settings = SendcloudPlugin::getInstance()->getSettings();
            if ($settings->hsCodeFieldHandle) {
                $itemData->hsCode = $this->tryGetProductField($purchasable, $settings->hsCodeFieldHandle);
            }
            if ($settings->originCountryFieldHandle) {
                $itemData->originCountry = $this->tryGetProductField($purchasable, $settings->originCountryFieldHandle);
            }
            
            $normalizedData[] = $itemData->toArray();
        }
        
        return $normalizedData;
    }
    
    protected function tryGetProductField(PurchasableInterface $purchasable, $fieldHandle)
    {
        if ($purchasable instanceof Element) {
            if ($purchasable->getFieldLayout()->isFieldIncluded($fieldHandle)) {
                return $purchasable->getFieldValue($fieldHandle);
            } elseif ($purchasable instanceof Variant) {
                $product = $purchasable->getProduct();
                if ($product->getFieldLayout()->isFieldIncluded($fieldHandle)) {
                    return $product->getFieldValue($fieldHandle);
                }
            }
        }
        
        return null;
    }

}
