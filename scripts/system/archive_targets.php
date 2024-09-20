<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 *
 * Script mass archives and deletes targets from the targets table for campaigns that have a lastsend
 * date between run-date and end-run date.
 *
 * Foreach campaign the script breaks its targets up unto chunks of max working-limit size and moves them to the archive table.
 * Once done the campaign targets are deleted.
 *
 * Will not operate if end-run-date is less than a year ago.
 *
 * tags:
 * - run-date
 * - end-run-date
 * - working-limit (default 10000 targets per chunk)
 *
 *
 * $tags['run-date'] = "-10 years";
 * $tags['end-run-date'] = "-5 years";
 */

require_once("Morpheus/api.php");

use Services\ActivityLogger;
use Services\Campaign\Archiver\BulkTargetArchiver;

$cronid = getenv('CRON_ID');
$tags = api_cron_tags_get($cronid);

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

// The working limit is the max number of targets that will be archived at once
if (isset($tags['working-limit']) && is_numeric($tags['working-limit'])) {
	$workingLimit = (int)$tags['working-limit'];
} else {
	$workingLimit = 50000;
}

echo "Running with settings:\n";
echo "Start: ".$start->format("Y-m-d H:i:s")."\n";
echo "End: ".$end->format("Y-m-d H:i:s")."\n";
echo "Worklimit: ".$workingLimit."\n\n";

if($end->diff(new DateTime())->y < 1) {
	echo "For safety this tool will not mass delete targets if the end-run-date is less than one year ago\n";
	exit(1);
}

$campaigns = api_campaigns_get_campaigns_lastsend_during_period($start, $end);

echo "Archiving ".count($campaigns)." campaigns:\n";
echo implode(", ", $campaigns)."\n";

$totalTargets = 0;
foreach ($campaigns as $campaignId) {
	// Check if the campaign has activity after the end date, if so leave it
	if (api_data_responses_campaign_get_response_count($campaignId, $end)) {
		echo "Campaign {$campaignId} has responses after the period, skipping.\n";
		continue;
	}

    $archiver = new BulkTargetArchiver($workingLimit, ActivityLogger::getInstance());
    try {
        api_db_starttrans();

        $archivedTargetCount = $archiver->archiveCampaign($campaignId);
        $totalTargets += $archivedTargetCount;

        if($archivedTargetCount) {
            echo "Archived {$archivedTargetCount} for campaign id: {$campaignId}\n";
            echo "Deleting {$archivedTargetCount} from campaign id: {$campaignId}\n\n";
            api_targets_delete_all($campaignId);
        }
        api_db_endtrans();
    } catch (Exception $e) {
        echo implode("\n", $archiver->getLog());
        api_db_failtrans();
        exit(1) ;
    }
}
echo "Archived and deleted {$totalTargets}\n";

