<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Validators;

use Models\CampaignSettings;
use Models\CampaignType;
use Services\Campaign\Classification\CampaignClassificationEnum;
use Services\Campaign\Interfaces\Validators\CampaignValidationServiceInterface;
use Services\Campaign\Validators\Disclaimers\TimingDisclaimerProvider;
use Services\Exceptions\Campaign\Validators\CampaignTimingRangeValidationFailure;
use Services\Exceptions\Campaign\Validators\CampaignTimingValidationFailure;
use Services\Exceptions\Campaign\Validators\PublicHolidayValidationFailure;
use Services\Exceptions\Campaign\Validators\TimingRuleValidationFailure;
use Services\Validators\CampaignPublicHolidaySettingsValidator;
use Services\Validators\CampaignTimingRulesSettingsValidator;

/**
 * Class CampaignTimingValidationService
 */
class CampaignTimingValidationService implements CampaignValidationServiceInterface
{
    /** @var CampaignPublicHolidaySettingsValidator */
    private $publicHolidayValidator;

    /** @var CampaignTimingRulesSettingsValidator */
    private $timingRuleValidator;

    /** @var TimingDisclaimerProvider */
    private $timingDisclaimerProvider;

    /**
     * CampaignTimingValidationService constructor.
     * @param CampaignPublicHolidaySettingsValidator $campaignPublicHolidayValidator
     * @param CampaignTimingRulesSettingsValidator   $campaignTimingRulesValidator
     * @param TimingDisclaimerProvider               $disclaimerProvider
     */
    public function __construct(
        CampaignPublicHolidaySettingsValidator $campaignPublicHolidayValidator,
        CampaignTimingRulesSettingsValidator $campaignTimingRulesValidator,
        TimingDisclaimerProvider $disclaimerProvider
    ) {
        $this->publicHolidayValidator = $campaignPublicHolidayValidator;
        $this->timingRuleValidator = $campaignTimingRulesValidator;
        $this->timingDisclaimerProvider = $disclaimerProvider;
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @return boolean
     */
    public function violatesValidationRules(CampaignSettings $campaignSettings)
    {
        $campaignSettings = $this->updateCampaignSettings($campaignSettings);

        return !$this->publicHolidayValidator->setCampaignSettings($campaignSettings)->isValid() ||
            !$this->timingRuleValidator->setCampaignSettings($campaignSettings)->isValid();
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @return string
     */
    public function getDisclaimer(CampaignSettings $campaignSettings)
    {
        return $this
            ->timingDisclaimerProvider
            ->setCampaignSettings($campaignSettings)
            ->getDisclaimer();
    }

    /**
     * @param \DateTime        $dateTime
     * @param CampaignSettings $campaignSettings
     * @return boolean
     * @throws PublicHolidayValidationFailure
     * @throws TimingRuleValidationFailure
     * @throws CampaignTimingRangeValidationFailure
     * @throws \Exception
     */
    public function isValidDateTime(\DateTime $dateTime, CampaignSettings $campaignSettings)
    {
        $campaignSettings = $this->updateCampaignSettings($campaignSettings);

        if (!$this
            ->publicHolidayValidator
            ->setCampaignSettings($campaignSettings)
            ->isValidDateTime($dateTime)
        ) {
            throw new PublicHolidayValidationFailure('Public holiday validation failed');
        }

        if (!$this->timingRuleValidator->setCampaignSettings($campaignSettings)->isValidDateTime($dateTime)) {
            throw new TimingRuleValidationFailure('Timing rule validation failed');
        }

        return true;
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @return CampaignSettings
     */
    private function updateCampaignSettings(CampaignSettings $campaignSettings)
    {
        // It is a business requirement that non-phone campaigns should be treated as exempt for timing validation
        // even if their classification is not set as exempt.
        if (!$campaignSettings->getType()->is(CampaignType::PHONE())) {
            $campaignSettings = clone $campaignSettings;
            $campaignSettings
                ->setClassificationEnum(CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT());
        }

        return $campaignSettings;
    }
}
