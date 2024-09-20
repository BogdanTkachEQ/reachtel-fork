<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators\Interfaces;

use Models\CampaignSettings;

/**
 * Class CampaignSettingsValidatorInterface
 */
interface CampaignSettingsValidatorInterface extends CampaignValidatorInterface
{
    /**
     * @param CampaignSettings $campaignSettings
     * @return $this
     */
    public function setCampaignSettings(CampaignSettings $campaignSettings);
}
