<?php

// Should run every hour between 12pm and 11pm every day of the week

require_once("Morpheus/api.php");

$timezone = "Australia/Sydney";
date_default_timezone_set($timezone);

$process = array();
$batches = array();

$i = 0;

foreach(api_campaigns_list_all(true, null, 15, array("search" => "WBCCollections-VRS")) as $campaignid => $name){

    $i++;

	$tags = api_campaigns_tags_get($campaignid);
	$settings = api_campaigns_setting_getall($campaignid);

	if(empty($tags["PROC-DATE"]) OR ($tags["PROC-DATE"] != date("Ymd")) OR !empty($tags["RETURNTIMESTAMP"]) OR ($settings["status"] != "DISABLED")) continue;

	if(date("G") < 14) continue;

	if($settings["type"] == "phone") $position = 1;
	else $position = 2;

	$process[$tags["SYSTEM"]][$tags["BATCH-ID"]][$position] = $campaignid;

}

if(empty($process)) exit;

foreach($process as $system => $batchids) foreach($batchids as $batchid => $campaigns){

	sort($campaigns);

	if($system == "TM"){

		if(count($campaigns) != 2) continue; // We can only process Tallyman files once both the voice and SMS campaigns have completed

		if(api_campaigns_setting_getsingle($campaigns[0], "type") == "phone") {

			api_misc_audit("WBC_VRS", "Returning: " . api_campaigns_setting_getsingle($campaigns[0], "name"));

			exec('/usr/bin/php ' . __DIR__ . '/wbcvrs-export.php ' . $campaigns[0]);

			api_misc_audit("WBC_VRS", "Returning: " . api_campaigns_setting_getsingle($campaigns[1], "name"));
			exec('/usr/bin/php ' . __DIR__ . '/wbcvrs-export.php ' . $campaigns[1]);

		} else {

			api_misc_audit("WBC_VRS", "Returning: " . api_campaigns_setting_getsingle($campaigns[1], "name"));

			exec('/usr/bin/php ' . __DIR__ . '/wbcvrs-export.php ' . $campaigns[1]);

			api_misc_audit("WBC_VRS", "Returning: " . api_campaigns_setting_getsingle($campaigns[0], "name"));
			exec('/usr/bin/php ' . __DIR__ . '/wbcvrs-export.php ' . $campaigns[0]);
		}

	} else {

		api_misc_audit("WBC_VRS", "Returning: " . api_campaigns_setting_getsingle($campaigns[0], "name") . " and " . api_campaigns_setting_getsingle($campaigns[1], "name"));
		exec('/usr/bin/php ' . __DIR__ . '/wbcvrs-export.php ' . implode(" ", $campaigns));

	}

}