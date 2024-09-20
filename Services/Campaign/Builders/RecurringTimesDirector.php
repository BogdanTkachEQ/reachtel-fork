<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Builders;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignRecurringTime;
use Models\Day;
use Services\Campaign\Interfaces\Builders\CampaignTimingRangesDirectorInterface;
use Services\Utils\CampaignUtils;

/**
 * Class RecurringTimesDirector
 */
class RecurringTimesDirector implements CampaignTimingRangesDirectorInterface
{
    /** @var RecurringTimeBuilder */
    private $builder;

    /**
     * RecurringTimesDirector constructor.
     * @param RecurringTimeBuilder $builder
     */
    public function __construct(RecurringTimeBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param $campaignId
     * @return ArrayCollection CampaignRecurringTime[]
     * @throws \Exception
     */
    public function buildFromCampaignId($campaignId)
    {
        $timeZone = api_campaigns_gettimezone($campaignId);

        if (!$timeZone) {
            throw new \InvalidArgumentException('Campaign id does not exist');
        }

        $settings = ['timezone' => $timeZone, 'recurring' => api_restrictions_time_recurring_listall($campaignId)];
        return $this->buildFromArray($settings);
    }

    /**
     * @param array $settings
     * @return ArrayCollection
     * @throws \Exception
     */
    public function buildFromArray(array $settings)
    {
        if (!isset($settings['timezone'])) {
            throw new \InvalidArgumentException("Timezone is required");
        }
        $timeZone = $settings['timezone'];

        /** @var CampaignRecurringTime[] ArrayCollection $recurringTimes */
        $recurringTimes = new ArrayCollection();

        if (isset($settings['recurring'])) {
            foreach ($settings['recurring'] as $setting) {
                $recurringTime = $this->builder->reset()->getRecurringTime();
                $recurringTime
                    ->setStartTime(
                        \DateTime::createFromFormat('H:i:s', $setting['starttime'], $timeZone)
                    )
                    ->setEndTime(
                        \DateTime::createFromFormat('H:i:s', $setting['endtime'], $timeZone)
                    );

                foreach (CampaignUtils::getDaysOfWeekArray($setting['daysofweek']) as $day => $active) {
                    if ($active) {
                        $recurringTime->addActiveDay(Day::byValue($day));
                    }
                }
                $recurringTimes->add($recurringTime);
            }
        }

        return $recurringTimes;
    }
}
