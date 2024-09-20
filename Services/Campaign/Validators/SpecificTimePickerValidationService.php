<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Validators;

use Models\CampaignSettings;
use Services\Campaign\Builders\SpecificTimesDirector;
use Services\Campaign\CampaignTimingAccessor;
use Services\Validators\CampaignPublicHolidaySettingsValidator;
use Services\Validators\SpecificTimesTimingPeriodValidator;

/**
 * Class SpecificTimePickerValidationService
 */
class SpecificTimePickerValidationService
{
    /** @var CampaignTimingAccessor */
    private $timingAccessor;

    /** @var SpecificTimesTimingPeriodValidator */
    private $timingPeriodValidator;

    /** @var SpecificTimesDirector */
    private $specificTimesDirector;

    /** @var CampaignPublicHolidaySettingsValidator */
    private $publicHolidaySettingsValidator;

    /**
     * SpecificTimePickerValidationService constructor.
     * @param SpecificTimesDirector                  $director
     * @param SpecificTimesTimingPeriodValidator     $timingPeriodValidator
     * @param CampaignTimingAccessor                 $timingRuleAccessor
     * @param CampaignPublicHolidaySettingsValidator $publicHolidaySettingsValidator
     */
    public function __construct(
        SpecificTimesDirector $director,
        SpecificTimesTimingPeriodValidator $timingPeriodValidator,
        CampaignTimingAccessor $timingRuleAccessor,
        CampaignPublicHolidaySettingsValidator $publicHolidaySettingsValidator
    ) {
        $this->timingPeriodValidator = $timingPeriodValidator;
        $this->timingAccessor = $timingRuleAccessor;
        $this->specificTimesDirector = $director;
        $this->publicHolidaySettingsValidator = $publicHolidaySettingsValidator;
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @param array            $settings
     * @return boolean
     */
    public function isValid(CampaignSettings $campaignSettings, array $settings)
    {
        $specificTimes = $this
            ->specificTimesDirector
            ->buildFromArray($settings);

        $timingGroup = $this->timingAccessor->getTimingGroup($campaignSettings);
        if ($timingGroup) {
            $this
                ->timingPeriodValidator
                ->setTimingGroup($timingGroup);
        }

        $campaignSettings->setSpecificTimes($specificTimes);

        return $this->publicHolidaySettingsValidator->setCampaignSettings($campaignSettings)->isValid() &&
            $this
                ->timingPeriodValidator
                ->setSpecificTimes($specificTimes)
                ->isValid();
    }
}
