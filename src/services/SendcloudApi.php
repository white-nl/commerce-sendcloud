<?php


namespace white\commerce\sendcloud\services;

use craft\base\Component;
use white\commerce\sendcloud\client\JouwWebFactory;
use white\commerce\sendcloud\SendcloudPlugin;

class SendcloudApi extends Component
{
    private $clientsBySiteId = [];
    
    /** @var Integrations */
    private $integrations;

    public function init()
    {
        parent::init();
        
        $this->integrations = SendcloudPlugin::getInstance()->integrations;
    }

    /**
     * Gets the Sendcloud API client configured for the given site.
     * 
     * @param integer|null $siteId
     * @return \white\commerce\sendcloud\client\SendcloudInterface
     * @throws \craft\errors\SiteNotFoundException
     */
    public function getClient(?int $siteId = null)
    {
        if ($siteId === null) {
            $siteId = \Craft::$app->getSites()->getPrimarySite()->id;
        }
        
        if (!array_key_exists($siteId, $this->clientsBySiteId)) {
            $integration = $this->integrations->getIntegrationBySiteId($siteId);
            if (!$integration) {
                throw new \Exception("Integration not found for site #{$siteId}.");
            }

            $this->clientsBySiteId[$siteId] = (new JouwWebFactory($integration))->getClient();
        }

        return $this->clientsBySiteId[$siteId];
    }
}
