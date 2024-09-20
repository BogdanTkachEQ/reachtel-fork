<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Repository;

use Doctrine\ORM\EntityRepository;
use Services\Queue\QueueProcessTypeEnum;

/**
 * Class CountryRepository
 */
class QueueItemRepository extends EntityRepository
{
    /**
     * @param $shortName
     * @return array
     */
    public function findNextRunnableByProcessType(QueueProcessTypeEnum $processType)
    {
        return $this->findOneBy(
            [
                "processType" => $processType,
                "hasRun" => 0,
                "isRunning" => 0,
                "canRun" => 1
            ],
            ["id" => "ASC"],
            1
        );
    }

    /**
     * @param QueueProcessTypeEnum $processType
     * @param $campaignId
     * @param int $limit
     * @return array
     */
    public function findRecentActiveByCampaignId(QueueProcessTypeEnum $processType, $campaignId, $limit = 10)
    {
        return $this->findBy(
            [
                "processType" => $processType,
                "campaignId" => $campaignId
            ],
            ["id" => "DESC"],
            $limit
        );
    }

    /**
     * @param QueueProcessTypeEnum $processType
     * @param                      $campaignId
     * @return array
     */
    public function findUserOrCampaignIsRunning(QueueProcessTypeEnum $processType, $campaignId, $userId)
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT q
            FROM Models\Entities\QueueItem q
            WHERE (q.campaignId = :campaignId OR q.userId = :userId)
            AND q.processType = :processType
            AND q.isRunning = true
            ORDER BY q.id'
        )->setParameter('processType', $processType->getValue())
         ->setParameter('campaignId', $campaignId)
         ->setParameter('userId', $userId);
        return $query->getResult();
    }

    /**
     * @param QueueProcessTypeEnum $processType
     * @param $campaignId
     * @param int $limita
     * @return array
     */
    public function findRecentActive($limit = 10)
    {
        return $this->findBy([], ["id" => "DESC"], $limit);
    }

    /**
     * @param \DateTime $date
     * @return mixed
     */
    public function deleteBetweenDates(\DateTime $startDate, \DateTime $endDate)
    {
        $qb = $this->createQueryBuilder('e')
            ->delete()
            ->where('e.createdAt >= :startdate')
            ->andWhere('e.createdAt <= :enddate')
            ->setParameter('startdate', $startDate)
            ->setParameter('enddate', $endDate);
        return $qb->getQuery()->execute();
    }

    /**
     * Gets processes which have a runtime greater than $maxRunMinutes
     *
     * @param $maxRunMinutes
     * @return array
     * @throws \Exception
     */
    public function findRuntimeGreaterThan($maxRunMinutes)
    {
        if (!is_numeric($maxRunMinutes) || $maxRunMinutes <= 0) {
            throw new \InvalidArgumentException("Max run age must be greater than 0 minutes");
        }

        $maxAgeDate = (new \DateTime())->sub(\DateInterval::createFromDateString($maxRunMinutes ." minutes"));

        $query = $this->getEntityManager()->createQuery(
            'SELECT q
            FROM Models\Entities\QueueItem q
            WHERE q.ranAt <= :beforeTime            
            AND q.isRunning = true
            ORDER BY q.id'
        )->setParameter('beforeTime', $maxAgeDate->format("Y-m-d H:i:s"));
        return $query->getResult();
    }
}
