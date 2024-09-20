<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Repository;

use Doctrine\ORM\EntityRepository;
use Models\Entities\Region;
use Models\Entities\TimingDescriptor;
use Services\Campaign\Classification\CampaignClassificationEnum;

/**
 * Class CampaignTimingRuleRepository
 */
class CampaignTimingRuleRepository extends EntityRepository
{
    /**
     * @param CampaignClassificationEnum $classificationEnum
     * @param TimingDescriptor           $timingDescriptor
     * @param Region                     $region
     * @return array
     */
    public function getTimingRules(
        CampaignClassificationEnum $classificationEnum,
        TimingDescriptor $timingDescriptor,
        Region $region
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        return $qb
            ->select('rules')
            ->from($this->_entityName, 'rules')
            ->join('rules.timingGroup', 'tg')
            ->join('tg.regions', 'r')
            ->join('rules.campaignClassification', 'classification')
            ->where('rules.timingDescriptor=:descriptor')
            ->andWhere('classification.name=:classification')
            ->andWhere('r.id=:r')
            ->setParameter(':descriptor', $timingDescriptor)
            ->setParameter(':classification', $classificationEnum->getValue())
            ->setParameter(':r', $region->getId())
            ->getQuery()
            ->getResult();
    }
}
