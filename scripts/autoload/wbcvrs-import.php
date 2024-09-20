<?php

require_once("Morpheus/api.php");

$timezone = "Australia/Sydney";
date_default_timezone_set($timezone);

$groupid = 138;

if(in_array($argv[2], array("TM", "B2K", "TCS"))) $system = $argv[2];
else {

	print "Failed. That is not a valid system\n";
	exit;

}

if(in_array($argv[3], array("Voice", "SMS"))) $technology = $argv[3];
else {

	print "Failed. That is not a valid technology\n";
	exit;

}

if(in_array($argv[4], array("prod", "test"))) $host = $argv[4];
else {

	print "Failed. That is not a valid host type\n";
	exit;

}

$specification = __DIR__ . "/wbcvrs/wbc-input-" . strtolower($system) . "-specification.csv";

$data = api_misc_wbcvrs_fileprocess($argv[1], $specification);

if(empty($data)) die("Failed to process the file");
else print "Data processed. " . count($data["data"]) . " records processed\n";

print "Creating campaign...";

$campaignname = "WBCCollections-VRS-" . date("jFy") . "-" . $system . "-" . $technology . "-" . $host;

$exists = api_campaigns_checknameexists($campaignname);

if(is_numeric($exists)) {

        print "Failed. The campaign already exists.\n";
        exit;

} else {

        $previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "WBCCollections-VRS-*-" . $system . "-" . $technology. "-" . $host));

	if(empty($previouscampaigns)) {

		print "Failed to find a campaign to duplicate.\n";
		exit;

	}

        $campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));
}

if(!is_numeric($campaignid)){

        print "Failed to create campaign\n";
        exit;

} else print "OK\n";

api_campaigns_tags_set($campaignid, array("BATCH-ID" => $data["header"]["BATCH-ID"], "VND-ID" => $data["header"]["VND-ID"], "PROC-DATE" => $data["header"]["PROC-DATE"], "FILENAME" => basename($argv[1]), "SYSTEM" => $system, "HOST" => $host));

api_campaigns_tags_delete($campaignid, array("RETURNTIMESTAMP"));

$settings = api_campaigns_setting_getall($campaignid);

$i = 0;

foreach($data["data"] as $record){

	if(($system != "TCS") AND ($record["REC-TYPE"] == "2")) continue;

	$i++;

	$record["ROWID"] = $i;

	if($settings["type"] == "phone"){
		if(!empty($record["CLASS-PARAMETER"]) AND !in_array($record["CLASS-PARAMETER"], array("V", "D"))) continue;
		elseif(!empty($record["INSTRUCTION-CODE"]) AND !in_array($record["INSTRUCTION-CODE"], array("531", "1237"))) continue;

	} elseif($settings["type"] == "sms"){
		if(empty($record["CLASS-PARAMETER"]) AND empty($record["INSTRUCTION-CODE"])) continue;
		elseif(!empty($record["INSTRUCTION-CODE"]) AND !in_array($record["INSTRUCTION-CODE"], array("693", "901", "788", "694"))) continue;
		elseif(!empty($record["CLASS-PARAMETER"]) AND !in_array($record["CLASS-PARAMETER"], array("A", "B", "C", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "Q", "R", "S", "T", "U", "W"))) continue;

		// If sms opt out is mentioned, ignore the record.
		if (!empty($record["SMS-MOBILE-PH-OPT"]) AND ($record["SMS-MOBILE-PH-OPT"] == "O")) {
			continue;
		}

		$smsTargetAdded = false;
		// Use the first non empty field as destination
		foreach (['MOBILE-PHONE', 'HOME-PHONE', 'BUSI-PHONE', 'OTHER-NUMB'] as $destination) {
			if (!empty($record[$destination])) {
				$smsTargetAdded = api_targets_add_single($campaignid, $record[$destination], $record["ACCT-ID"], 1 * $record["REC-TYPE"], null, null, array("settings" => $settings, "returntrueonly" => true));
				break;
			}
		}

		if ($smsTargetAdded === false) {
			continue;
		}
	}

	if(($settings["type"] == "phone") AND !empty($record["MOBILE-PHONE"]) AND !(!empty($record["VRS-MOBILE-PH-OPT"]) AND ($record["VRS-MOBILE-PH-OPT"] == "O")) AND !(!empty($record["VRS-HOMEMOBILE-PREFERENCE"]) AND ($record["VRS-HOMEMOBILE-PREFERENCE"] == "N"))) api_targets_add_single($campaignid, $record["MOBILE-PHONE"], $record["ACCT-ID"], 1 * $record["REC-TYPE"], null, null, array("settings" => $settings, "returntrueonly" => true));

	if(($settings["type"] == "phone") AND !empty($record["HOME-PHONE"]) AND !(!empty($record["VRS-HOME-PH-OPT"]) AND ($record["VRS-HOME-PH-OPT"] == "O")) AND !(!empty($record["VRS-HOMEPHONE-PREFERENCE"]) AND ($record["VRS-HOMEPHONE-PREFERENCE"] == "N"))) api_targets_add_single($campaignid, $record["HOME-PHONE"], $record["ACCT-ID"], 2 * $record["REC-TYPE"], null, null, array("settings" => $settings, "returntrueonly" => true));
	if(($settings["type"] == "phone") AND !empty($record["BUSI-PHONE"]) AND !(!empty($record["VRS-WORK-PH-OPT"]) AND ($record["VRS-WORK-PH-OPT"] == "O")) AND !(!empty($record["VRS-WORKPHONE-PREFERENCE"]) AND ($record["VRS-WORKPHONE-PREFERENCE"] == "N"))) api_targets_add_single($campaignid, $record["BUSI-PHONE"], $record["ACCT-ID"], 3 * $record["REC-TYPE"], null, null, array("settings" => $settings, "returntrueonly" => true));
	if(($settings["type"] == "phone") AND !empty($record["OTHER-NUMB"]) AND !(!empty($record["VRS-OTHER-PH-OPT"]) AND ($record["VRS-OTHER-PH-OPT"] == "O")) AND !(!empty($record["VRS-OTHERPHONE-PREFERENCE"]) AND ($record["VRS-OTHERPHONE-PREFERENCE"] == "N"))) api_targets_add_single($campaignid, $record["OTHER-NUMB"], $record["ACCT-ID"], 4 * $record["REC-TYPE"], null, null, array("settings" => $settings, "returntrueonly" => true));

	$uniquefields = array("CUST-ID", "ACCT-NAME", "DOB", "DOBLONG", "CUST-PFX-NAME", "CUST-1ST-NAME", "CUST-MID-NAME", "CUST-LST-NAME");

	foreach($uniquefields as $field) $record[$record["REC-TYPE"] . $field] = $record[$field];

	api_targets_add_extradata_multiple($campaignid, $record["ACCT-ID"], $record);

}

