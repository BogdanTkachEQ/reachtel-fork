#!/usr/bin/php
<?php

use Services\Customers\Branding\BrandingFactory;
use Services\Customers\Toyota\Autoload\AutoloadStrategy;

/**
 *
$testdata = [[
"UNIQUEID"=>"571466427",
"DESTINATION"=>"1800284123",
"STATUS"=>"COMPLETE",
"vchFinancier"=>"Power Alliance Finance",
"DURATIONS ->"=>23
],[
"UNIQUEID"=>"571466427",
"DESTINATION"=>"1800284123",
"STATUS"=>"COMPLETE",
"vchFinancier"=>"",
"DURATIONS ->"=>23
],[
"UNIQUEID"=>"571466427",
"DESTINATION"=>"1800284123",
"STATUS"=>"COMPLETE",
"vchFinancier"=>"Power Alliance Finance",
"DURATIONS ->"=>23
]];
 */
require_once("Morpheus/api.php");

$cron_id = 36;
$tags = api_cron_tags_get($cron_id);

$from = 'yesterday';
$to = 'yesterday';

if (!isset($tags['from']) || (isset($tags['from']) && $tags['from'] !== $from)) {
    $updated_tags['from'] = $from;
    $from = isset($tags['from']) ? $tags['from'] : $from;
}

if (!isset($tags['to']) || (isset($tags['to']) && $tags['to'] !== $to)) {
    $updated_tags['to'] = $to;
    $to = isset($tags['to']) ? $tags['to'] : $to;
}

if (isset($updated_tags)) {
    print "Setting date tags to default value.\n";
    api_cron_tags_set($cron_id, $updated_tags);
}

$overrideHeaders = [
	'UNIQUEID', 'DESTINATION', 'STATUS', 'DISCONNECTED', '1_ONHOLD', '1_OPTION', '2_TRANSCALLTIME', '2_TRANSDEST',
	'2_TRANSDUR', 'source', 'sourcecampaign', 'TIMESTAMP', 'customernumber', 'customerrefnum', 'date', 'unsuccessfulsms',
	'COST', 'durations ->', AutoloadStrategy::BRAND_COLUMN_NAME
];

$options = [
	"start" => date("Y-m-d 00:00:00", strtotime($from)),
	"end" => date("Y-m-d 23:59:59", strtotime($to)),
	'reportformatcompleteoverride' => implode(',', $overrideHeaders)
];
$campaignname = "ToyotaFS-CallMe-" . date("FY", strtotime($options['end']));
$campaignid = api_campaigns_checkorcreate($campaignname, 31539);

$data = api_campaigns_report_summary_phone_array($campaignid, $options) ?: []; //Convert false to empty array

if (!$data) {
	print 'No data retrieved. Sending email with empty file attached.';
	toyotaFsCallmeSendEmail($tags, $data);
	exit;
}

$brands = [];
$brandFactory = new BrandingFactory();

$slicePosition = array_search('durations ->', $overrideHeaders) + 1;
foreach ($data as &$row) {
	// Add extra column after duration ->
	$row = array_slice($row, 0, $slicePosition, true) +
		['' => ''] +
		array_slice($row, $slicePosition, count($row)-1, true);

	if (isset($row[AutoloadStrategy::BRAND_COLUMN_NAME])) {
	    $longBrand = $row[AutoloadStrategy::BRAND_COLUMN_NAME];
		if (!isset($brands[$longBrand])) {
			$brand = $brandFactory
				->build($row[AutoloadStrategy::BRAND_COLUMN_NAME]);
			$brands[$longBrand] = ($brand !== false ? $brand->getValue() : $brand);
		}
		$value = $brands[$longBrand] ? : $row[AutoloadStrategy::BRAND_COLUMN_NAME];
		unset($row[AutoloadStrategy::BRAND_COLUMN_NAME]);
		$row[AutoloadStrategy::BRAND_COLUMN_NAME] = $value;
	}
}

reset($data);
$headers = array_keys(current($data));
array_unshift($data, $headers);

print "Sending report via email";
toyotaFsCallmeSendEmail($tags, $data);

function toyotaFsCallmeSendEmail(array $tags, array $data) {
	$content = api_csv_string($data);
	$email["to"]          = $tags["callreservation-reportemail"];
	$email["subject"]     = "[ReachTEL] Daily Call Reservation report - " . date("Y-m-d", strtotime("yesterday"));
	$email["textcontent"] = "Hello,\n\nPlease find attached the Call Reservation report for today.\n\n";
	$email["htmlcontent"] = "Hello,<br /><br />Please find attached the Call Reservation report for today.<br /><br />";
	$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

	$email["attachments"][] = array("content" => $content, "filename" => "ToyotaFS-CallMe-" . date("Ymd", strtotime("yesterday")) . ".csv");

	return api_email_template($email);
}
