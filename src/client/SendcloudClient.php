<?php

namespace white\commerce\sendcloud\client;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\models\Store;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use white\commerce\sendcloud\enums\LabelFormat;
use white\commerce\sendcloud\exception\SendcloudRequestException;
use white\commerce\sendcloud\exception\SendcloudStateException;
use white\commerce\sendcloud\models\Integration;
use white\commerce\sendcloud\models\Order as SendcloudOrder;
use white\commerce\sendcloud\models\OrderSyncStatus;
use white\commerce\sendcloud\models\ShippingOption;
use white\commerce\sendcloud\SendcloudPlugin;
use yii\base\Component;

/**
 * Client to perform calls on the Sendcloud API.
 */
class SendcloudClient extends Component
{
    protected const API_BASE_URL = 'https://panel.sendcloud.sc/api/v3/';
    protected const API_BASE_URL_V2 = 'https://panel.sendcloud.sc/api/v2/';

    protected Client $guzzleClient;

    private ?array $sendcloudShippingOptions = null;

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
            $this->guzzleClient->patch("integrations/{$integration->externalId}", [
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
     * @param Store $store
     * @return ShippingOption[]
     */
    public function getShippingOptions(Store $store): array
    {
        if (!$this->sendcloudShippingOptions) {
            $this->sendcloudShippingOptions = \Craft::$app->getCache()->getOrSet("sendcloud-shipping-options-$store->id", function() use ($store) {
                $storeCountry = $store->getSettings()->getLocationAddress()->getCountryCode();
                $response = $this->guzzleClient->post('shipping-options', [
                    'body' => Json::encode([
                        'from_address' => [
                            'country_code' => $storeCountry,
                        ],
                    ]),
                ]);
                $shippingOptionsData = Json::decodeIfJson($response->getBody(), true)['data'];

                $shippingOptions = array_map(fn(array $shippingOptionData) => (
                    ShippingOption::fromArray($shippingOptionData)
                ), $shippingOptionsData);

                // Sort shipping methods by carrier and name
                usort($shippingOptions, function(ShippingOption $shippingOption1, ShippingOption $shippingOption2) {
                    if ($shippingOption1->getCarrier() !== $shippingOption2->getCarrier()) {
                        return strcasecmp($shippingOption1->getCarrier(), $shippingOption1->getCarrier());
                    }

                    return strcasecmp($shippingOption1->getName(), $shippingOption2->getName());
                });

                return ArrayHelper::map(
                    $shippingOptions,
                    static fn(ShippingOption $shippingOption) => $shippingOption->getName(),
                    static fn(ShippingOption $shippingOption) => $shippingOption,
                );
            }, 3600);
        }

        return $this->sendcloudShippingOptions;
    }

    public function pushOrder(SendcloudOrder $order): bool
    {
        try {
            $this->guzzleClient->post('orders', [
                RequestOptions::JSON => [
                    $order->toArray(),
                ],
            ]);

            return true;
        } catch (TransferException $exception) {
            throw (new SendcloudRequestException())->parseGuzzleException($exception, Craft::t('commerce-sendcloud', 'Failed to push Order'));
        }
    }

    /**
     * Create a shipping label for a Order
     * @param Order $order
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     */
    public function createLabel(Order $order): ?array
    {
        $integration = SendcloudPlugin::getInstance()->integrations->getIntegrationByStoreId($order->storeId);
        $response = $this->createLabels(
            [$order->number],
            $integration->externalId,
            SendcloudPlugin::getInstance()->getSettings()->isApplyShippingRules(),
        );

        return $response['data'][0] ?? null;
    }

    /**
     * Create shipping labels for multiple orders.
     * @param string[] $orderNumbers
     * @param int $integrationId
     * @param bool $applyShippingRules
     * @return array{data: array<int, array<string, mixed>>, errors?: array<int, array<string, mixed>>}
     * @throws SendcloudRequestException
     */
    public function createLabels(array $orderNumbers, int $integrationId, bool $applyShippingRules = false): array
    {
        $orders = array_map(static fn(string $orderNumber) => [
            'order_id' => $orderNumber,
            'apply_shipping_rules' => $applyShippingRules,
        ], $orderNumbers);

        try {
            $response = $this->guzzleClient->post('orders/create-labels-async', [
                RequestOptions::JSON => [
                    'integration_id' => $integrationId,
                    'orders' => $orders,
                ],
            ]);

            return Json::decodeIfJson($response->getBody());
        } catch (TransferException $exception) {
            throw (new SendcloudRequestException())->parseGuzzleException($exception, Craft::t('commerce-sendcloud', 'Failed to create Label'));
        }
    }

    /**
     * Get the shipping label in PDF format
     * @param OrderSyncStatus $status
     * @param LabelFormat|null $format
     * @return string
     * @throws SendcloudRequestException
     * @throws SendcloudStateException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLabelPdf(OrderSyncStatus $status, ?LabelFormat $format = null): string
    {
        if ($format === null) {
            $settings = SendcloudPlugin::getInstance()->getSettings();
            $format = $settings->getLabelFormat();
        }
        try {
            $response = $this->guzzleClient->get('parcels/' . $status->parcelId . '/documents/label?paper_size=' . $format->value, [
                RequestOptions::HEADERS => [
                    'Accept' => 'application/pdf',
                ],
            ]);
            return $response->getBody()->getContents();
        } catch (TransferException $exception) {
            throw (new SendcloudRequestException())->parseGuzzleException($exception, Craft::t('commerce-sendcloud', 'Failed to get Label'));
        }
    }

    /**
     * @param array<int> $parcelIds
     * @param LabelFormat|null $format
     * @return string
     * @throws SendcloudRequestException
     * @throws SendcloudStateException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLabelsPdf(array $parcelIds, ?LabelFormat $format = null): string
    {
        if (empty($parcelIds)) {
            throw new SendcloudStateException('No parcels were provided to download labels for.');
        }

        if ($format === null) {
            $settings = SendcloudPlugin::getInstance()->getSettings();
            $format = $settings->getLabelFormat();
        }

        $query = ['paper_size=' . $format->value];
        foreach ($parcelIds as $parcelId) {
            $query[] = 'parcels=' . urlencode((string)$parcelId);
        }

        try {
            $response = $this->guzzleClient->get('parcel-documents/label?' . implode('&', $query), [
                RequestOptions::HEADERS => [
                    'Accept' => 'application/pdf',
                ],
            ]);
            return $response->getBody()->getContents();
        } catch (TransferException $exception) {
            throw (new SendcloudRequestException())->parseGuzzleException($exception, Craft::t('commerce-sendcloud', "Failed to get label PDF's"));
        }
    }

    public function getReturnPortalUrl(int $parcelId): ?string
    {
        try {
            $response = $this->guzzleClient->get(self::API_BASE_URL_V2 . "parcels/$parcelId/return_portal_url");
            return Json::decodeIfJson($response->getBody())['url'];
        } catch (RequestException $exception) {
            if ($exception->getResponse() && $exception->getResponse()->getStatusCode() === 404) {
                return null;
            }

            throw $exception;
        }
    }
}