print "OK\n";

print "Deduplicating campaign...";

api_targets_dedupe($campaignid);

print "OK\n";

$tags = api_cron_tags_get(77);

if (isset($tags['activate-campaign']) && $tags['activate-campaign']) {
    print "Activating campaign...";
    if (!api_campaigns_setting_set($campaignid, "status", "ACTIVE")) {
        print "Failed!\n";
        exit;
    }
    print "OK\n";
}

print "Job done!\n";

function api_misc_wbcvrs_fileprocess($filename, $specificationfile){

	global $groupid, $host;

	$tags = api_cron_tags_get(77);

	if($host == "prod") $directory = $tags['sftp-path-prod'];
	else $directory = $tags['sftp-path-test'];

	$options = [
		'hostname' => $tags['sftp-hostname'],
		'username' => $tags['sftp-username'],
		'password' => $tags['sftp-password'],
		'remotefile' => $directory . $filename,
		'localfile' => tempnam("/tmp", "wbc-vrs"),
	];

	if(!api_misc_sftp_get($options)) die("Input file SFTP issue");

	if(!is_readable($specificationfile)) die("Specification file doesn't exist.");

	$specification = api_misc_wbcvrs_specificationprocess($specificationfile);

	if(empty($specification)) die("Sorry, that is not a valid file specification");

	$filecontent = api_misc_pgp_decrypt(file_get_contents($options['localfile']));

	if(!$filecontent) die("Failed to open and decrypt the file.");

	unlink($options['localfile']);

	$data = array("header" => array(), "data" => array(), "trailer" => array());

	print "Processing...\n";

	if(!preg_match("/^0(VND[0-9]{2}     )(.{8})(.{25})/", $filecontent)) die("That doesn't look like a valid file");

	foreach(preg_split("/\R/", $filecontent) as $row){

		if(preg_match("/^0(VND[0-9]{2}     )(.{8})(.{25})/", $row, $matches)) {

			// Header row

			$data["header"] = array("VND-ID" => trim($matches[1]), "PROC-DATE" => trim($matches[2]), "BATCH-ID" => trim($matches[3]));

			print "Importing batch ID " . $data["header"]["BATCH-ID"] . "\n";

		} else if (preg_match("/^9(.{7})/", $row, $matches)) {

			// Trialer row

			$data["trailer"] = array("TOTAL-REC" => (int)$matches[1]);

		} else if (preg_match("/^[12]/", $row, $matches)) {

			// Data row

			$data["data"][] = api_misc_wbcvrs_dataprocess($row, $specification);

		} else {

			// Blank or malformed row

		}

	}

	if(count($data["data"]) != $data["trailer"]["TOTAL-REC"]) return api_error_raise("Record count mismatch! Received " . count($data["data"]) . " records and file said there should be " . $data["trailer"]["TOTAL-REC"] . " records.");

	return $data;
}

function api_misc_wbcvrs_dataprocess($row, $format){

	if(empty($row)) return api_error_raise("Sorry, that is not a valid data row");

	if(empty($format)) return api_error_raise("Sorry, that is not a valid format description");

	$items = array();

	foreach($format as $item) {
		$value = trim(substr($row, $item["start"] -1, $item["length"]));

		if(preg_match("/^0+(\\$.+)$/", $value, $matches)) $items[$item["name"]] = $matches[1];
		else $items[$item["name"]] = $value;

		if(in_array($item["name"], array("AMT-DUE", "BALANCE" ,"AI-DUE", "AMT-OVER-LIMIT", "PROMISE-AMOUNT")) AND (strpos($value, ".") === false)) $items[$item["name"]] = "$" . sprintf("%01.2f", $value / 100);

		if($item["name"] == "DOB") {
			$dayofbirth = mktime(0, 0, 0, substr($value, 4, 2), substr($value, 6, 2), 2016);

			$items["DOBLONG"] = date("jS F", $dayofbirth);
		}
	}

	return $items;

}

function api_misc_wbcvrs_specificationprocess($filename){

	$handle = fopen($filename, "r");

	if(!$handle) die("Failed to open specification file.");

	$row = 0;

	$specification = array();

	while(($line = fgetcsv($handle, 4096)) !== false) {

		if($row == 0){

			if(!in_array("name", $line) OR !in_array("length", $line) OR !in_array("start", $line)) return api_error_raise("Sorry, that is not a valid specification header row");
			else {

				$namepos = array_search("name", $line);
				$startpos = array_search("start", $line);
				$lengthpos = array_search("length", $line);

			}

		} else $specification[] = array("name" => $line[$namepos], "start" => $line[$startpos], "length" => $line[$lengthpos]);

	        $row++;
	}

	fclose($handle);

	return $specification;
}
