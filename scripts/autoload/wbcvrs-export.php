<?php

require_once("Morpheus/api.php");

define("ONE_DAY", 86400);

$timezone = "Australia/Sydney";
date_default_timezone_set($timezone);

$data = array();

foreach($argv as $key => $campaignid){

	if($key == 0) continue;

	if(!api_campaigns_checkidexists($campaignid)) api_error_raise("Sorry, that is not a valid campaignid");

	$settings[$campaignid] = api_campaigns_setting_getall($campaignid);
	$tags[$campaignid] = api_campaigns_tags_get($campaignid);

	if(($settings[$campaignid]["groupowner"] != 138) OR empty($tags[$campaignid]["VND-ID"])) api_error_raise("Sorry, that doesn't appear to be a valid Westpac VRS campaign");

	if($settings[$campaignid]["type"] == "phone") $data[$campaignid] = api_data_responses_phone_report($campaignid);
	elseif($settings[$campaignid]["type"] == "sms") $data[$campaignid] = api_data_responses_sms_report($campaignid);
	else api_error_raise("Sorry, that is not a valid campaign type");

	$rundate = mktime(0, 0, 0, substr($tags[$campaignid]["PROC-DATE"], 4, 2), substr($tags[$campaignid]["PROC-DATE"], 6, 2), substr($tags[$campaignid]["PROC-DATE"], 0, 4));

	// Header row
	$content = "0" . str_pad($tags[$campaignid]["VND-ID"], 10) . date("Ymd") . str_pad($tags[$campaignid]["BATCH-ID"], 25) . str_pad(" ", 717) . "\n";

	$host = $tags[$campaignid]["HOST"];
	$system = $tags[$campaignid]["SYSTEM"];
	$vndid = $tags[$campaignid]["VND-ID"];
	$type = $settings[$campaignid]["type"];

	$campaigns[] = $campaignid;
}

$records = array();

