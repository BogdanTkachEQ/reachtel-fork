<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Builders;

use Doctrine\ORM\EntityManager;
use Models\CampaignType;
use Models\Entities\Country;
use Models\Entities\TimingDescriptor;
use Services\Campaign\Classification\CampaignClassificationEnum;

/**
 * Class CampaignValidationSettingDirector
 */
class CampaignSettingsDirector
{
    /** @var CampaignSettingsBuilder */
    private $campaignSettingsBuilder;

    /** @var EntityManager */
    private $entityManager;

    /** @var SpecificTimesDirector */
    private $specificTimesDirector;

    /** @var RecurringTimesDirector */
    private $recurringTimesDirector;

    /**
     * CampaignSettingsDirector constructor.
     * @param CampaignSettingsBuilder $builder
     * @param SpecificTimesDirector   $specificTimesDirector
     * @param RecurringTimesDirector  $recurringTimesDirector
     * @param EntityManager           $entityManager
     */
    public function __construct(
        CampaignSettingsBuilder $builder,
        SpecificTimesDirector $specificTimesDirector,
        RecurringTimesDirector $recurringTimesDirector,
        EntityManager $entityManager
    ) {
        $this->campaignSettingsBuilder = $builder;
        $this->specificTimesDirector = $specificTimesDirector;
        $this->recurringTimesDirector = $recurringTimesDirector;
        $this->entityManager = $entityManager;
    }

    /**
     * @param $campaignId
     * @return \Models\CampaignSettings
     * @throws \Exception
     */
    public function buildCampaignSettings($campaignId)
    {
        $this->campaignSettingsBuilder->reset();
        $settings = api_campaigns_setting_get_multi_byitem(
            $campaignId,
            [
                CAMPAIGN_SETTING_TYPE,
                CAMPAIGN_SETTING_REGION,
                CAMPAIGN_SETTING_WITHHOLD_CALLER_ID
            ]
        );

        if (!$settings) {
            throw new \InvalidArgumentException('Campaign id does not exist');
        }

        $country = isset($settings[CAMPAIGN_SETTING_REGION]) ?
            $this->entityManager
                ->getRepository(Country::class)
                ->findByShortName($settings[CAMPAIGN_SETTING_REGION]) :
            null;



        $this
            ->campaignSettingsBuilder
            ->setId($campaignId)
            ->setClassificationEnum(CampaignClassificationEnum::byValue(api_campaigns_getclassification($campaignId)))
            ->setType(CampaignType::byValue($settings[CAMPAIGN_SETTING_TYPE]))
            ->setSpecificTimes($this->specificTimesDirector->buildFromCampaignId($campaignId))
            ->setRecurringTimes($this->recurringTimesDirector->buildFromCampaignId($campaignId))
            ->setTimingDescriptor(
                $this
                    ->entityManager
                    ->getRepository(TimingDescriptor::class)
                    //Timing descriptor currently set to ACMA until we implement overriding timing rules
                    ->find(TimingDescriptor::DEFAULT_TIMING_DESCRIPTOR_ID)
            )
            ->setTimeZone(api_campaigns_gettimezone($campaignId))
            ->setCallerIdWithHeld(
                isset($settings[CAMPAIGN_SETTING_WITHHOLD_CALLER_ID]) ?
                    $settings[CAMPAIGN_SETTING_WITHHOLD_CALLER_ID] :
                    false
            );

        if ($country) {
            $this->campaignSettingsBuilder->setRegion($country->getRegions()->first());
        }

        return $this->campaignSettingsBuilder->getCampaignSettings();
    }
}
