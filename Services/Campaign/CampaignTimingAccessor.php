<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Models\CampaignSettings;
use Models\Entities\CampaignTimingRule;
use Models\Entities\TimingGroup;
use Services\Repository\CampaignTimingRuleRepository;

/**
 * Class CampaignTimingAccessor
 */
class CampaignTimingAccessor
{
    /** @var CampaignTimingRuleRepository */
    private $campaingTimingRuleRepo;

    /**
     * CampaignTimingRuleAccessor constructor.
     * @param CampaignTimingRuleRepository $ruleRepo
     */
    public function __construct(CampaignTimingRuleRepository $ruleRepo)
    {
        $this->campaingTimingRuleRepo = $ruleRepo;
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @return CampaignTimingRule | null
     */
    public function getRule(CampaignSettings $campaignSettings)
    {
        if (!$campaignSettings->getRegion()) {
            return null;
        }

        $rules = $this
            ->campaingTimingRuleRepo
            ->getTimingRules(
                $campaignSettings->getClassificationEnum(),
                $campaignSettings->getTimingDescriptor(),
                $campaignSettings->getRegion()
            );

        if (!$rules) {
            return null;
        }

        return $rules[0];
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @return TimingGroup|null
     */
    public function getTimingGroup(CampaignSettings $campaignSettings)
    {
        $rule = $this->getRule($campaignSettings);

        if (!$rule) {
            return null;
        }

        return $rule->getTimingGroup();
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @return Collection
     */
    public function getTimingPeriods(CampaignSettings $campaignSettings)
    {
        $rule = $this->getRule($campaignSettings);

        if (!$rule) {
            return new ArrayCollection();
        }

        return $rule->getTimingGroup()->getTimingPeriods();
    }
}
