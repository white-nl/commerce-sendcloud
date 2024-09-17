<?php

namespace white\commerce\sendcloud\client;

use craft\commerce\elements\Order;
use craft\commerce\errors\CurrencyException;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Utils;
use white\commerce\sendcloud\models\Integration;
use white\commerce\sendcloud\models\ShippingMethod;
use yii\base\InvalidConfigException;

/**
 * Client to perform calls on the Sendcloud API.
 */
class SendcloudClient
{
    protected const API_BASE_URL = 'https://panel.sendcloud.sc/api/v2/';

    protected Client $guzzleClient;

    private ?array $sendcloudShippingMethods = null;

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
}
