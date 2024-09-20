<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Hooks\Cascading\Interfaces;

/**
 * Interface CascadingCampaignCreator
 */
interface CascadingCampaignCreatorInterface
{
    /**
     * @return mixed
     */
    public function setupNextCampaign();

    /**
     * @return mixed
     */
    public function getPreviousCampaignName();

    /**
     * @return mixed
     */
    public function getPreviousCampaignIteration();

    /**
     * @return mixed
     */
    public function getCurrentCampaignName();

    /**
     * @return mixed
     */
    public function getNextCampaignName();

    /**
     * @return mixed
     */
    public function getCurrentCampaignIteration();

    /**
     * @return mixed
     */
    public function getNextCampaignIteration();

    /**
     * @return mixed
     */
    public function getFirstCampaign();

    /**
     * @return mixed
     */
    public function getFirstCampaignName();
}
