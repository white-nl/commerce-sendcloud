<?php


namespace white\commerce\sendcloud\services;

use craft\base\Component;
use craft\errors\SiteNotFoundException;
use white\commerce\sendcloud\client\JouwWebFactory;
use white\commerce\sendcloud\client\SendcloudInterface;
use white\commerce\sendcloud\SendcloudPlugin;

class SendcloudApi extends Component
{
    private array $clientsBySiteId = [];
    
    private ?Integrations $integrations = null;

    public function init(): void
    {
        parent::init();
        
        $this->integrations = SendcloudPlugin::getInstance()->integrations;
    }

    /**
     * Gets the Sendcloud API client configured for the given site.
     * @param int|null $siteId
     * @return SendcloudInterface
     * @throws SiteNotFoundException
     */
    public function getClient(?int $siteId = null): SendcloudInterface
    {
        if ($siteId === null) {
            $siteId = \Craft::$app->getSites()->getPrimarySite()->id;
        }
        
        if (!array_key_exists($siteId, $this->clientsBySiteId)) {
            $integration = $this->integrations->getIntegrationBySiteId($siteId);
            if (!$integration instanceof \white\commerce\sendcloud\models\Integration) {
                throw new \RuntimeException(sprintf('Integration not found for site #%s.', $siteId));
            }

            $this->clientsBySiteId[$siteId] = (new JouwWebFactory($integration))->getClient();
        }

        return $this->clientsBySiteId[$siteId];
    }
}
