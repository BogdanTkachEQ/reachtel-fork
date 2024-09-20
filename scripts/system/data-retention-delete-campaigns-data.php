<?php

require_once(__DIR__ . '/../../api.php');

use Services\ActivityLogger;
use Services\Utils\ActivityLoggerActions;

// @TODO create a date option to delete campaign data for a period of time

die(
	"\033[1;41mThis script exit on purpose.\nYou need to uncomment line " . __LINE__.
	" to be able to use it\n\033[0;0m"
);

if (!isset($argv[1]) || !$argv[1]) {
	die("\033[1;31mERROR: You need to pass the group id as 1st argument\n\033[0;0m");
}

$groupid = (int) $argv[1];
if (!api_groups_checkidexists($groupid)) {
	die("\033[1;31mERROR: The group id '{$groupid}' does not exists\n\033[0;0m");
}

$groupid = $argv[1];
if (ADMIN_GROUP_OWNER_ID == $groupid) {
	die("\033[1;31mERROR: You can not delete data from the admin group!\n\033[0;0m");
}
$name = api_groups_setting_getsingle($groupid, 'name');

if ('Y' === readline("Are you sure you want to delete all campaign data of group {$name} (id={$groupid}) (Y/n) ? ")) {

	echo "\nStarting deleting campaign data.\n";
	sleep(5); // give a last chance to the most unlucky clumsy dev ever

	echo "Fetching campaign... ";

	$campaignIds = api_groups_get_all_campaignids($groupid);

	echo "Found " . count($campaignIds) . "\n\n";

	$table_names_with_campaignid_field = [
		'response_data',
		'response_data_archive',
		'call_results',
		'call_results_archive',
		'merge_data',
		'merge_data_archive',
		'targets',
		'targets_archive',
	];
	foreach($campaignIds as $campaign) {
		$campaignid = $campaign['id'];
		$name = api_campaigns_setting_getsingle($campaignid, 'name');
		echo "\033[0;33m{$name}:\n\033[0;0m";

		foreach ($table_names_with_campaignid_field as $table) {
			echo "   * Deleting in {$table}... ";
			$sql = "DELETE FROM `{$table}` WHERE `campaignid` = ?;";
			$rs = api_db_query_write($sql, [$campaignid]);
			if (!$rs) die("\033[0;31mERROR: Query failed:\n{$sql}\n\033[0;0m");
			echo "\033[1;32m✔\033[0;0m\n";

		}

		// Delete campaign
		echo "   * Deleting campaign... ";
		api_keystore_purge("CAMPAIGNS", $campaignid);
		echo "\033[1;32m✔\033[0;0m\n";

		// Delete campaign
		ActivityLogger::getInstance()->addLog(
			KEYSTORE_TYPE_CAMPAIGNS,
			ActivityLoggerActions::ACTION_CAMPAIGN_DELETE,
			'Deleted campaign ' . $campaignid,
			$campaignid
		);
	}
} else {
	die("\033[1;31mAborted!\n\033[0;0m");
}
