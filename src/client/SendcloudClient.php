<?php

namespace white\commerce\sendcloud\client;

use CommerceGuys\Addressing\Country\CountryRepository;
use Craft;
use craft\base\Element;
use craft\commerce\base\PurchasableInterface;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\commerce\Plugin as Commerce;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use Illuminate\Support\Collection;
use white\commerce\sendcloud\enums\LabelFormat;
use white\commerce\sendcloud\events\AddressEvent;
use white\commerce\sendcloud\events\ParcelEvent;
use white\commerce\sendcloud\exception\SendcloudRequestException;
use white\commerce\sendcloud\exception\SendcloudStateException;
use white\commerce\sendcloud\models\Address;
use white\commerce\sendcloud\models\Integration;
use white\commerce\sendcloud\models\OrderSyncStatus;
use white\commerce\sendcloud\models\Parcel;
use white\commerce\sendcloud\models\ShippingMethod;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\base\Component;

/**
 * Client to perform calls on the Sendcloud API.
 */
class SendcloudClient extends Component
{
    protected const API_BASE_URL = 'https://panel.sendcloud.sc/api/v2/';

    protected Client $guzzleClient;

    private ?array $sendcloudShippingMethods = null;

    /**
     * @var string Event emitted before the Sendcloud address is created
     */
    public const EVENT_AFTER_CREATE_ADDRESS = 'afterCreateAddress';

    /** @var string Event emitted before the Sendcloud parcel is pushed */
    public const EVENT_BEFORE_PUSH_PARCEL = 'beforePushParcel';

    /**
     * SendcloudClient constructor.
     * @param string $publicKey
     * @param string $secretKey
     * @param string|null $partnerId
     * @param string|null $apiBaseUrl
     */
    public function __construct(
        protected string $publicKey,
        protected string $secretKey,
        protected ?string $partnerId = null,
        ?string $apiBaseUrl = null,
    ) {
        $clientConfig = [
            'base_uri' => $apiBaseUrl ?: self::API_BASE_URL,
            'timeout' => 60,
            'auth' => [
                $publicKey,
                $secretKey,
            ],
            'headers' => [
                'User-Agent' => 'white-nl/commerce-sendcloud ' . Utils::defaultUserAgent(),
            ],
        ];

        if ($partnerId) {
            $clientConfig['headers']['Sendcloud-Partner-Id'] = $partnerId;
        }

        $this->guzzleClient = new Client($clientConfig);
    }

    /**
     * Update the sendcloud integration
     * @param Integration $integration
     * @param string $shopName
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateIntegration(Integration $integration, string $shopName): bool
    {
        try {
            $this->guzzleClient->put("integrations/{$integration->externalId}", [
                RequestOptions::JSON => [
                    'shop_name' => $shopName,
                    'shop_url' => $integration->shopUrl,
                    'webhook_url' => $integration->webhookUrl,
                ],
            ]);
            return true;
        } catch (TransferException $exception) {
            throw (new SendcloudRequestException())->parseGuzzleException($exception, Craft::t('commerce-sendcloud', 'Failed to update integration'));
        }
    }

    /**
     * Removes the Sendcloud integration
     * @param int $integrationId
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function removeIntegration(int $integrationId): bool
    {
        try {
            $this->guzzleClient->delete('integrations/' . $integrationId);
            return true;
        } catch (TransferException $exception) {
            throw (new SendcloudRequestException())->parseGuzzleException($exception, Craft::t('commerce-sendcloud', 'Failed to remove integration'));
        }
    }

    /**
     * @param int $storeId
     * @return ShippingMethod[]
     */
    public function getShippingMethods(int $storeId): array
    {
        if (!$this->sendcloudShippingMethods) {
            $this->sendcloudShippingMethods = \Craft::$app->getCache()->getOrSet("sendcloud-shipping-methods-$storeId", function() {
                $response = $this->guzzleClient->get('shipping_methods');
                $shippingMethodsData = Json::decodeIfJson($response->getBody(), true)['shipping_methods'];

                $shippingMethods = array_map(fn(array $shippingMethodData) => (
                    ShippingMethod::fromArray($shippingMethodData)
                ), $shippingMethodsData);

                // Sort shipping methods by carrier and name
                usort($shippingMethods, function(ShippingMethod $shippingMethod1, ShippingMethod $shippingMethod2) {
                    if ($shippingMethod1->getCarrier() !== $shippingMethod2->getCarrier()) {
                        return strcasecmp($shippingMethod1->getCarrier(), $shippingMethod2->getCarrier());
                    }

                    return strcasecmp($shippingMethod1->getName(), $shippingMethod2->getName());
                });

                return ArrayHelper::map(
                    $shippingMethods,
                    static fn(ShippingMethod $shippingMethod) => $shippingMethod->getName(),
                    static fn(ShippingMethod $shippingMethod) => $shippingMethod,
                );
            }, 3600);
        }

        return $this->sendcloudShippingMethods;
    }

