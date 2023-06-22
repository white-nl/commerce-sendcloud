<?php

namespace white\commerce\sendcloud\client;

use Craft;
use craft\base\Element;
use craft\commerce\base\PurchasableInterface;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\commerce\models\LineItem;
use craft\commerce\Plugin as CommercePlugin;
use craft\errors\InvalidFieldException;
use craft\helpers\ArrayHelper;
use GuzzleHttp\Exception\GuzzleException;
use JouwWeb\SendCloud\Exception\SendCloudClientException;
use JouwWeb\SendCloud\Exception\SendCloudRequestException;
use JouwWeb\SendCloud\Model\Address;
use JouwWeb\SendCloud\Model\ParcelItem;
use JouwWeb\SendCloud\Model\ShippingMethod;
use Throwable;
use white\commerce\sendcloud\client\SendcloudClient as Client;
use white\commerce\sendcloud\models\Parcel;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\base\InvalidConfigException;

final class JouwWebSendcloudAdapter implements SendcloudInterface
{
    private ?array $sendcloudShippingMethods = null;

    /**
     * JouwWebSendCloudAdapter constructor.
     * @param Client $client
     */
    public function __construct(private Client $client)
    {
    }

    /**
     * @param int $parcelId
     * @return Parcel
     * @throws SendCloudClientException
     */
    public function getParcel(int $parcelId): Parcel
    {
        $parcel = $this->client->getParcel($parcelId);
        
        return (new JouwWebParcelNormalizer())->getParcel($parcel);
    }

    /**
     * @param Order $order
     * @param int|null $servicePointId
     * @param int|null $weight
     * @return Parcel
     * @throws SendCloudRequestException
     * @throws GuzzleException
     * @throws Throwable
     */
    public function createParcel(Order $order, ?int $servicePointId = null, ?int $weight = null): Parcel
    {
        $address = $this->createAddress($order);

        if ($weight === null) {
            $weight = 0;
            foreach ($order->getLineItems() as $item) {
                $weight += ($item->qty * $this->getLineItemWeightGrams($item));
            }
        }
        
        $items = [];
        foreach ($order->getLineItems() as $item) {
            $purchasable = $item->getPurchasable();
            
            $parcelItem = new ParcelItem(
                !empty($item->getDescription()) ? $item->getDescription() : $purchasable->getDescription(),
                $item->qty,
                $this->getLineItemWeightGrams($item),
                $item->getPrice(),
                null,
                null,
                !empty($item->getSku()) ? $item->getSku() : $purchasable->getSku()
            );

            $settings = SendcloudPlugin::getInstance()->getSettings();
            if ($settings->hsCodeFieldHandle) {
                $parcelItem->setHarmonizedSystemCode($this->tryGetProductField($purchasable, $settings->hsCodeFieldHandle));
            }
            if ($settings->originCountryFieldHandle) {
                $parcelItem->setOriginCountryCode($this->tryGetProductField($purchasable, $settings->originCountryFieldHandle));
            }

            $items[] = $parcelItem;
        }

        $orderNumberTemplate = SendcloudPlugin::getInstance()->getSettings()->orderNumberFormat;

        try {
            $vars = array_merge(['order' => $order]);
            $orderNumber = Craft::$app->getView()->renderString($orderNumberTemplate, $vars);
        } catch (Throwable $exception) {
            Craft::error('Unable to generate Sendcloud order reference for Order ID: ' . $order->getId() . ', with format: ' . $orderNumberTemplate . ', error: ' . $exception->getMessage());
            throw $exception;
        }
        
        $parcel = $this->client->createParcel(
            $address,
            $servicePointId,
            $orderNumber,
            $weight,
            $order->reference,
            \JouwWeb\SendCloud\Model\Parcel::CUSTOMS_SHIPMENT_TYPE_COMMERCIAL_GOODS,
            $items,
            null,
            $order
        );

        return (new JouwWebParcelNormalizer())->getParcel($parcel);
    }

    /**
     * @param int $parcelId
     * @param Order $order
     * @return Parcel
     * @throws SendCloudRequestException
     */
    public function updateParcel(int $parcelId, Order $order): Parcel
    {
        $address = $this->createAddress($order);
        $parcel = $this->client->updateParcel($parcelId, $address);

        return (new JouwWebParcelNormalizer())->getParcel($parcel);
    }

