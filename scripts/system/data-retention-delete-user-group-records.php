#!/usr/bin/php
<?php

use Services\DataRetention\UserGroupRecordsPolicy;
require_once(__DIR__ . "/../../../Morpheus/api.php");


$cronid = getenv('CRON_ID');
if (!$cronid) {
	die("ERROR: Invalid env var CRON_ID\n");
}

$tags = api_cron_tags_get($cronid);
if(isset($tags['user-group-id'])) {
    $groupOwnerId = $tags['user-group-id'];
} else {
    die("ERROR: User Group Id Not Defined\n");
}

if(!api_groups_checkidexists($groupOwnerId)) {
    die("Sorry, that is not a valid group id");
}

echo "Retention User Group set to {$groupOwnerId}\n";
echo "Deleting all records that belongs to Group Owner {$groupOwnerId}...\n";
try {
    $policy = new UserGroupRecordsPolicy($groupOwnerId);	
    $policy->removeDoNotContactLists();
    $policy->removeSMSAndCampaignRecords();
    $policy->removeSMSReceived();
    $policy->removeTargetsOut();
    $policy->removeSMSOutRecords();
    $policy->removeWashOut();
} catch (Exception $e) {
    $email["to"] 	      = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["subject"]     = "[ReachTEL] Error when deleting all records for group={$details["groupid"]}";
    $email["textcontent"] = "Hello,\n\nWe have received an error when deleting all records for group={$details["groupid"]}: " . $e->getMessage();
    $email["htmlcontent"] = "Hello,\n\nWe have received an error when deleting all records for group={$details["groupid"]}: " . $e->getMessage();

    api_email_template($email);
    die("Error when deleting all records for group={$details["groupid"]}: " . $e->getMessage());
}
echo "done!\n";