<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators;

use Models\CampaignSettings;
use Models\CampaignSpecificTime;
use Models\Entities\Region;
use Services\Campaign\Classification\CampaignClassificationEnum;
use Services\Exceptions\Validators\ValidatorRuntimeException;
use Services\Utils\PublicHolidayChecker;
use Services\Validators\Interfaces\CampaignSettingsValidatorInterface;
use Services\Validators\Interfaces\DateTimeValidatorInterface;

/**
 * Class SpecificTimesPublicHolidayValidator
 */
class CampaignPublicHolidaySettingsValidator implements CampaignSettingsValidatorInterface, DateTimeValidatorInterface
{
    /** @var PublicHolidayChecker */
    private $publicHolidayChecker;

    /** @var CampaignSettings */
    private $campaignSettings;

    /**
     * CampaignPublicHolidaySettingsValidator constructor.
     * @param PublicHolidayChecker $publicHolidayChecker
     */
    public function __construct(PublicHolidayChecker $publicHolidayChecker)
    {
        $this->publicHolidayChecker = $publicHolidayChecker;
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
     * @throws ValidatorRuntimeException
     */
    public function isValid()
    {
        if ($this->preValidate()) {
            return true;
        }

        /** @var CampaignSpecificTime $specificTime */
        foreach ($this->campaignSettings->getSpecificTimes() as $specificTime) {
            $specificTime->validate();

            if ($specificTime->getStatus() === CampaignSpecificTime::STATUS_PAST) {
                continue;
            }

            if ($this->isPublicHoliday($specificTime->getStartDateTime(), $this->campaignSettings->getRegion())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \DateTime $dateTime
     * @return boolean
     */
    public function isValidDateTime(\DateTime $dateTime)
    {
        if ($this->preValidate()) {
            return true;
        }

        $newDateTime = clone $dateTime;

        $newDateTime->setTimezone($this->campaignSettings->getTimeZone());

        if (!$this->isPublicHoliday($newDateTime, $this->campaignSettings->getRegion())) {
            return true;
        }

        return false;
    }

    /**
     * @return boolean
     */
    private function preValidate()
    {
        if (!$this->campaignSettings) {
            throw new ValidatorRuntimeException('Campaign settings not set');
        }

        $classification = $this->campaignSettings->getClassificationEnum();
        if (is_null($classification)) {
            throw new ValidatorRuntimeException('Campaign classification not found');
        }

        if ($classification->is(CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT()) ||
            !$this->campaignSettings->getRegion()
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param \DateTime $dateTime
     * @param Region    $region
     * @return boolean
     */
    private function isPublicHoliday(\DateTime $dateTime, Region $region)
    {
        return $this
            ->publicHolidayChecker
            ->isPublicHoliday(
                $dateTime,
                $region
            );
    }
}
