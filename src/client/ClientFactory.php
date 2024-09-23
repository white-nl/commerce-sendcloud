<?php

namespace white\commerce\sendcloud\client;

use white\commerce\sendcloud\models\Integration;

final class ClientFactory
{
    private string $publicKey;

    private string $secretKey;

    /**
     * ClientFactort constructor.
     * @param Integration $integration
     */
    public function __construct(Integration $integration)
    {
        if (empty($integration->publicKey) || empty($integration->secretKey)) {
            throw new \InvalidArgumentException('One of keys are empty. Please, check your Sendcloud settings.');
        }

        $this->publicKey = $integration->publicKey;
        $this->secretKey = $integration->secretKey;
    }

    /**
     * @return SendcloudClient
     */
    public function getClient(): SendcloudClient
    {
        return new SendcloudClient($this->publicKey, $this->secretKey);
    }
}
