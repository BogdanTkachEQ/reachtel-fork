<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 *
 * Deletes items from the queue which are between run-date and end-run-date
 * tags
 *  - run-date
 *  - end-run-date
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
if (isset($tags['queue-item-id'])) {
    $id = $tags['queue-item-id'];
} else {
    if (isset($tags['run-date'])) {
        try {
            $start = new DateTime($tags['run-date']);
        } catch (Exception $e) {
            print "Invalid run date given: '" . $tags['run-date'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
            exit;
        }
    } else {
        echo "A start date via tag 'run-date' must be given!";
        exit(1);
    }

    if (isset($tags['end-run-date'])) {
        try {
            $end = new DateTime($tags['end-run-date']);
        } catch (Exception $e) {
            print "Invalid end run date given:  '" . $tags['end-run-date'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
            exit;
        }
    } else {
        echo "An end run date via tag 'end-run-date' must be given!";
        exit(1);
    }
}

$qm = ContainerAccessor::getContainer()->get(QueueManager::class);
$em = ContainerAccessor::getContainer()->get(EntityManager::class);

if ($id) {
    $item = $em->getRepository(QueueItem::class)->find($id);
    if ($item) {
        $em->remove($item);
        $em->flush();
        echo "Deleted item id {$id} from the queue\n";
    } else {
        echo "The queue item {$id} does not exist.\n";
        exit(1);
    }
} else {
    $oldItems = $em->getRepository(QueueItem::class)->deleteBetweenDates($start, $end);
    echo "Deleted $oldItems from the queue\n";
}