    /**
     * Get a Sendcloud parcel by ID
     * @param int $parcelId
     * @return Parcel
     * @throws SendcloudRequestException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getParcel(int $parcelId): Parcel
    {
        try {
            $response = $this->guzzleClient->get("parcels/$parcelId");
            return Parcel::fromData(Json::decodeIfJson($response->getBody())['parcel']);
        } catch (TransferException $exception) {
            throw (new SendcloudRequestException())->parseGuzzleException($exception, Craft::t('commerce-sendcloud', 'Failed to get Parcel'));
        }
    }

    /**
     * Create a Sendcloud parcel
     * @param Order $order
     * @param int|null $servicePointId
     * @return Parcel
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     */
    public function createParcel(Order $order, ?int $servicePointId = null): Parcel
    {
        $parcel = $this->_createParcelData($order, $servicePointId);
        try {
            $response = $this->guzzleClient->post('parcels', [
                RequestOptions::JSON => [
                    'parcel' => $parcel,
                ],
            ]);

            return Parcel::fromData(Json::decodeIfJson($response->getBody())['parcel']);
        } catch (TransferException $exception) {
            throw (new SendcloudRequestException())->parseGuzzleException($exception, Craft::t('commerce-sendcloud', 'Failed to create Parcel'));
        }
    }

    /**
     * Update a Sendcloud parcel
     * @param OrderSyncStatus $orderSyncStatus
     * @param Order $order
     * @return Parcel
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     */
    public function updateParcel(OrderSyncStatus $orderSyncStatus, Order $order): Parcel
    {
        $parcelId = $orderSyncStatus->parcelId;
        $parcel = $this->_createParcelData($order, $orderSyncStatus->servicePointId);

        try {
            $parcel['id'] = $parcelId;
            $response = $this->guzzleClient->put('parcels', [
                RequestOptions::JSON => [
                    'parcel' => $parcel,
                ],
            ]);

            return Parcel::fromData(Json::decodeIfJson($response->getBody())['parcel']);
        } catch (TransferException $exception) {
            throw (new SendcloudRequestException())->parseGuzzleException($exception, Craft::t('commerce-sendcloud', 'Failed to update Parcel'));
        }
    }

    /**
     * Create a shipping label for a parcel
     * @param Order $order
     * @param int $parcelId
     * @return Parcel
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     */
    public function createLabel(Order $order, int $parcelId): Parcel
    {
        $store = $order->getStore();
        $shippingMethods = $this->getShippingMethods($store->id);
        if (!array_key_exists($order->shippingMethodName, $shippingMethods)) {
            throw new \RuntimeException(\Craft::t('commerce-sendcloud', "Could not find Sendcloud shipping method '{method}'", ['method' => $order->shippingMethodName]));
        }
        $shippingMethodId = $shippingMethods[$order->shippingMethodName]->getId();
        $parcel = $this->_createParcelData($order, $shippingMethodId, requestLabel: true);
        $parcel['id'] = $parcelId;

        try {
            $response = $this->guzzleClient->put('parcels', [
                RequestOptions::JSON => [
                    'parcel' => $parcel,
                ],
            ]);

            return Parcel::fromData(Json::decodeIfJson($response->getBody())['parcel']);
        } catch (TransferException $exception) {
            throw (new SendcloudRequestException())->parseGuzzleException($exception, Craft::t('commerce-sendcloud', 'Failed to create Label'));
        }
    }

