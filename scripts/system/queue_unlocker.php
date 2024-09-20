<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 *
 * Forces items in the queue to an error state if they have been in a run state for too long
 * tags
 *  - min-run-time = Minutes: Minimum run time a process allowed to even be looked at - don't change (60 minute default)
 *  - max-run-time = Minutes: Maximum run time a process is allowed before being forced into error sate
 *  - queue-item-id (if given will remove the specific id)
 */

use Doctrine\ORM\EntityManager;
use Models\Entities\QueueItem;
use Services\Container\ContainerAccessor;
use Services\Queue\QueueManager;

require_once("Morpheus/api.php");

$cronid = getenv('CRON_ID');
$tags = api_cron_tags_get($cronid);

$id = null;

$minRunTime = 60; // Minimum run time allowed to be included in the script in minutes
if (isset($tags['min-run-time'])) {
    if (!is_numeric($tags['min-run-time'])) {
        print 'Tag min-run-time must be numeric';
        exit;
    }
    $minRunTime = $tags['min-run-time'];
}

$maxRunRime = null;

if (isset($tags['queue-item-id'])) {
    $id = $tags['queue-item-id'];
} elseif (isset($tags['max-run-time'])) {
    $maxRunRime = $tags['max-run-time'];
    if (!is_numeric($maxRunRime)) {
        print 'Tag max-run-time must be numeric';
        exit;
    }

    if ($maxRunRime < $minRunTime) {
        print "Will not run on processes that have run for less than {$minRunTime} minutes";
        print " - override it with the tag min-run-time or use individual ids (tag: queue-item-id) instead\n";
        exit;
    }
} else {
    print "You must set a max-run-time tag.  It must be in numeric format representing how many minutes a process can run for\n";
    exit;
}

$qm = ContainerAccessor::getContainer()->get(QueueManager::class);
$em = ContainerAccessor::getContainer()->get(EntityManager::class);

try {
    $oldItems = [];
    if ($id) {
        $item = $em->getRepository(QueueItem::class)->find($id);
        $oldItems[] = $item;
    } else {
        $oldItems = $em->getRepository(QueueItem::class)->findRuntimeGreaterThan($maxRunRime);
    }

    foreach ($oldItems as $item) {
        $qm->forceEndRun($item);
        echo "Forced item id: " . $item->getId() . " to end\n";
    }

    $itemCount = count($oldItems);
    echo "Forced a total of " . $itemCount . "items to end\n";
    api_misc_audit("QUEUE_UNLOCKER", "Forced a total of " . $itemCount . "items to end\n");
    if ($itemCount > 0) {
        alertSupport($oldItems);
    }
} catch (Exception $e) {
    api_error_raise("QUEUE_UNLOCKER: " . $e->getMessage());
    exit(1);
}

function alertSupport($queueItems) {
    api_error_raise("QUEUE_UNLOCKER: Process queue items stuck in 'run' state for too long set to 'error' state"  );

    $email["to"] = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["subject"] = "[ReachTEL] File Upload processing error - forcefully canceled processes";
    $email["textcontent"] = "Hello,\n\nFile uploads running in the process queue were forcefully ended, details follow:\n\n";

    foreach ($queueItems as $queueItem) {
        $email["textcontent"] .= "User ID: " . $queueItem->getUserId() . ", Queue Item ID: " . $queueItem->getId() . "\n";
    }
    $email["htmlcontent"] = nl2br($email["textcontent"]);

    api_email_template($email);
}