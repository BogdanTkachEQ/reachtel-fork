<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign;

use Services\Campaign\Builders\CampaignSettingsDirector;
use Services\Campaign\Interfaces\Validators\CampaignValidationServiceInterface;
use Services\Exceptions\Campaign\Validators\ValidationDisclaimerException;

/**
 * Class CampaignValidationService
 */
class CampaignActivationPermissionService
{
    /** @var CampaignValidationServiceInterface */
    private $campaignValidationService;

    /** @var CampaignSettingsDirector */
    private $campaignSettingsDirector;

    /**
     * CampaignActivationPermissionService constructor.
     * @param CampaignSettingsDirector           $campaignSettingsDirector
     * @param CampaignValidationServiceInterface $campaignValidationService
     */
    public function __construct(
        CampaignSettingsDirector $campaignSettingsDirector,
        CampaignValidationServiceInterface $campaignValidationService
    ) {
        $this->campaignSettingsDirector = $campaignSettingsDirector;
        $this->campaignValidationService = $campaignValidationService;
    }

    /**
     * @param integer $campaignId
     * @return boolean
     * @throws ValidationDisclaimerException
     * @throws \Exception
     */
    public function canBeActivated($campaignId)
    {
        $campaignSettings = $this->campaignSettingsDirector->buildCampaignSettings($campaignId);

        if (!$campaignSettings->getRecurringTimes()->count() &&
            !$campaignSettings->getSpecificTimes()->count()
        ) {
            return false;
        }

        if ($this->campaignValidationService->violatesValidationRules($campaignSettings)) {
            $exception = new ValidationDisclaimerException();
            $exception->setDisclaimer($this->campaignValidationService->getDisclaimer($campaignSettings));
            throw $exception;
        }

        return true;
    }
}
