<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Name\Interfaces;

use Services\Exceptions\Campaign\CampaignNameRuntimeException;

/**
 * Interface CampaignNameInterface
 */
interface CampaignNameInterface
{
    /**
     * @return string
     * @throws CampaignNameRuntimeException
     */
    public function getName();

    /**
     * @return string
     * @throws CampaignNameRuntimeException
     */
    public function getSearchableName();
}
