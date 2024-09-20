<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Interfaces\Validators;

use Models\CampaignSettings;

/**
 * Interface CampaignValidationServiceInterface
 */
interface CampaignValidationServiceInterface
{
    /**
     * @param CampaignSettings $campaignSettings
     * @return boolean
     */
    public function violatesValidationRules(CampaignSettings $campaignSettings);

    /**
     * @param CampaignSettings $campaignSettings
     * @return string
     */
    public function getDisclaimer(CampaignSettings $campaignSettings);
}
