<?php

namespace white\commerce\sendcloud\client;

use CommerceGuys\Addressing\Country\CountryRepository;
use Craft;
use craft\base\Element;
use craft\commerce\base\PurchasableInterface;
use craft\commerce\elements\Order;
use craft\commerce\elements\Variant;
use craft\commerce\errors\CurrencyException;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
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
use white\commerce\sendcloud\models\ParcelItem;
use white\commerce\sendcloud\models\ShippingMethod;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\base\Component;
use yii\base\InvalidConfigException;

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

    public const EVENT_BEFORE_PUSH_PARCEL = 'beforePushParcel';

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

    public function updateIntegration(Integration $integration, string $shopName): bool
    {
        $response = $this->guzzleClient->put("integrations/{$integration->externalId}", [
            RequestOptions::JSON => [
                'shop_name' => $shopName,
                'shop_url' => $integration->shopUrl,
                'webhook_url' => $integration->webhookUrl,
            ],
        ]);

        if ($response->getStatusCode() !== 400) {

        }

        return true;
    }

    public function removeIntegration(int $integrationId): bool
    {
        $response = $this->guzzleClient->delete('integrations/' . $integrationId);
        if ($response->getStatusCode() !== 204) {

        }
        return true;
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

    public function getParcel(int $parcelId): Parcel
    {
        try {
            $response = $this->guzzleClient->get("parcels/$parcelId");
            return Parcel::fromData(Json::decodeIfJson($response->getBody(), true)['parcel']);
        } catch (TransferException $exception) {
            throw new \RuntimeException(Craft::t('commerce-sendcloud', 'Unable to find parcel'));
        }
    }

    public function createParcel(Order $order, ?int $servicePointId = null, ?int $weight = null): Parcel
    {
        $parcel = $this->_createParcelData($order, $servicePointId, $weight);
        $response = $this->guzzleClient->post('parcels', [
            RequestOptions::JSON => [
                'parcel' => $parcel,
            ],
        ]);

        return Parcel::fromData(Json::decodeIfJson($response->getBody(), true)['parcel']);
    }

    public function updateParcel(OrderSyncStatus $orderSyncStatus, Order $order): Parcel
    {
        $parcelId = $orderSyncStatus->parcelId;
        $parcel = $this->_createParcelData($order, $orderSyncStatus->servicePointId);
        $parcel['id'] = $parcelId;
        $response = $this->guzzleClient->put('parcels', [
            RequestOptions::JSON => [
                'parcel' => $parcel,
            ],
        ]);

        return Parcel::fromData(Json::decodeIfJson($response->getBody(), true)['parcel']);
    }

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

        $response = $this->guzzleClient->put('parcels', [
            RequestOptions::JSON => [
                'parcel' => $parcel,
            ],
        ]);

        return Parcel::fromData(Json::decodeIfJson($response->getBody(), true)['parcel']);
    }

    public function getLabelPdf(Parcel|int $parcel, LabelFormat $format): string
    {
        if (is_int($parcel)) {
            $parcel = $this->getParcel($parcel);
        }

        $labelUrl = $parcel->getLabelUrl($format);

        if (!$labelUrl) {
            throw new \Exception(Craft::t('commerce-sendcloud', 'Sendcloud parcel does not have any labels.'));
        }

        try {
            return (string)$this->guzzleClient->get($labelUrl)->getBody();
        } catch (TransferException $exception) {
            throw new \Exception(Craft::t('commerce-sendcloud', 'Could not retrieve label.'));
        }
    }

    public function getLabelsPdf(array $parcels, LabelFormat $format): string
    {
        $parcelIds = [];
        foreach ($parcels as $parcel) {
            if (is_int($parcel)) {
                $parcelIds[] = $parcel;
            } elseif ($parcel instanceof Parcel) {
                $parcelIds[] = $parcel->getId();
            }
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
            throw (new SendcloudRequestException)->parseGuzzleExceoption($exception, 'Could ot retrieve label information');
        }

        $labelData = Json::decodeIfJson($response->getBody(), true);
        $labelUrl = $format->getUrl($labelData);
        if (!$labelUrl) {
            throw new SendcloudStateException('No label URL could be obtained from the response.');
        }

        try {
            return (string)$this->guzzleClient->get($labelUrl)->getBody();
        } catch (TransferException $exception) {
            throw (new SendcloudRequestException)->parseGuzzleExceoption($exception, 'Could not retrieve label PDF data.');
        }

    }

    private function _createParcelData(Order $order, ?int $servicePointId = null, ?int $weight = null, bool $requestLabel = false): array
    {
        $store = $order->getStore();
        $settings = SendcloudPlugin::getInstance()->getSettings();
        $address = $this->_createAddress($order);

        $weight = $weight ?: $order->getTotalWeight();

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
        $orrderNumberTemplate = $statusMapping->orderNumberFormat;

        try {
            $vars = ['order' => $order];
            $orderNumber = \Craft::$app->getView()->renderString($orrderNumberTemplate, $vars);
        } catch (\Throwable $exception) {
            Craft::error('Unable to generate Sendcloud order reference for Order ID: ' . $order->getId() . ', with format: ' . $orderNumberTemplate . ', error: ' . $exception->getMessage());
            throw $exception;
        }

        $parcel = \Craft::createObject(Parcel::class);
        $parcel->setRequestLabel($requestLabel);

        if ($address) {
            $parcel->setAddress($address);
        }
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

        $parcel->setTotalOrderValue($order->getTotalPaid());
        $parcel->setTotalOrderValueCurrency($order->getPaymentCurrency());

        $parcelEvent = new ParcelEvent([
            'parcel' => $parcel,
            'order' => $order,
        ]);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_PUSH_PARCEL)) {
            $this->trigger(self::EVENT_BEFORE_PUSH_PARCEL, $parcelEvent);
        }

        return $parcel->toArray();
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
            $shippingAddress->getOrganization(),
            $shippingAddress->getAddressLine1(),
            $shippingAddress->getAddressLine2() ?? '',
            null,
            $locality,
            $shippingAddress->getPostalCode(),
            $phoneNumber ?? null,
            $countryCode,
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
}