if(!empty($data)) foreach($data as $campaignid => $results) if(!empty($results)) foreach($results as $targetid => $result){

 	if(empty($result["merge_data"]["ROWID"])) api_error_raise("Sorry, the data doesn't seem to be in the correct format");

	if(!($result["priority"] % 4)) $result["merge_data"]["DEVICE-TYPE"] = "O";
	elseif(!($result["priority"] % 3)) $result["merge_data"]["DEVICE-TYPE"] = "W";
	elseif(!($result["priority"] % 2)) $result["merge_data"]["DEVICE-TYPE"] = "H";
	else $result["merge_data"]["DEVICE-TYPE"] = "M";

	if($result["priority"] < 5) $rectype = "1";
	else $rectype = "2";

	if(isset($result["events"])) $oldestEvent = api_misc_oldestevent($result["events"]);
	else $oldestEvent = null;

	$firstname = $result["merge_data"][$rectype . "CUST-1ST-NAME"];

	if($system == "TCS") {

		$firstname = substr($firstname, 0, 10);
		$rowid = $result["merge_data"]["ACCT-ID"];

		if($rectype == 1){

			$records[$rowid]["merge_data"] = $result["merge_data"];
			$records[$rowid]["custid"] = $result["merge_data"][$rectype . "CUST-ID"];
		        $records[$rowid]["rectype"] = $rectype;

		}

	} else {

		$rowid = $result["merge_data"]["ROWID"];

		if(empty($records[$rowid]["merge_data"])) $records[$rowid]["merge_data"] = $result["merge_data"];

		$records[$rowid]["custid"] = $result["merge_data"][$rectype . "CUST-ID"];
		$records[$rowid]["rectype"] = $rectype;

	}

	if(isset($result["response_data"]["3_OPTION"]) AND ($result["response_data"]["3_OPTION"] == "CUSTOMER_SERVICE")){

		$event = array("activityCode" => "P" . $result["merge_data"]["DEVICE-TYPE"] . "CTB",
			     "activitySkedDate" => date("dmY", $result["response_data"]["ACTIONTIMESTAMP"] + (ONE_DAY * 3)),
			     "activityDateTime" => date("d/m/Y H:i:s", $result["response_data"]["ACTIONTIMESTAMP"]),
			     "promiseActivityCode" => "",
			     "promiseActivityDate" => "",
			     "promiseAmount" => "",
			     "processCompleteDateTime" => date("Y/m/d H:i:s", $result["response_data"]["ACTIONTIMESTAMP"]),
			     "rectype" => $rectype);


		if($system == "TCS") $event["activityMessage"] = "VRS OUT CALL; VERIFIED; " . $firstname . "; TXFR PH BNK";
		else $event["activityMessage"] = "AUTOMATED OUTBOUND CALL; VERIFIED; " . $firstname . "; TXFR TELEPHONE BANKING";

		$records[$rowid]["OUTCOME"][$oldestEvent] = $event;

	} else if(isset($result["response_data"]["3_OPTION"]) AND ($result["response_data"]["3_OPTION"] == "PROMISE")){

		$event = array("activitySkedDate" => date("dmY", api_misc_addbusinessdays($result["response_data"]["ACTIONTIMESTAMP"], 5)),
			     "activityDateTime" => date("d/m/Y H:i:s", $result["response_data"]["ACTIONTIMESTAMP"]),
			     "promiseActivityDate" => date("dmY", api_misc_addbusinessdays($result["response_data"]["ACTIONTIMESTAMP"], 5)),
			     "promiseAmount" => $result["response_data"]["PROMISEAMOUNT"],
			     "processCompleteDateTime" => date("Y/m/d H:i:s", $result["response_data"]["ACTIONTIMESTAMP"]),
			     "rectype" => $rectype);

		if($system == "TCS") {

			$event["activityCode"] = "P";
			$event["activityMessage"] = "VRS OUT CALL; VERIFIED;" . $firstname . "; PROM OBT";
			$event["promiseActivityCode"] = "P";

		} else {

			$event["activityCode"] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "CP";
			$event["activityMessage"] = "AUTOMATED OUTBOUND CALL; VERIFIED; " . $firstname . "; PROMISE OBTAINED";
			$event["promiseActivityCode"] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "CP";

		}

		$records[$rowid]["OUTCOME"][$oldestEvent] = $event;

	} else if(isset($result["response_data"]["3_OPTION"]) AND ($result["response_data"]["3_OPTION"] == "ALREADY_PAID")){

		$event = array("activitySkedDate" => date("dmY", api_misc_addbusinessdays($result["response_data"]["ACTIONTIMESTAMP"], 3)),
			     "activityDateTime" => date("d/m/Y H:i:s", $result["response_data"]["ACTIONTIMESTAMP"]),
			     "promiseActivityDate" => date("dmY", api_misc_addbusinessdays($result["response_data"]["ACTIONTIMESTAMP"], 3)),
			     "promiseAmount" => "",
			     "processCompleteDateTime" => date("Y/m/d H:i:s", $result["response_data"]["ACTIONTIMESTAMP"]),
			     "rectype" => $rectype);

		if($system == "TCS"){

			$event["activityCode"] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "CO";
			$event["activityMessage"] = "VRS OUT CALL; VERIFIED; " . $firstname . "; CUST CONF PAID";
			$event["promiseActivityCode"] = "";

		} else {

			$event["activityCode"] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "CP";
			$event["activityMessage"] = "AUTOMATED OUTBOUND CALL; VERIFIED; " . $firstname . "; PAYMENT MADE";
			$event["promiseActivityCode"] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "CP";
		}

		$records[$rowid]["OUTCOME"][$oldestEvent] = $event;

	} else if(isset($result["response_data"]["2_DOBVALIDATE"]) AND ($result["response_data"]["2_DOBVALIDATE"] == "PASS")){

		$event = array("activityCode" => "P" . $result["merge_data"]["DEVICE-TYPE"] . "CO",
			     "activitySkedDate" => date("dmY", $result["response_data"]["AUTHTIMESTAMP"] + (ONE_DAY * 3)),
			     "activityDateTime" => date("d/m/Y H:i:s", $result["response_data"]["AUTHTIMESTAMP"]),
			     "promiseActivityCode" => "",
			     "promiseActivityDate" => "",
			     "promiseAmount" => "",
			     "processCompleteDateTime" => date("Y/m/d H:i:s", $result["response_data"]["AUTHTIMESTAMP"]),
			     "rectype" => $rectype);

		if($system == "TCS") $event["activityMessage"] = "VRS OUT CALL; VERIFIED; " . $firstname;
		else $event["activityMessage"] = "AUTOMATED OUTBOUND CALL; VERIFIED; " . $firstname;

		$records[$rowid]["OUTCOME"][$oldestEvent] = $event;

	} else if(isset($result["response_data"]["2_DOBVALIDATE"]) AND ($result["response_data"]["2_DOBVALIDATE"] == "FAIL")){

		$event = array("activityDateTime" => date("d/m/Y H:i:s", $result["response_data"]["ANSWERTIMESTAMP"]),
			     "promiseActivityCode" => "",
			     "promiseActivityDate" => "",
			     "promiseAmount" => "",
			     "processCompleteDateTime" => date("Y/m/d H:i:s", $result["response_data"]["ANSWERTIMESTAMP"]),
			     "rectype" => $rectype);

		if($system == "TCS"){

			$event["activitySkedDate"] = date("dmY", $result["response_data"]["ANSWERTIMESTAMP"] + (ONE_DAY * 1));
			$event["activityCode"] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "OO";
			$event["activityMessage"] = "VRS OUT CALL; NOT VERIFIED; " . $firstname;

		} else {

			$event["activitySkedDate"] = date("dmY", $result["response_data"]["ANSWERTIMESTAMP"] + (ONE_DAY * 3));
			$event["activityCode"] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "DBF";
			$event["activityMessage"] = "AUTOMATED OUTBOUND CALL; DATE OF BIRTH FAIL; " . $firstname;
		}

		$records[$rowid]["OUTCOME"][$oldestEvent] = $event;

	} else if(isset($result["response_data"]["0_AMD"]) AND ($result["response_data"]["0_AMD"] == "MACHINE")){

		$records[$rowid]["CALL-PROGRESS"][] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "AM";

		$event = array("activitySkedDate" => date("dmY", $result["response_data"]["ANSWERTIMESTAMP"] + ONE_DAY),
			     "activityDateTime" => date("d/m/Y H:i:s", $result["response_data"]["ANSWERTIMESTAMP"]),
			     "promiseActivityCode" => "",
			     "promiseActivityDate" => "",
			     "promiseAmount" => "",
			     "processCompleteDateTime" => date("Y/m/d H:i:s", $result["response_data"]["ANSWERTIMESTAMP"]),
			     "rectype" => $rectype);

		if($system == "TCS"){

			$event["activityCode"] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "OO";
			$event["activityMessage"] = "VRS OUT CALL; NOT VERIFIED; " . $firstname;

		} else {

			$event["activityCode"] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "AM";
			$event["activityMessage"] = "AUTOMATED OUTBOUND CALL; NOT VERIFIED; AMD NO MSG";

		}

		$records[$rowid]["OUTCOME"][$oldestEvent] = $event;

	} else if(isset($result["response_data"]["ANSWERTIMESTAMP"])){

		$event = array("activityCode" => "P" . $result["merge_data"]["DEVICE-TYPE"] . "OO",
			     "activitySkedDate" => date("dmY", $result["response_data"]["ANSWERTIMESTAMP"] + ONE_DAY),
			     "activityDateTime" => date("d/m/Y H:i:s", $result["response_data"]["ANSWERTIMESTAMP"]),
			     "promiseActivityCode" => "",
			     "promiseActivityDate" => "",
			     "promiseAmount" => "",
			     "processCompleteDateTime" => date("Y/m/d H:i:s", $result["response_data"]["ANSWERTIMESTAMP"]),
			     "rectype" => $rectype);


		if($system == "TCS") $event["activityMessage"] = "VRS OUT CALL; NOT VERIFIED; " . $firstname;
                else $event["activityMessage"] = "AUTOMATED OUTBOUND CALL; NOT VERIFIED; " . $firstname;

		$records[$rowid]["OUTCOME"][$oldestEvent] = $event;

	} else if(($settings[$campaignid]["type"] == "phone") AND (isset($result["response_data"]["DISCONNECTED"]) OR (isset($result["response_data"]["REMOVED"]) AND ($result["response_data"]["REMOVED"] == "DNC")))){

		$event = array("activitySkedDate" => date("dmY", $oldestEvent + ONE_DAY),
			     "activityDateTime" => date("d/m/Y H:i:s", $oldestEvent),
			     "promiseActivityCode" => "",
			     "promiseActivityDate" => "",
			     "promiseAmount" => "",
			     "processCompleteDateTime" => date("Y/m/d H:i:s", $oldestEvent),
			     "rectype" => $rectype);

		if($system == "TCS"){

			$event["activityCode"] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "OO";
			$event["activityMessage"] = "VRS OUT CALL; NOT VERIFIED; " . $firstname;

		} else {

			$event["activityCode"] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "D";
			$event["activityMessage"] = "AUTOMATED OUTBOUND CALL; NOT VERIFIED; DIAL ERROR";
		}

		$records[$rowid]["OUTCOME"][$oldestEvent] = $event;

	} else if(($settings[$campaignid]["type"] == "phone") AND (api_misc_hasevent($result["events"], "CANCEL") OR api_misc_hasevent($result["events"], "CONGESTION"))) {

		$records[$rowid]["CALL-PROGRESS"][] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "NA";

		$event = array("activitySkedDate" => date("dmY", $oldestEvent + ONE_DAY),
			     "activityDateTime" => date("d/m/Y H:i:s", $oldestEvent),
			     "promiseActivityCode" => "",
			     "promiseActivityDate" => "",
			     "promiseAmount" => "",
			     "processCompleteDateTime" => date("Y/m/d H:i:s", $oldestEvent),
			     "rectype" => $rectype);

                if($system == "TCS"){

                        $event["activityCode"] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "OO";
                        $event["activityMessage"] = "VRS OUT CALL; NOT VERIFIED; " . $firstname;

                } else {

			$event["activityCode"] = "P" . $result["merge_data"]["DEVICE-TYPE"] . "NA";
			$event["activityMessage"] = "AUTOMATED OUTBOUND CALL; NOT VERIFIED; " . $firstname;
		}

		$records[$rowid]["OUTCOME"][$oldestEvent] = $event;

	} else if(isset($result["response_data"]["UNDELIVERED"]) OR (isset($result["response_data"]["REMOVED"]) AND ($result["response_data"]["REMOVED"] == "DNC"))){

		$event = array("activitySkedDate" => date("dmY", $oldestEvent + ONE_DAY),
			     "activityDateTime" => date("d/m/Y H:i:s", $oldestEvent),
			     "promiseActivityCode" => "",
			     "promiseActivityDate" => "",
			     "promiseAmount" => "",
			     "processCompleteDateTime" => date("Y/m/d H:i:s", $oldestEvent),
			     "rectype" => $rectype);

                if($system == "TCS"){

                        $event["activityCode"] = "SMS" . $result["merge_data"]["DEVICE-TYPE"] . "F";
                        $event["activityMessage"] = "SMS UNSUCCESSFUL; " . $firstname;

                } else {

			$event["activityCode"] = "SMS" . $result["merge_data"]["DEVICE-TYPE"] . "F";
			$event["activityMessage"] = "SMS UNSUCCESSFUL; " . $firstname;
		}

		$records[$rowid]["OUTCOME"][$oldestEvent] = $event;

	} else if(isset($result["response_data"]["SENT"])){

		$oldestEvent = strtotime($result["response_data"]["SENT"]);

		$event = array("activityCode" => "P" . $result["merge_data"]["DEVICE-TYPE"] . "SMS",
			     "activitySkedDate" => date("dmY", $oldestEvent + (ONE_DAY * 3)),
			     "activityDateTime" => date("d/m/Y H:i:s", $oldestEvent),
			     "promiseActivityCode" => "",
			     "promiseActivityDate" => "",
			     "promiseAmount" => "",
			     "processCompleteDateTime" => date("Y/m/d H:i:s", $oldestEvent),
			     "rectype" => $rectype);

                if($system == "TCS") $event["activityMessage"] = "PH MOBILE NUMBER FIELD; SMS MESSAGE SENT; " . $firstname;
                else $event["activityMessage"] = "PHONED MOBILE NUMBER FIELD, SMS MESSAGE SENT; " . $firstname;

		$records[$rowid]["OUTCOME"][$oldestEvent] = $event;
	}

}

