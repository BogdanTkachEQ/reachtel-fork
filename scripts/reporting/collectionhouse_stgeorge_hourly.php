#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(66);

// Use TAB to delimit the file.
$delimiter = 3;

$timing = ["start" => date("Y-m-d H:00:00", time() - 3600), "finish" => date("Y-m-d H:59:59", time() - 3600)];

$environment = (!empty($tags["environment"])) ? $tags["environment"] : "uat";

$content = "Ual\tCustomerNumber\tcampaign\ttargetid\tdestination\tstatus\tresponse_data\teventid\tevent\ttimestamp\n";

$hasevents = false;

foreach(api_campaigns_list_all(true, null, 15, array("search" => "CollectionHouse-*-" . $environment . "-")) as $campaignid => $name) {

	// Skip anything created more than 14 days ago
	if(api_campaigns_setting_getsingle($campaignid, "created") < time() - 14*86400) {
		continue;
	}

	$data = api_data_responses_phone_report($campaignid, $timing["start"], $timing["finish"]);

	if(empty($data)) {
		continue;
	}

	// Iterate over each target
	foreach($data as $targetid => $result) {

		// Iterate over each event for the target
		foreach($result["events"] as $eventid => $events) {

			foreach($events as $key => $event) {

				if(!isset($event["value"])) {
					continue;
				}

				$hasevents = true;

				$content .= api_data_delimit($result["targetkey"], $delimiter);
				$content .= api_data_delimit($result["merge_data"]["CustomerNumber"], $delimiter);
				$content .= api_data_delimit(api_misc_crypt_safe($campaignid), $delimiter);
				$content .= api_data_delimit(api_misc_crypt_safe($targetid), $delimiter);
				$content .= api_data_delimit($result["destination"], $delimiter);
				$content .= api_data_delimit(strtolower($result["status"]), $delimiter);

				$response_data = "";
				if(isset($result["response_data"])) {
					foreach($result["response_data"] as $key => $value) {
						if(!empty($response_data)) {
							$response_data .= ",";
						}
						$response_data .= $key . ":" . $value;
					}
				}

				$content .= api_data_delimit($response_data, $delimiter);

				$content .= api_data_delimit(api_misc_crypt_safe($eventid), $delimiter);
				$content .= api_data_delimit(strtolower($event["value"]), $delimiter);
				$content .= api_data_delimit(date("r", strtotime($event["timestamp"])), $delimiter);
				$content .= "\n";
			}

		}
	}

}

if(!$hasevents) {
	print "No events to report on. Exiting\n";
	exit;
}

$tempfname = tempnam("/tmp", "ReachTEL-CollectionHouse-Hourly");

if(!file_put_contents($tempfname, $content)) die("Failed to write file");

$filename = "ReachTEL-CollectionHouse-Hourly-" . date("Ymd-H0000", time() - 3600) . ".txt";

$options = array("hostname"  => $tags["sftp-hostname"],
	"username"  => $tags["sftp-username"],
    "password"  => $tags["sftp-password"],
	"localfile" => $tempfname,
	"remotefile" => $tags["sftp-path-" . $environment] . $filename);

$result = api_misc_sftp_put_safe($options);

unlink($tempfname);

if(!$result) {

	print "Failed to upload to SFTP\n";
	exit;

}