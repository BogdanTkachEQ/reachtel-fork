<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators;

use Models\CampaignSettings;
use Models\Entities\TimingGroup;
use Services\Exceptions\Campaign\Validators\CampaignTimingRangeValidationFailure;
use Services\Exceptions\Validators\CampaignTimingPeriodInvalidTimeException;
use Services\Exceptions\Validators\ValidatorRuntimeException;
use Services\Validators\Interfaces\CampaignSettingsValidatorInterface;
use Services\Validators\Interfaces\TimingPeriodValidatorInterface as TpValidatorInterface;

/**
 * Class CampaignTimeperiodValidator
 */
class CampaignTimingPeriodSettingsValidator implements CampaignSettingsValidatorInterface, TpValidatorInterface
{
    /** @var RecurringTimesTimingPeriodValidator */
    private $recurringTimePeriodValidator;

    /** @var SpecificTimesTimingPeriodValidator */
    private $specificTimePeriodValidator;

    /** @var CampaignSettings */
    private $campaignSettings;

    /**
     * CampaignTimingPeriodValidator constructor.
     * @param RecurringTimesTimingPeriodValidator $recurringTimesTimingPeriodValidator
     * @param SpecificTimesTimingPeriodValidator  $specificTimesTimingPeriodValidator
     */
    public function __construct(
        RecurringTimesTimingPeriodValidator $recurringTimesTimingPeriodValidator,
        SpecificTimesTimingPeriodValidator $specificTimesTimingPeriodValidator
    ) {
        $this->recurringTimePeriodValidator = $recurringTimesTimingPeriodValidator;
        $this->specificTimePeriodValidator = $specificTimesTimingPeriodValidator;
    }

    /**
     * @param TimingGroup $timingGroup
     * @return $this
     */
    public function setTimingGroup(TimingGroup $timingGroup)
    {
        $this->recurringTimePeriodValidator->setTimingGroup($timingGroup);
        $this->specificTimePeriodValidator->setTimingGroup($timingGroup);
        return $this;
    }

    /**
     * @return boolean
     * @throws CampaignTimingPeriodInvalidTimeException
     * @throws ValidatorRuntimeException
     */
    public function isValid()
    {
        if (!$this->campaignSettings) {
            throw new ValidatorRuntimeException('Campaign settings not set');
        }

        return (
            $this
                ->recurringTimePeriodValidator
                ->setRecurringTimes($this->campaignSettings->getRecurringTimes())
                ->isValid() &&
            $this
                ->specificTimePeriodValidator
                ->setSpecificTimes($this->campaignSettings->getSpecificTimes())
                ->isValid()
        );
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @return $this
     */
    public function setCampaignSettings(CampaignSettings $campaignSettings)
    {
        $this->campaignSettings = $campaignSettings;
        return $this;
    }

    /**
     * @param \DateTime $dateTime
     * @return boolean
     * @throws ValidatorRuntimeException
     * @throws CampaignTimingRangeValidationFailure
     * @throws \Exception
     */
    public function isValidDateTime(\DateTime $dateTime)
    {
        if (!$this->campaignSettings) {
            throw new ValidatorRuntimeException('Campaign settings not set');
        }

        try {
            return $this
                ->specificTimePeriodValidator
                ->setSpecificTimes($this->campaignSettings->getSpecificTimes())
                ->isValidDateTime($dateTime);
        } catch (CampaignTimingRangeValidationFailure $exception) {
            return $this
                ->recurringTimePeriodValidator
                ->setRecurringTimes($this->campaignSettings->getRecurringTimes())
                ->isValidDateTime($dateTime);
        }
    }
}
