<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Cloner\Interfaces;

use Services\Campaign\Cloner\Actions\Interfaces\CloneActionInterface;

/**
 * Class GenericCampaignCloner
 */
interface CampaignClonerInterface
{
    /**
     * @param $sourceCampaignId
     * @param $newName
     * @return false|int
     */
    public function cloneCampaign($sourceCampaignId, $newName);

    /**
     * @param \Services\Campaign\Cloner\Actions\Interfaces\CloneActionInterface $action
     * @return mixed
     */
    public function addPreCloneAction(CloneActionInterface $action);

    /**
     * @param CloneActionInterface $action
     * @return mixed
     */
    public function addPostCloneAction(CloneActionInterface $action);
}
