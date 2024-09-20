<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign;

use Services\Campaign\Cloner\GenericCampaignCloner;
use Services\Campaign\Interfaces\CampaignCreatorInterface;
use Services\Exceptions\Campaign\CampaignCreationException;
use Services\Exceptions\CampaignValidationException;

/**
 * Class GenericCampaignCreator
 */
class GenericCampaignCreator implements CampaignCreatorInterface
{
    /** @var GenericCampaignCloner */
    private $cloner;

    /**
     * GenericCampaignCreator constructor.
     * @param GenericCampaignCloner $campaignCloner
     */
    public function __construct(GenericCampaignCloner $campaignCloner)
    {
        $this->cloner = $campaignCloner;
    }

    /**
     * @param string  $name
     * @param integer $sourceCampaignId
     * @param integer $ownerId
     * @return integer
     * @throws CampaignCreationException
     * @throws CampaignValidationException
     */
    public function create($name, $sourceCampaignId, $ownerId = null)
    {
        return $this->cloner->setOwnerId($ownerId)->cloneCampaign($sourceCampaignId, $name);
    }
}