    /**
     *
     * @param int $parcelId
     * @param Order $order
     * @return Parcel
     * @throws SendCloudClientException
     * @throws SendCloudRequestException
     */
    public function createLabel(Order $order, int $parcelId): Parcel
    {
        $shippingMethods = $this->getShippingMethods();
        if (!array_key_exists($order->shippingMethodName, $shippingMethods)) {
            throw new \RuntimeException("Could not find Sendcloud shipping method '{$order->shippingMethodName}'.");
        }
        
        $shippingMethodId = $shippingMethods[$order->shippingMethodName]->getId();
        
        $parcel = $this->client->getParcel($parcelId);
        $parcel = $this->client->createLabel($parcel, $shippingMethodId, null);
        
        return (new JouwWebParcelNormalizer())->getParcel($parcel);
    }

    /**
     * @return array
     */
    public function getShippingMethods(): array
    {
        if (!$this->sendcloudShippingMethods) {
            $this->sendcloudShippingMethods = Craft::$app->getCache()->getOrSet('sendcloud-shipping-methods', function() {
                $sendcloudShippingMethods = $this->client->getShippingMethods();
                return ArrayHelper::map(
                    $sendcloudShippingMethods,
                    static fn(ShippingMethod $method) => $method->getName(),
                    static fn(ShippingMethod $method) => $method,
                );
            }, 3600);
        }

        return $this->sendcloudShippingMethods;
    }

    /**
     * @param int $parcelId
     * @param int $format
     * @return string
     * @throws SendCloudClientException
     */
    public function getLabelPdf(int $parcelId, int $format): string
    {
        return $this->client->getLabelPdf($parcelId, $format);
    }

    /**
     * @param array $parcelIds
     * @param int $format
     * @return string
     * @throws SendCloudClientException
     */
    public function getLabelsPdf(array $parcelIds, int $format): string
    {
        return $this->client->getBulkLabelPdf($parcelIds, $format);
    }

    /**
     * @param int $parcelId
     * @return string|null
     */
    public function getReturnPortalUrl(int $parcelId): ?string
    {
        return $this->client->getReturnPortalUrl($parcelId);
    }

    /**
     * @param LineItem $lineItem
     * @return float|int
     * @throws \Exception
     */
    protected function getLineItemWeightGrams(LineItem $lineItem): float|int
    {
        $weight = $lineItem->weight;
        if ($weight <= 0) {
            return 1;
        }
        
        $units = CommercePlugin::getInstance()->getSettings()->weightUnits;
        return match ($units) {
            'g' => $weight,
            'kg' => $weight * 1000,
            'lb' => $weight * 453.592,
            default => throw new \Exception("Unsupported Craft weight units: '{$units}'."),
        };
    }

    /**
     * @param Order $order
     * @return Address
     * @throws InvalidConfigException|InvalidFieldException
     */
    protected function createAddress(Order $order): Address
    {
        /** @var \craft\elements\Address $shippingAddress */
        $shippingAddress = $order->getShippingAddress();
        $settings = SendcloudPlugin::getInstance()->getSettings();
        if ($settings->phoneNumberFieldHandle) {
            $phoneNumber = $shippingAddress->getFieldValue($settings->phoneNumberFieldHandle);
        }
        return new Address(
            $shippingAddress->fullName ?: $shippingAddress->getGivenName() . ' ' . $shippingAddress->getFamilyName(),
            $shippingAddress->getOrganization(),
            $shippingAddress->getAddressLine1(),
            trim($shippingAddress->getAddressLine2()),
            $shippingAddress->getLocality(),
            $shippingAddress->getPostalCode(),
            $shippingAddress->getCountryCode(),
            $order->getEmail(),
            $phoneNumber ?? null,
            '',
            $shippingAddress->getAdministrativeArea()
        );
    }

    /**
     * @throws InvalidFieldException
     * @throws InvalidConfigException
     */
    protected function tryGetProductField(PurchasableInterface $purchasable, $fieldHandle)
    {
        if ($purchasable instanceof Element) {
            if ($purchasable->getFieldLayout()->isFieldIncluded($fieldHandle)) {
                return $purchasable->getFieldValue($fieldHandle);
            }

            if ($purchasable instanceof Variant) {
                $product = $purchasable->getProduct();
                if ($product->getFieldLayout()->isFieldIncluded($fieldHandle)) {
                    return $product->getFieldValue($fieldHandle);
                }
            }
        }

        return null;
    }
}
