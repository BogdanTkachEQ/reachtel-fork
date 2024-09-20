<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Interfaces;

use Services\Exceptions\Campaign\CampaignCreationException;
use Services\Exceptions\CampaignValidationException;

/**
 * Class CampaignCreatorInterface
 */
interface CampaignCreatorInterface
{
    /**
     * @param string  $name
     * @param integer $sourceCampaignId
     * @param integer $ownerId
     * @return integer
     * @throws CampaignValidationException
     * @throws CampaignCreationException
     */
    public function create($name, $sourceCampaignId, $ownerId = null);
}
