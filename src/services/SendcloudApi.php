<?php


namespace white\commerce\sendcloud\services;

use craft\base\Component;
use craft\commerce\Plugin;
use RuntimeException;
use white\commerce\sendcloud\client\ClientFactory;
use white\commerce\sendcloud\client\SendcloudClient;
use white\commerce\sendcloud\models\Integration;
use white\commerce\sendcloud\SendcloudPlugin;

class SendcloudApi extends Component
{
    private array $clientsByStoreId = [];
    
    private ?Integrations $integrations = null;

    public function init(): void
    {
        parent::init();
        
        $this->integrations = SendcloudPlugin::getInstance()->integrations;
    }

    /**
     * Gets the Sendcloud API client configured for the given store.
     * @param int|null $storeId
     * @return SendcloudClient
     * @throws \yii\base\InvalidConfigException
     */
    public function getClient(?int $storeId = null): SendcloudClient
    {
        if ($storeId === null) {
            $storeId = Plugin::getInstance()->getStores()->getPrimaryStore()->id;
        }
        
        if (!array_key_exists($storeId, $this->clientsByStoreId)) {
            $integration = $this->integrations->getIntegrationByStoreId($storeId);
            if (!$integration instanceof Integration) {
                throw new RuntimeException(sprintf('Integration not found for store #%s.', $storeId));
            }

            $this->clientsByStoreId[$storeId] = (new ClientFactory($integration))->getClient();
        }

        return $this->clientsByStoreId[$storeId];
    }
}
