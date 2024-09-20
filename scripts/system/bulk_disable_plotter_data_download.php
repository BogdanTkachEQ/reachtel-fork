<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

// This file can be removed in the next release once it is executed

require_once("Morpheus/api.php");

$cronid = getenv('CRON_ID');
$tags = api_cron_tags_get($cronid);


if (!isset($tags['createdbefore'])) {
    print "Failed because 'createdbefore' is a mandatory tag and is not set";
    exit;
}

// The working limit is the max number of merge data that will be removed
if (isset($tags['working-limit']) && is_numeric($tags['working-limit'])) {
    $workingLimit = (int)$tags['working-limit'];
} else {
    $workingLimit = 1000000;
}

$created = DateTime::createFromFormat('d-m-Y', $tags['createdbefore']);
$created = $created->getTimestamp();

$sql = 'SELECT k1.id as id FROM key_store k1 join key_store k2 on (k1.id=k2.id and k1.type=k2.type and k1.item="disabledownload" and k2.item="created") where k1.type="CAMPAIGNS" AND k2.value<? AND k1.value=1';

$campaignIds = [];
$rs = api_db_query_read($sql, [$created]);
print "Fetching all campaign Ids that has plotter data.\n";
while ($row = $rs->FetchRow()) {
    $campaignIds[] = $row['id'];
}
print "Finished fetching campaign ids. " . count($campaignIds) . " in total.\n";

$sql = 'DELETE FROM merge_data 
WHERE element IN 
("state", "surname", "initial", "gender", "agebracket", "street", "lat", "long", "point", "Coalition_Score", "Labor_Score", "Green_Score", "Undecided_Score")
AND campaignId IN (' . implode(',', array_fill(0, count($campaignIds), '?')) . ')
LIMIT ' . $workingLimit;

print "Removing plotter data from these campaigns\n";
api_db_query_write($sql, $campaignIds);

print "Removed data";
