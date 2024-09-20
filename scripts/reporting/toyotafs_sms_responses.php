#!/usr/bin/php
<?php

use Services\Customers\Branding\BrandingFactory;
use Services\Customers\Toyota\Autoload\AutoloadStrategy;

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(37);

if (!isset($tags['usergroup-ids']) || !isset($tags['sms-did-ids'])) {
	$message = 'Missing mandatory tags';
	print $message;
	api_error_raise($message);
	exit;
}

$groupids = explode(',', $tags['usergroup-ids']);

if (isset($tags['run-date'])) {
	try {
		$start = (new DateTime($tags['run-date']));
	}catch(\Exception $e){
		print "Invalid run date given: '".$tags['run-date']."' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
		exit;
	}
}else{
	$start = (new DateTime("yesterday 00:00:00"));
}

if (isset($tags['end-run-date'])) {
	try {
		$end = (new DateTime($tags['end-run-date']));
	}catch(\Exception $e){
		print "Invalid end run date given:  '".$tags['end-run-date']."' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
		exit;
	}
}else{
	$end = (new DateTime("yesterday 23:59:59"));
}

$dids = explode(',', $tags['sms-did-ids']);

print "Running from ".$start->format(DateTime::W3C)." to ".$end->format(DateTime::W3C)."\n";

$messages = api_sms_get_received_sms($dids, $start, $end) ;

if(empty($messages)){
    print "No messages found for the report, exiting...\n";
    exit;
}

foreach($messages as $messageData){

	$targetid = api_targets_findrecentsms($messageData["from"], $messageData['sms_account']);

	if(is_numeric($targetid)){

		$target = api_targets_getinfo($targetid);

		$md = api_data_merge_get_all($target["campaignid"], $target["targetkey"]);
		if($md){
		    foreach($md as $element => $value){
		        $elements[$element] = 1;
		    }
		}

		$response[$messageData["smsid"]] = array("targetkey" => $target["targetkey"], "destination" => $target["destination"], "time" => $messageData["received"], "message" => $messageData["contents"], "merge_data" => $md);

	} else {
		$number = api_data_numberformat($messageData["from"]);
		$response[$messageData["smsid"]] = array("targetkey" => "", "destination" => $number["fnn"], "time" => $messageData["received"], "message" => $messageData["contents"]);
	}
}

$contents =  "targetkey,destination,time,message,";
$contentsWithBrand = $contents;

$mergedata = "chBPAYnumber,chCollectionsProduct,chLease,chSortOrder,cRiskLevel,ExtractDt,iArrearsDays,mArrearsTot,vchCustName,vchPostcode,vchState,Campaign,CurrentInstalment,PaymentFrequency,ScheduledPymt";

$fields = explode(",", $mergedata);

$contents .= $mergedata . "\n";
$contentsWithBrand .= $mergedata . "," . AutoloadStrategy::BRAND_COLUMN_NAME . "\n";

if($response == null) exit;

$brands = [];
$brandsFactory = new BrandingFactory();

foreach($response as $sms => $value) {
	$data = "\"" . $value["targetkey"] . "\",\"" . $value["destination"] . "\",\"" . $value["time"] . "\",\"" . preg_replace("/\n/", " ", $value["message"]) . "\",";
	foreach($fields as $k) {
		if(isset($value["merge_data"][$k])) $data .= "\"" . $value["merge_data"][$k] . "\",";
		else $data .= ",";
	}
	$contents .= $data;
	$contentsWithBrand .= $data;
	if (isset($value['merge_data'][AutoloadStrategy::BRAND_COLUMN_NAME])) {
		if (!isset($brands[$value['merge_data'][AutoloadStrategy::BRAND_COLUMN_NAME]])) {
			$brand = $brandsFactory
				->build($value['merge_data'][AutoloadStrategy::BRAND_COLUMN_NAME]);
			$brands[$value['merge_data'][AutoloadStrategy::BRAND_COLUMN_NAME]] = ($brand !== false ? $brand->getValue() : $brand);
		}

		$contentsWithBrand .= '"' . ($brands[$value['merge_data'][AutoloadStrategy::BRAND_COLUMN_NAME]] ? : "") . '",';
	} else {
		$contentsWithBrand .= ',';
	}

	$contents .= "\n";
	$contentsWithBrand .= "\n";
}

$sql = "SELECT `userid`, COUNT(*) as `count`, SUM(`messageunits`) as `sum` FROM `sms_api_mapping` WHERE `timestamp` >= ? AND `timestamp` <= ? AND `userid` IN (";
$variables = array($start->format("Y-m-d H:i:s"), $end->format("Y-m-d H:i:s"));

foreach(api_users_list_all_by_groupowners($groupids) as $userid) {
        $sql .= "?, ";
        $variables[] = $userid;
        $userdata[$userid] = api_users_setting_getall($userid);
}

$sql = substr($sql, 0, -2) . ") GROUP BY `userid`";
$rs = api_db_query_read($sql, $variables);
$statistical = "username,description,count,billableunits\n";

while($rs && !$rs->EOF){

    $username = isset($userdata[$rs->Fields("userid")]["username"]) ? $userdata[$rs->Fields("userid")]["username"] : '' ;
    $description = isset($userdata[$rs->Fields("userid")]["description"]) ? $userdata[$rs->Fields("userid")]["description"] : '';

	$statistical .= $username
        . "," . $description
        . "," . $rs->Fields("count")
        . "," . $rs->Fields("sum") . "\n";

	$rs->MoveNext();
}

$email["to"]          = $tags["reporting-emailaddress"];
$email["subject"]     = "[ReachTEL] Toyota Financial Services - Daily SMS response report - " . date("Y-m-d", strtotime("yesterday"));
$email["textcontent"] = "Hello,\n\nPlease find attached the SMS response report for today.\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the SMS response report for today.<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contentsWithBrand, "filename" => "TFS-Daily-SMS-" . date("Ymd", strtotime("yesterday")) . ".csv");
$email["attachments"][] = array("content" => $statistical, "filename" => "TFS-Daily-SMS-Statistical-" . date("Ymd", strtotime("yesterday")) . ".csv");

api_email_template($email);

// SFTP the TFS-Daily-SMS file

$filename = "ReachTel_SMS_" . date("Ymd") . ".csv";

$tempfname = tempnam("/tmp", "toyotafs");

file_put_contents($tempfname, $contents);

$options = array("hostname"  => $tags["sftp-hostname"],
	"username"  => $tags["sftp-username"],
    "password"  => $tags["sftp-password"],
	"localfile" => $tempfname,
	"remotefile" => $tags["sftp-path"] . $filename);

$result = api_misc_sftp_put_safe($options);

unlink($tempfname);

if(!$result) {
	print "Failed to upload to SFTP\n";
	exit;

}
