<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Customers\Toyota\Autoload\Traits;

use Services\Campaign\Interfaces\CampaignCreatorInterface;
use Services\Utils\CampaignUtils;

/**
 * Trait CampaignFinder
 */
trait CampaignFinder
{
    /**
     * @var array
     */
    protected $brandCampaignIdMap = [];

    /**
     * @var array
     */
    protected $failedBrands = [];

    /**
     * @return array
     */
    abstract protected function getBrandCampaignMap();

    /**
     * @return CampaignCreatorInterface
     */
    abstract protected function getCampaignCreator();

    /**
     * @param string $brand
     * @return integer
     * @throws \Exception
     */
    protected function findCampaign($brand)
    {
        if (in_array($brand, $this->failedBrands)) {
            throw new \InvalidArgumentException('Could not find or create campaign for brand ' . $brand);
        }

        if (!isset($this->brandCampaignIdMap[$brand])) {
            $campaignId = $this->fetchOrCreateCampaign($brand);
            $this->brandCampaignIdMap[$brand] = $campaignId;
        }

        return $this->brandCampaignIdMap[$brand];
    }

    /**
     * @param string $brand
     * @return string
     * @throws \Exception
     */
    private function fetchOrCreateCampaign($brand)
    {
        $brandCampaignMap = $this->getBrandCampaignMap();

        if (!isset($brandCampaignMap[$brand])) {
            throw new \InvalidArgumentException(
                sprintf('Invalid brand name %s reveived', $brand)
            );
        }

        $campaignName = CampaignUtils::normalizeCampaignName($brandCampaignMap[$brand]);
        $searchName = str_replace(['{{', '}}'], '', $brandCampaignMap[$brand]);
        $previousCampaignId = api_campaigns_checknameexists($searchName);

        if (!$previousCampaignId) {
            // Caching the brand name so that it need not attempt again
            $this->failedBrands[] = $brand;
            throw new \InvalidArgumentException('Could not find or create campaign for brand ' . $brand);
        }

        try {
            $campaignId = $this->getCampaignCreator()->create($campaignName, $previousCampaignId);
        } catch (\Exception $e) {
            $this->failedBrands[] = $brand;
            throw $e;
        }

        return $campaignId;
    }
}