    /**
     * Get the shipping label in PDF format
     * @param Parcel|int $parcel
     * @param LabelFormat|null $format
     * @return string
     * @throws SendcloudRequestException
     * @throws SendcloudStateException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLabelPdf(Parcel|int $parcel, ?LabelFormat $format = null): string
    {
        if (is_int($parcel)) {
            $parcel = $this->getParcel($parcel);
        }

        if ($format === null) {
            $settings = SendcloudPlugin::getInstance()->getSettings();
            $format = $settings->getLabelFormat();
        }

        $labelUrl = $parcel->getLabelUrl($format);

        if (!$labelUrl) {
            throw new SendcloudStateException(Craft::t('commerce-sendcloud', 'Sendcloud parcel does not have any labels.'));
        }

        try {
            return (string)$this->guzzleClient->get($labelUrl)->getBody();
        } catch (TransferException $exception) {
            throw (new SendcloudRequestException())->parseGuzzleException($exception, Craft::t('commerce-sendcloud', 'Failed to get label PDF'));
        }
    }

    /**
     * @param array<int|Parcel> $parcels
     * @param LabelFormat|null $format
     * @return string
     * @throws SendcloudRequestException
     * @throws SendcloudStateException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLabelsPdf(array $parcels, ?LabelFormat $format = null): string
    {
        $parcelIds = [];
        foreach ($parcels as $parcel) {
            $parcelIds[] = is_int($parcel) ? $parcel : $parcel->getId();
        }

        try {
            $response = $this->guzzleClient->post('labels', [
                RequestOptions::JSON => [
                    'label' => [
                        'parcels' => $parcelIds,
                    ],
                ],
            ]);
        } catch (TransferException $exception) {
            throw (new SendcloudRequestException())->parseGuzzleException($exception, Craft::t('commerce-sendcloud', "Failed to get label PDF's"));
        }

        if ($format === null) {
            $settings = SendcloudPlugin::getInstance()->getSettings();
            $format = $settings->getLabelFormat();
        }

        $labelData = Json::decodeIfJson($response->getBody());
        $labelUrl = $format->getUrl($labelData);
        if (!$labelUrl) {
            throw new SendcloudStateException('No label URL could be obtained from the response.');
        }

        try {
            return (string)$this->guzzleClient->get($labelUrl)->getBody();
        } catch (TransferException $exception) {
            throw (new SendcloudRequestException())->parseGuzzleException($exception, Craft::t('commerce-sendcloud', 'Failed to get label PDF'));
        }
    }

    public function getReturnPortalUrl(Parcel|int $parcel): ?string
    {
        try {
            $parcelId = is_int($parcel) ? $parcel : $parcel->getId();

            $response = $this->guzzleClient->get("parcels/$parcelId/return_portal_url");
            return Json::decodeIfJson($response->getBody())['url'];
        } catch (RequestException $exception) {
            if ($exception->getResponse() && $exception->getResponse()->getStatusCode() === 404) {
                return null;
            }

            throw $exception;
        }
    }

    private function _createParcelData(Order $order, ?int $servicePointId = null, bool $requestLabel = false): array
    {
        $store = $order->getStore();
        $settings = SendcloudPlugin::getInstance()->getSettings();
        $address = $this->_createAddress($order);

        $weight = $this->_getOrderWeightInKg($order);

        $items = [];
        $parcelItems = SendcloudPlugin::getInstance()->parcelItems;
        foreach ($order->getLineItems() as $lineItem) {
            $purchasable = $lineItem->getPurchasable();

            if ($settings->hsCodeFieldHandle) {
                $hsSystemCode = $this->_tryGetProductField($purchasable, $settings->hsCodeFieldHandle);
            }
            if ($settings->originCountryFieldHandle) {
                $originCountryCode = $this->_tryGetProductField($purchasable, $settings->originCountryFieldHandle);
            }

            $params = [
                'hsCode' => $hsSystemCode ?? null,
                'originCountry' => $originCountryCode ?? null,
            ];

            $items[] = $parcelItems->createFromLineItem($lineItem, $params);
        }

        $statusMapping = SendcloudPlugin::getInstance()->statusMapping->getStatusMappingByStoreId($store->id);
        $orderNumberTemplate = $statusMapping->orderNumberFormat;

        try {
            $vars = ['order' => $order];
            $orderNumber = \Craft::$app->getView()->renderString($orderNumberTemplate, $vars);
        } catch (\Throwable $exception) {
            Craft::error('Unable to generate Sendcloud order reference for Order ID: ' . $order->getId() . ', with format: ' . $orderNumberTemplate . ', error: ' . $exception->getMessage());
            throw $exception;
        }

        $parcel = \Craft::createObject(Parcel::class);
        $parcel->setApplyShippingRules($settings->isApplyShippingRules());
        $parcel->setRequestLabel($requestLabel);

        $parcel->setAddress($address);
        $parcel->setEmail($order->getEmail());
        $parcel->setOrderNumber($orderNumber);
        $parcel->setWeight($weight);
        $parcel->setParcelItems($items);

        $sendcloudShippingMethod = $this->getShippingMethods($store->id)[$order->shippingMethodName] ?? null;
        if ($sendcloudShippingMethod) {
            $parcel->setShippingMethod($sendcloudShippingMethod);
            $parcel->setShippingMethodCheckoutName($order->shippingMethodName);
            if ($sendcloudShippingMethod->isServicePointInputRequired()) {
                $parcel->setToServicePoint($servicePointId);
            }
        }

        $parcel->setTotalOrderValue((string)$order->getTotalPaid());
        $parcel->setTotalOrderValueCurrency($order->getPaymentCurrency());

        $parcelEvent = new ParcelEvent([
            'parcel' => $parcel,
            'order' => $order,
        ]);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_PUSH_PARCEL)) {
            $this->trigger(self::EVENT_BEFORE_PUSH_PARCEL, $parcelEvent);
        }

        return array_filter($parcel->toArray(), fn($value) => !is_null($value));
    }

    private function _createAddress(Order $order): Address
    {
        $shippingAddress = $order->getShippingAddress();
        $settings = SendcloudPlugin::getInstance()->getSettings();
        if ($settings->phoneNumberFieldHandle) {
            $phoneNumber = $shippingAddress->getFieldValue($settings->phoneNumberFieldHandle);
        }

        $locality = $shippingAddress->getLocality();
        $countryCode = $shippingAddress->getCountryCode();
        if ($locality === null) {
            $countryRepository = new CountryRepository();
            $country = $countryRepository->get($countryCode);
            $locality = $country->getName();
        }

        $administrativeArea = null;
        if ($shippingAddress->getAdministrativeArea()) {
            $administrativeAreas = new Collection(\Craft::$app->getAddresses()->getSubdivisionRepository()->getList([$countryCode]));
            $administrativeArea = $administrativeAreas->flip()->get($shippingAddress->getAdministrativeArea());
        }

        $address = new Address(
            $shippingAddress->fullName ?: $shippingAddress->getGivenName() . ' ' . $shippingAddress->getFamilyName(),
            $shippingAddress->getAddressLine1(),
            $locality,
            $shippingAddress->getPostalCode(),
            $countryCode,
            $shippingAddress->getOrganization(),
        $shippingAddress->getAddressLine2() ?? '',
            null,
        $phoneNumber ?? null,
            $administrativeArea,
        );

        $addressEvent = new AddressEvent([
            'shippingAddress' => $shippingAddress,
            'address' => $address,
        ]);

        if ($this->hasEventHandlers(self::EVENT_AFTER_CREATE_ADDRESS)) {
            $this->trigger(self::EVENT_AFTER_CREATE_ADDRESS, $addressEvent);
        }

        return $address;
    }

    private function _tryGetProductField(PurchasableInterface $purchasable, string $fieldHandle): ?string
    {
        if ($purchasable instanceof Element) {
            if ($purchasable->getFieldLayout()->isFieldIncluded($fieldHandle)) {
                return $purchasable->getFieldValue($fieldHandle);
            }

            if ($purchasable instanceof Variant) {
                $product = $purchasable->getOwner();
                if ($product?->getFieldLayout()->isFieldIncluded($fieldHandle)) {
                    return $product->getFieldValue($fieldHandle);
                }
            }
        }

        return null;
    }

    private function _getOrderWeightInKg(Order $order): ?string
    {
        $weight = $order->getTotalWeight();
        if ($weight <= 0) {
            return null;
        }

        $weightUnit = Commerce::getInstance()->getSettings()->weightUnits;
        $totalWeight = match ($weightUnit) {
            'g' => $weight * 1000,
            'lb' => $weight * 0.453,
            default => $weight,
        };

        return (string)$totalWeight;
    }
}
