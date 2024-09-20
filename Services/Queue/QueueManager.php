<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Queue;

use Closure;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use InvalidArgumentException;
use Models\Entities\QueueFile;
use Models\Entities\QueueItem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class QueueManager
 * @package Services\Queue
 */
class QueueManager
{

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * QueueManager constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param QueueItem $queue
     * @param Closure|null $onSave
     * @return bool
     * @throws OptimisticLockException
     */
    public function persistToQueue(QueueItem $queue, Closure $onSave = null)
    {
        $this->entityManager->persist($queue);
        $this->entityManager->flush();
        if ($onSave && $onSave($queue)) {
            return true;
        } elseif (!$onSave) {
            return true;
        }
        return false;
    }

    /**
     * @param QueueItem $queueItem
     * @param $filename
     * @param File $file
     * @return QueueFile
     * @throws OptimisticLockException
     */
    public function addAttachment(QueueItem $queueItem, $filename, File $file)
    {
        if (!$queueItem->getId()) {
            throw new InvalidArgumentException(
                "The given queue item must be persisted before adding an attachment"
            );
        }

        if (!$file->isFile()) {
            throw new FileException("The given path is not a valid file: {$file->getPathname()}");
        }

        if (!$file->isReadable()) {
            throw new FileException("Could not read file: {$file->getPathname()}");
        }

        $queueFile = new QueueFile();
        $queueFile->setFileName($filename);

        $fileData = file_get_contents($file->getPathname());
        $queueFile->setData($fileData);
        $queueFile->setCreatedAt(new DateTime());

        $queueFile->setQueueItem($queueItem);

        $this->entityManager->persist($queueFile);
        $this->entityManager->flush();
        return $queueFile;
    }

    /**
     * @param QueueItem $queueItem
     * @return QueueItem
     * @throws Exception
     */
    public function startRun(QueueItem $queueItem)
    {
        $queueItem->setIsRunning(true);
        $queueItem->setRanAt(new DateTime());
        $queueItem->setCanRun(false);
        $this->persistToQueue($queueItem);
        return $queueItem;
    }

    /**
     * @param QueueItem $queueItem
     * @return bool
     */
    public function canRunNow(QueueItem $queueItem)
    {

        $alreadyRunning = $this->entityManager->getRepository(QueueItem::class)->findUserOrCampaignIsRunning(
            $queueItem->getProcessType(),
            $queueItem->getCampaignId(),
            $queueItem->getUserId()
        );

        if (!count($alreadyRunning)) {
            return true;
        }
        return false;
    }

    /**
     * @param QueueItem $queueItem
     * @param $returnCode
     * @param null $returnText
     * @param null $data
     * @return QueueItem
     * @throws OptimisticLockException
     */
    public function endRun(QueueItem $queueItem, $returnCode, $returnText = null, $data = null)
    {
        $queueItem->setIsRunning(false);
        $queueItem->setCanRun(false);
        $queueItem->setHasRun(true);
        $queueItem->setReturnCode($returnCode);
        $queueItem->setReturnText($returnText);
        $queueItem->setData($data);
        $queueItem->setCompletedAt(new DateTime());
        $this->persistToQueue($queueItem);
        return $queueItem;
    }

    /**
     * @param QueueItem $queueItem
     * @param $returnCode
     * @param null $returnText
     * @param null $data
     * @return QueueItem
     * @throws OptimisticLockException
     */
    public function failedToRun(QueueItem $queueItem, $returnCode, $returnText = null, $data = null)
    {
        $queueItem->setIsRunning(false);
        $queueItem->setCanRun(false);
        $queueItem->setHasRun(false);
        $queueItem->setReturnCode($returnCode);
        $queueItem->setReturnText($returnText);
        $queueItem->setData($data);
        $this->persistToQueue($queueItem);
        return $queueItem;
    }

    /**
     * @param QueueItem $queueItem
     * @return QueueItem
     * @throws OptimisticLockException
     */
    public function forceEndRun(QueueItem $queueItem)
    {
        return $this->failedToRun(
            $queueItem,
            -2,
            "Forced process to end",
            json_encode(["There has been a problem with this process - forced it to end."])
        );
    }

    /**
     * @param QueueItem $queueItem
     * @return QueueItem
     *
     * Requeue's the given QueueItem by cloning it and its attachments, setting the old one to complete
     * and returning a new instance.
     *
     * @throws OptimisticLockException
     */
    public function requeue(QueueItem $queueItem)
    {
        $this->endRun($queueItem, $queueItem->getReturnCode(), $queueItem->getReturnText());
        $newItem = clone $queueItem;
        $newItem->setId(null);
        $this->entityManager->detach($newItem);
        $newItem->setCanRun(true);
        $newItem->setIsRunning(false);
        $newItem->setCompletedAt(null);
        $newItem->setRanAt(null);
        $newItem->setHasRun(false);
        $newItem->setData(json_encode(["requeue of: " . $queueItem->getId()]));
        $this->persistToQueue($newItem);
        if ($queueItem->getQueueFiles()) {
            foreach ($queueItem->getQueueFiles() as $queueFile) {
                /**
                 * @var $queueFile QueueFile
                 */
                $newFile = clone $queueFile;
                $this->entityManager->detach($newFile);
                $newFile->setQueueItem($newItem);
                rewind($queueFile->getData());
                $newFile->setData(stream_get_contents($queueFile->getData()));

                $this->entityManager->persist($newFile);
                $this->entityManager->flush();
            }
        }
        return $newItem;
    }
}
