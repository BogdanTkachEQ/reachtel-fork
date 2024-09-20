#!/usr/bin/php
<?php
// NOTE: This report needs to be scheduled on the first minute of the first day of every month since we don't rely on
// blling month anymore. It fetches all campaigns that run after the first minute of previous month.

require_once("Morpheus/api.php");

define("RETURN_CARRIERCODE", true);
define("RETURN_HLRCODE", true);

$lastmonth = strtotime("last day of last month");

$sql = "SELECT * FROM `wash_out` WHERE `timestamp` > ? AND `timestamp` < ?";
$rs = api_db_query_read($sql, array(date("Y-m-01 00:00:00", $lastmonth), date("Y-m-t 23:59:59", $lastmonth)));

$tempnam = tempnam("/tmp", "veda");

$fp = fopen($tempnam, "w");

fwrite($fp, "Method,Timestamp,Destination,Status,Carrier,HLRCODE\n");

$rows = 0;

while(!$rs->EOF){

	$carriercode = $rs->Fields("carriercode");

	if(isset($carriercode)) $carrier = api_hlr_supplier_mccmnctoname($carriercode);
	else $carrier = null;

	fwrite($fp, "restapi," . $rs->Fields("timestamp") . "," . $rs->Fields("destination") . "," . $rs->Fields("status") . "," . $carrier . ",\n");

	$rows++;

	$rs->MoveNext();

}

$campaigns = array_intersect(
	api_keystore_getidswithvalue("CAMPAIGNS", "type", "wash"),
	api_campaigns_get_campaigns_sent_after_period(
		DateTime::createFromFormat('d-m-Y H:i:s', date('01-m-Y 00:00:00', $lastmonth))
	)
);

foreach($campaigns as $campaignid){

	$report = api_data_responses_wash_report($campaignid);

	$timestamp = date("Y-m-d H:i:s", api_campaigns_setting_getsingle($campaignid, "created"));

	$region = api_campaigns_setting_getsingle($campaignid, "region");

	foreach($report as $targetid => $results) {

		if(isset($results["response_data"]["carrier"])) $carrier = $results["response_data"]["carrier"];
		else $carrier = null;

		$destination = api_data_numberformat($results["destination"], $region);

		if(is_array($destination)) $destination = $destination["destination"];
		else $destination = $results["destination"];

		fwrite($fp, "campaign," . $timestamp . "," . $destination . "," . (!empty($results["response_data"]["status"]) ? $results["response_data"]["status"] : "") . "," . $carrier . "," . (!empty($results["response_data"]["hlrcode"]) ? $results["response_data"]["hlrcode"] : "") . "\n");

		$rows++;

	}

}

fclose($fp);

$filename = "veda-wash-report-" . date("Ym01", $lastmonth);

$tempnamZip = tempnam("/tmp", "veda");

$zip = new ZipArchive;
if ($zip->open($tempnamZip, ZipArchive::CREATE) === TRUE) {
	$zip->addFile($tempnam, $filename . '.csv');
	$zip->close();
	echo "ZIP ok\n";
} else {
	echo "ZIP failed\n";
}

$tags = api_cron_tags_get(38);

$options = [
    "hostname"   => $tags['sftp-hostname'],
    "username"   => $tags['sftp-username'],
    "password"   => $tags['sftp-password'],
    "localfile"  => $tempnamZip,
    "remotefile" => $tags['sftp-path'] . "/{$filename}.zip",
];

$result = api_misc_sftp_put_safe($options);

unlink($tempnam);
unlink($tempnamZip);

print "Completed. Dumped " . $rows . " rows.";