// Output the results
ksort($records);

foreach($records as $rowid => $record){

	if(empty($record["CALL-PROGRESS"])) $callProgress = "";
	else {

		$callProgress = "";

		while((count($record["CALL-PROGRESS"]) > 0) AND (strlen($callProgress) < 45)) $callProgress .= array_pop($record["CALL-PROGRESS"]) . "; ";

		$callProgress = substr($callProgress, 0, -1);

	}

	if(empty($record["OUTCOME"])) $record["OUTCOME"] = array();

	ksort($record["OUTCOME"]);

	if(!empty($record["OUTCOME"])) $outcome = array_pop($record["OUTCOME"]);
	else $outcome = array("activityCode" => null, "activitySkedDate" => null, "activityDateTime" => null, "activityMessage" => null, "promiseActivityCode" => null, "promiseActivityDate" => null, "promiseAmount" => null, "processCompleteDateTime" => null);

	$outcome["promiseAmount"] = $outcome["promiseAmount"] ? sprintf("%01.2f", $outcome["promiseAmount"]) : '';

	if ($system == 'TCS' && $outcome["promiseAmount"]) {
		$outcome["promiseAmount"] *= 100;
	}

	$content .= $record["rectype"];
	$content .= str_pad($record["merge_data"]["CORP"], 3);
	$content .= str_pad($record["custid"], 30);
	$content .= str_pad($record["merge_data"]["APPL"], 3);
	$content .= str_pad($record["merge_data"]["CCG"], 3);
	$content .= str_pad($record["merge_data"]["ACCT-ID"], 17);
	$content .= str_pad($outcome["activityCode"], 5);
	$content .= str_pad($outcome["activitySkedDate"], 8);
	$content .= str_pad($outcome["activityDateTime"], 19);
	$content .= str_pad($outcome["activityMessage"], 75);
	$content .= str_pad($outcome["promiseActivityCode"], 5);
	$content .= str_pad($outcome["promiseActivityDate"], 8);
	$padString = $outcome['promiseAmount'] ? '0' : ' ';
	$content .= str_pad($outcome["promiseAmount"], 15, $padString, STR_PAD_LEFT);
	$content .= str_pad($outcome["processCompleteDateTime"], 19);
	$content .= str_pad($callProgress, 50);
	$content .= str_pad(" ", 289);
	$content .= "\n";
}

