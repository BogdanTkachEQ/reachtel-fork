<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

use Doctrine\ORM\EntityManager;
use Models\Entities\QueueItem;
use Services\Container\ContainerAccessor;
use Services\Queue\QueueManager;

require_once("Morpheus/api.php");

if(!isset($argv[1]) || !is_numeric($argv[1])) {
    die("A valid queue id must be given");
}

$queueId = $argv[1];

echo "Running file upload process with queue id :$queueId\n";
echo "Continue? Y/N\n";

if(readline() == "Y") {
    $qm = ContainerAccessor::getContainer()->get(QueueManager::class);
    $em = ContainerAccessor::getContainer()->get(EntityManager::class);
    /**
     * @var $queuedItem QueueItem
     */
    $queuedItem = $em->getRepository(QueueItem::class)->find($queueId);
    $newQueuedItem = $qm->requeue($queuedItem);

    var_dump(api_queue_process_fileupload(["queue_id" => $newQueuedItem->getId()], []));
}