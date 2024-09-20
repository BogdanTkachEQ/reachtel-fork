<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Validators;

use Models\CampaignSettings;
use Services\Campaign\Builders\RecurringTimesDirector;
use Services\Campaign\CampaignTimingAccessor;
use Services\Validators\RecurringTimesTimingPeriodValidator;

/**
 * Class RecurringTimePickerValidationService
 */
class RecurringTimePickerValidationService
{
    /** @var RecurringTimesTimingPeriodValidator */
    private $timingPeriodValidator;

    /** @var RecurringTimesDirector */
    private $recurringTimesDirector;

    /** @var CampaignTimingAccessor */
    private $timingAccessor;

    /**
     * RecurringTimeValidationService constructor.
     * @param RecurringTimesTimingPeriodValidator $timingPeriodValidator
     * @param RecurringTimesDirector              $recurringTimesDirector
     * @param CampaignTimingAccessor              $timingRuleAccessor
     */
    public function __construct(
        RecurringTimesTimingPeriodValidator $timingPeriodValidator,
        RecurringTimesDirector $recurringTimesDirector,
        CampaignTimingAccessor $timingRuleAccessor
    ) {
        $this->timingPeriodValidator = $timingPeriodValidator;
        $this->recurringTimesDirector = $recurringTimesDirector;
        $this->timingAccessor = $timingRuleAccessor;
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @param array            $settings
     * eg: [
     *      'timezone' => DateTimezone,
     *      'recurring' => ['starttime' => timestamp, 'endtime' => timestamp, 'daysofweek' => integer]
     * ]
     * @return boolean
     * @throws \Exception
     */
    public function isValid(CampaignSettings $campaignSettings, array $settings)
    {
        $recurringTimes = $this->recurringTimesDirector->buildFromArray($settings);
        $timingGroup = $this->timingAccessor->getTimingGroup($campaignSettings);

        if ($timingGroup) {
            $this
                ->timingPeriodValidator
                ->setTimingGroup($timingGroup);
        }

        return $this
            ->timingPeriodValidator
            ->setRecurringTimes($recurringTimes)
            ->isValid();
    }
}
