<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign;

use Services\Campaign\Hooks\Cascading\Creators\CascadingCampaignCreatorFactory;
use Services\Campaign\Interfaces\CampaignCreatorInterface;
use Services\Exceptions\Campaign\CampaignCreationException;
use Services\Exceptions\CampaignValidationException;

/**
 * Class CascadingCampaignCreator
 */
class CascadingCampaignCreatorWrapper implements CampaignCreatorInterface
{
    /** @var CascadingCampaignCreatorFactory */
    private $cascadingCampaignCreatorFactory;

    /**
     * CascadingCampaignCreator constructor.
     * @param CascadingCampaignCreatorFactory $cascadingCampaignCreatorFactory
     */
    public function __construct(CascadingCampaignCreatorFactory $cascadingCampaignCreatorFactory)
    {
        $this->cascadingCampaignCreatorFactory = $cascadingCampaignCreatorFactory;
    }

    /**
     * @param string  $name
     * @param integer $sourceCampaignId
     * @param integer $ownerId
     * @return integer
     * @throws CampaignValidationException
     * @throws CampaignCreationException
     */
    public function create($name, $sourceCampaignId, $ownerId = null)
    {
        return $this
            ->cascadingCampaignCreatorFactory
            ->makeCreator($sourceCampaignId, $name, $ownerId)
            ->setupNextCampaign(false);
    }
}
