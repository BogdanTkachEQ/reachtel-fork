<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators\Factory;

use Models\CampaignType;
use Services\Exceptions\Validators\CampaignTargetDataValidatorFactoryException;
use Services\Validators\Interfaces\CampaignTargetDataValidatorInterface;
use Services\Validators\WashCampaignTargetDataValidator;

/**
 * Class CampaignTargetDataValidatorFactory
 */
class CampaignTargetDataValidatorFactory
{
    /**
     * @param CampaignType $campaignType
     * @return CampaignTargetDataValidatorInterface
     * @throws CampaignTargetDataValidatorFactoryException
     */
    public function create(CampaignType $campaignType)
    {
        if ($campaignType->is(CampaignType::WASH())) {
            return new WashCampaignTargetDataValidator();
        }

        throw new CampaignTargetDataValidatorFactoryException('No validators found for the campaign type');
    }
}
