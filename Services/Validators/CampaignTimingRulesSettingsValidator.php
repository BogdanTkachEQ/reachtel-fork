<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators;

use Models\CampaignSettings;
use Models\Entities\TimingGroup;
use Services\Campaign\CampaignTimingAccessor;
use Services\Exceptions\Campaign\Validators\CampaignTimingRangeValidationFailure;
use Services\Exceptions\Validators\CampaignTimingRuleValidatorException;
use Services\Validators\Interfaces\DateTimeValidatorInterface;
use Services\Validators\Interfaces\CampaignSettingsValidatorInterface;

/**
 * Class TimingRulesValidator
 */
class CampaignTimingRulesSettingsValidator implements CampaignSettingsValidatorInterface, DateTimeValidatorInterface
{
    /** @var CampaignTimingAccessor */
    private $campaignTimingAccessor;

    /** @var CampaignTimingPeriodSettingsValidator */
    private $campaignTimingPeriodValidator;

    /** @var CampaignSettings */
    private $campaignSettings;

    /**
     * CampaignTimingRulesValidator constructor.
     * @param CampaignTimingAccessor                $campaignTimingRuleAccessor
     * @param CampaignTimingPeriodSettingsValidator $campaignTimingPeriodValidator
     */
    public function __construct(
        CampaignTimingAccessor $campaignTimingRuleAccessor,
        CampaignTimingPeriodSettingsValidator $campaignTimingPeriodValidator
    ) {
        $this->campaignTimingAccessor = $campaignTimingRuleAccessor;
        $this->campaignTimingPeriodValidator = $campaignTimingPeriodValidator;
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
     * @return boolean
     */
    public function isValid()
    {
        $this->buildValidator();
        $group = $this->getTimingGroup();

        if (is_null($group)) {
            return true;
        }

        $this->campaignTimingPeriodValidator->setTimingGroup($group);
        return $this->campaignTimingPeriodValidator->isValid();
    }

    /**
     * @param \DateTime $dateTime
     * @return boolean
     * @throws CampaignTimingRangeValidationFailure
     * @throws \Exception
     */
    public function isValidDateTime(\DateTime $dateTime)
    {
        $this->buildValidator();

        $group = $this->getTimingGroup();
        if (!is_null($group)) {
            $this->campaignTimingPeriodValidator->setTimingGroup($group);
        }

        $newDateTime = clone $dateTime;
        $newDateTime->setTimezone($this->campaignSettings->getTimeZone());

        return $this
            ->campaignTimingPeriodValidator
            ->isValidDateTime($newDateTime);
    }

    /**
     * @return void
     */
    private function buildValidator()
    {
        if (!$this->campaignSettings) {
            throw new CampaignTimingRuleValidatorException('Campaign settings not set for validation');
        }

        $this
            ->campaignTimingPeriodValidator
            ->setCampaignSettings($this->campaignSettings);
    }

    /**
     * @return TimingGroup|null
     */
    private function getTimingGroup()
    {
        try {
            return $this
                ->campaignTimingAccessor
                ->getTimingGroup($this->campaignSettings);
        } catch (\RuntimeException $exception) {
            throw new CampaignTimingRuleValidatorException($exception->getMessage());
        }
    }
}
