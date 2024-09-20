<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Builders;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignSpecificTime;
use Services\Campaign\Interfaces\Builders\CampaignTimingRangesDirectorInterface;

/**
 * Class SpecificTimeDirector
 */
class SpecificTimesDirector implements CampaignTimingRangesDirectorInterface
{
    /** @var SpecificTimeBuilder */
    private $builder;

    /**
     * SpecificTimeDirector constructor.
     * @param SpecificTimeBuilder $builder
     */
    public function __construct(SpecificTimeBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param $campaignId
     * @return ArrayCollection CampaignSpecificTime[]
     */
    public function buildFromCampaignId($campaignId)
    {
        $timeZone = api_campaigns_gettimezone($campaignId);

        if (!$timeZone) {
            throw new \InvalidArgumentException('Campaign id does not exist');
        }

        $settings = [
            'timezone' => $timeZone,
            'specific' => api_restrictions_time_specific_listall($campaignId)
        ];

        return $this->buildFromArray($settings);
    }

    public function buildFromArray(array $settings)
    {
        /** @var CampaignSpecificTime[] ArrayCollection $specificTimes */
        $specificTimes = new ArrayCollection();
        $timeZone = $settings['timezone'];
        foreach ($settings['specific'] as $specific) {
            $specificTime = $this->builder->reset()->getSpecificTime();
            $specificTime
                ->setStatus((int)$specific['status'])
                ->setStartDateTime((new \DateTime('@' . $specific['starttime']))->setTimezone($timeZone))
                ->setEndDateTime((new \DateTime('@' . $specific['endtime'], $timeZone))->setTimezone($timeZone));

            $specificTimes->add($specificTime);
        }

        return $specificTimes;
    }
}
