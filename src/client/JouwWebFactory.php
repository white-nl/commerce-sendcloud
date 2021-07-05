<?php

namespace white\commerce\sendcloud\client;

use InvalidArgumentException;
use white\commerce\sendcloud\client\SendcloudClient as Client;
use white\commerce\sendcloud\models\Integration;

final class JouwWebFactory
{
    private $publicKey;
    
    private $secretKey;

    /**
     * JouwWebFactory constructor.
     * @param Integration $integration
     */
    public function __construct(Integration $integration)
    {
        if (empty($integration->publicKey) || empty($integration->secretKey)) {
            throw new InvalidArgumentException('One of keys are empty. Please, check your Sendcloud settings.');
        }

        $this->publicKey = $integration->publicKey;
        $this->secretKey = $integration->secretKey;
    }

    /**
     * @return SendcloudInterface
     */
    public function getClient(): SendcloudInterface
    {
        return new JouwWebSendcloudAdapter(new Client($this->publicKey, $this->secretKey));
    }
}
