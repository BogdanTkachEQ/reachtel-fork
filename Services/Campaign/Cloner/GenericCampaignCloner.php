<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Cloner;

use Services\Campaign\Cloner\Actions\Interfaces\CloneActionInterface;
use Services\Campaign\Cloner\Interfaces\CampaignClonerInterface;
use Services\Exceptions\Campaign\CampaignCreationException;
use Services\Exceptions\CampaignValidationException;

/**
 * Class GenericCampaignCloner
 * @package Services\Campaign\Hooks\Cascading\Cloner
 *
 * Clones the given source campaign giving it $newName
 *
 * Accepts CloneActions which run pre and post clone
 *
 */
class GenericCampaignCloner implements CampaignClonerInterface
{

    /**
     * @var null
     */
    protected $ownerId;

    public function __construct($ownerId = null)
    {
        $this->ownerId = $ownerId;
    }

    /**
     * @var array
     */
    private $preCloneActions = [];
    /**
     * @var array
     */
    private $postCloneActions = [];

    /**
     * @param $sourceCampaignId
     * @param $newName
     * @return false|int
     * @throws CampaignCreationException
     * @throws CampaignValidationException
     */
    public function cloneCampaign($sourceCampaignId, $newName)
    {
        $this->runPreCloneActions($sourceCampaignId);
        if ($this->campaignNameExists($newName)) {
            throw new CampaignValidationException("A campaign with the name {$newName} already exists!");
        }

        $newId = api_campaigns_add($newName, null, $sourceCampaignId, $this->ownerId);

        if (!is_numeric($newId)) {
            throw new CampaignCreationException(
                "Could not clone the campaign {$sourceCampaignId} to {$newName}, an error occurred."
            );
        }
        $this->runPostCloneActions($sourceCampaignId, $newId);
        return $newId;
    }

    /**
     * @param $name
     * @return bool
     */
    protected function campaignNameExists($name)
    {
        return is_numeric(api_campaigns_checknameexists($name));
    }

    public function addPreCloneAction(CloneActionInterface $action)
    {
        $this->preCloneActions[] = $action;
        return $this;
    }

    /**
     * @param \Services\Campaign\Cloner\Actions\Interfaces\CloneActionInterface $action
     * @return $this|mixed
     */
    public function addPostCloneAction(CloneActionInterface $action)
    {
        $this->postCloneActions[] = $action;
        return $this;
    }

    /**
     * Runs pre-clone actions
     * @param $sourceCampaignId
     * @return void
     */
    protected function runPreCloneActions($sourceCampaignId)
    {
        foreach ($this->preCloneActions as $action) {
            $action->apply($sourceCampaignId);
        }
    }

    /**
     * Runs post clone actions, each action is given the source campaign id and the new campaign id
     *
     * @param $sourceCampaignId
     * @param $newCampaignId
     * @return void @void
     */
    protected function runPostCloneActions($sourceCampaignId, $newCampaignId)
    {
        foreach ($this->postCloneActions as $action) {
            $action->apply($sourceCampaignId, $newCampaignId);
        }
    }

    /**
     * @return integer
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * @param integer $ownerId
     * @return $this
     */
    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;
        return $this;
    }
}
