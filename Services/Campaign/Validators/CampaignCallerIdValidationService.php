<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Validators;

use Models\CampaignSettings;
use Models\CampaignType;
use Services\Campaign\Interfaces\Validators\CampaignValidationServiceInterface;
use Services\Campaign\Validators\Disclaimers\CallerIdDisclaimerProvider;
use Services\Validators\CampaignCallerIdValidator;

/**
 * Class CampaignCallerIdValidationService
 */
class CampaignCallerIdValidationService implements CampaignValidationServiceInterface
{
    /** @var CampaignCallerIdValidator */
    private $callerIdValidator;

    /** @var CallerIdDisclaimerProvider */
    private $disclaimerProvider;

    /**
     * CampaignCallerIdValidationService constructor.
     * @param CampaignCallerIdValidator  $callerIdValidator
     * @param CallerIdDisclaimerProvider $disclaimerProvider
     */
    public function __construct(
        CampaignCallerIdValidator $callerIdValidator,
        CallerIdDisclaimerProvider $disclaimerProvider
    ) {
        $this->callerIdValidator = $callerIdValidator;
        $this->disclaimerProvider = $disclaimerProvider;
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @return boolean
     */
    public function violatesValidationRules(CampaignSettings $campaignSettings)
    {
        return $this->isCallerIdValidationApplicable($campaignSettings) &&
            !$this
            ->callerIdValidator
            ->setClassification($campaignSettings->getClassificationEnum())
            ->isCallerIdWithHeld($campaignSettings->isCallerIdWithHeld())
            ->isValid();
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @return string
     */
    public function getDisclaimer(CampaignSettings $campaignSettings)
    {
        return $this->disclaimerProvider->getDisclaimer();
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @return boolean
     */
    private function isCallerIdValidationApplicable(CampaignSettings $campaignSettings)
    {
        return $campaignSettings->getType()->is(CampaignType::PHONE());
    }
}