// Trailer row
$content .= "9" . str_pad(count($records), 7, "0", STR_PAD_LEFT) . str_pad(" ", 753) . "\n";

print "Dumping file...";

if(($system == "TM") AND ($type == "phone")) $file = array("content" => $content, "filename" => $system . "_" . $vndid . "_RESULT_VRS_" . date("Ymd") . ".txt");
elseif(($system == "TM") AND ($type == "sms")) $file = array("content" => $content, "filename" => $system . "_" . $vndid . "_RESULT_SMS_" . date("Ymd") . ".txt");
else $file = array("content" => $content, "filename" => $system . "_" . $vndid . "_RESULT_" . date("Ymd") . ".txt");

//print $file["content"]; exit;
//file_put_contents("/tmp/" . $file["filename"], $file["content"]); exit;

$tmpfname = tempnam("/tmp", "westpac-vrs");

$tags = api_cron_tags_get(16);

$file = api_misc_pgp_encrypt($file, $tags["westpac-sterling-" . $host . "-pgp"]);

if($file == false) api_error_raise("Failed to encrypt the content");

if(!file_put_contents($tmpfname, $file["content"])) api_error_raise("Failed to write file");

print "OK!\n";

print "Uploading via SFTP...";

$options = [
    "hostname"   => $tags["sftp-hostname"],
    "username"   => $tags["sftp-username"],
    "password"   => $tags["sftp-password"],
    "localfile"  => $tmpfname,
    "remotefile" => $tags['sftp-path-' . $host] . "/" . $file["filename"],
];

$result = api_misc_sftp_put_safe($options);

if(!$result) {

    print "Failed to upload " . $file["filename"] . " report to sFTP server. Result=" . serialize($result) . "\n";
    exit;

} else {

	print "Upload to Westpac sFTP seems to have worked:" . serialize($result) . "\n";

	foreach($campaigns as $campaignid) api_campaigns_tags_set($campaignid, array("RETURNTIMESTAMP" => time()));

}

unlink($tmpfname);

print "OK!\n";

print "Job done!\n";
