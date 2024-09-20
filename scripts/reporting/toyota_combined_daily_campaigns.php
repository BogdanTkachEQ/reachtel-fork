<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 *
 *
 *
 *
 */

/*
//Mock report data
$report[0]['vchFinancier'] = 'Toyota Finance';
$report[0]['cost'] = 'test';
$report[0]['duration_1'] = 'duration test1';
$report[0]['duration_2'] = 'duration test2';
$report[1]['cost'] = 'test1';
$report[1]['vchFinancier'] = 'lexus financial services';
$report[1]['duration_1'] = 'duration test1.1';
$report[2]['cost'] = 'test 2';
$report[2]['duration_1'] = 'duration test 2.1';
$report[2]['vchFinancier'] = 'lexus financial services';
*/

require_once("Morpheus/api.php");

use Services\Campaign\Hooks\Cascading\Creators\TemplateBasedCascadingCampaignCreator;
use Services\Customers\Branding\BrandingFactory;
use Services\Customers\Toyota\Autoload\AutoloadStrategy as ToyotaAutoloadStrategy;
use Services\Utils\StringFunctions;

$cronId = getenv('CRON_ID');
$tags = api_cron_tags_get($cronId);

/*
//Mock Tags
$tags['campaign-type'] = "SMSVoice";
$tags['sftp-hostname'] = "host";
$tags['sftp-username'] = "user";
$tags['sftp-password'] = "pass";
$tags['sftp-path'] = "path";
*/

$smsHeaders = ["uniqueid", "destination", "status", "SENT", "DELIVERED", "UNDELIVERED", "UNKNOWN", "DUPLICATE",
               "REMOVED", "RESPONSE", "chWorkList", "chBPAYnumber", "chCollectionsProduct", "chLease", "chSortOrder",
               "cRiskLevel", "ExtractDt", "iArrearsDays", "mArrearsTot", "vchCustName", "vchPostcode", "vchState",
               "cost", "vchFinancier"];

$voiceHeaders = ["targetkey", "destination", "status", "disconnected", "REMOVED", "0_AMD", "1_OPTION", "2_OPTION",
                 "3_DOBVALIDATE", "3_DOBENTERED", "4_SELFSERVEOPTION", "2_TRANSDUR", "CALLBACK", "CALLBACK_TRANSDUR",
                 "Campaign", "chWorkList", "chBPAYNumber", "chCollectionsProduct", "chLease", "chSortOrder",
                 "cRiskLevel", "CurrentInstalment", "ExtractDt", "iArrearsDays", "mArrearsTot", "PaymentFrequency",
                 "ScheduledPymt", "vchCustName", "vchPostcode", "vchState", "cost", "TIMESTAMP", "DURATIONS_1",
                 "DURATIONS_2", "vchFinancier"];

if (isset($tags['exclude-voice-headers'])) {
    $exclude = explode(',', $tags['exclude-voice-headers']);
    $voiceHeaders = array_diff($voiceHeaders, $exclude);
}

if (isset($tags['exclude-sms-headers'])) {
    $exclude = explode(',', $tags['exclude-sms-headers']);
    $smsHeaders = array_diff($smsHeaders, $exclude);
}

$runDate = (new DateTime("yesterday 00:00:00"));
if (isset($tags['run-date'])) {
	try {
		$runDate = (new DateTime($tags['run-date']));
	} catch (\Exception $e) {
		print "Invalid run date given: '" . $tags['run-date'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
		exit;
	}
}

if (!isset($tags['sftp-hostname'])) {
	print "sftp-hostname must be set, exiting.\n";
	exit;
}

if (!isset($tags['sftp-username'])) {
	print "sftp-username must be set, exiting\n";
	exit;
}

if (!isset($tags['sftp-password'])) {
	print "sftp-password must be set, exiting\n";
	exit;
}

if (!isset($tags['sftp-path'])) {
	print "sftp-path must be set, exiting\n";
	exit;
}

$campaignType = $tags['campaign-type'];
switch ($campaignType) {
	case "Voice":
	case "1to8":
	case "SMS1to8":
	case "SMSVoice":
	case "FollowUp":
		print "Generating combined report for {$campaignType}\n";
		break;
	default:
		print "Unrecognised campaign type {$campaignType}, exiting...\n";
		exit;
}

print "Running for campaigns on date: " . $runDate->format("Y-m-d") . "\n";

/**
 * Generate the campaigns
 */
$campaignNames = generateCampaignNames($runDate, $campaignType);

/**
 * Build the array data for each campaign
 */
$reportData = [];
$csvHeaders = [];
foreach ($campaignNames as $campaignName) {
	$campaignid = api_campaigns_nametoid($campaignName);
	if (!$campaignid) {
		$message = "Could not resolve a campaign for the campaign name {$campaignName}, skipping..\n";
		api_error_raise($message) ;
		print $message;
		continue;
	}
	$report = [];
	switch ($campaignType) {
		case "Voice":
			$options = ['max_events' => 2, 'all_durations' => true,
			            'reportformatcompleteoverride' => implode(",", $voiceHeaders)];
			$report = api_campaigns_report_summary_phone_array($campaignid, $options);

			$csvHeaders = $voiceHeaders;
			break;
		case "1to8":
		case "SMS1to8":
		case "SMSVoice":
		case "FollowUp":
			$report = api_campaigns_report_summary_sms_array(
				$campaignid, ['reportformatcompleteoverride' => implode(
				",", $smsHeaders
			)]
			);
			$csvHeaders = $smsHeaders;
			break;
	}
	if (empty($report)) {
		print "There is no campaign data for {$campaignName}: {$campaignType}\n";
	}
	$reportData[$campaignName] = $report;
}

// Setup and manage the fields in each row
array_walk($reportData, 'setupFields', $csvHeaders);

/**
 * Build and upload the CSV Data
 */
$csv = api_csv_string([setupCSVHeaders($csvHeaders)]) . "\n";
foreach ($reportData as $csvData) {
	$csv .= api_csv_string($csvData) . "\n";
}

if (!saveCSV($csv, $campaignType, $tags)) {
	print "Failed to remove temp file\n";
	exit;
}

/**
 * Setup vchFinancier field and replace the subisdiary name with the brand name
 * @param $reportEntry
 */
function setupFields(array &$reportsData, $key, $csvHeaders) {
	foreach ($reportsData as &$reportEntry) {
		// Replace the subisdiary name with the brand name
		if (array_key_exists(ToyotaAutoloadStrategy::BRAND_COLUMN_NAME, $reportEntry)) {
			$brand = (new BrandingFactory())->build($reportEntry[ToyotaAutoloadStrategy::BRAND_COLUMN_NAME]);
			if ($brand) {
				$reportEntry[ToyotaAutoloadStrategy::BRAND_COLUMN_NAME] = $brand->getBrandName();
			} else {
				print "Could not resolve the brand name for '{$reportEntry[ToyotaAutoloadStrategy::BRAND_COLUMN_NAME]}'\n";
			}
		} else {
			$reportEntry[ToyotaAutoloadStrategy::BRAND_COLUMN_NAME] = "";
		}

		// Update the entry to contain all possible headers
		$output = [];
		foreach ($csvHeaders as $headerKey) {
			$output[$headerKey] = isset($reportEntry[$headerKey]) ? $reportEntry[$headerKey] : "";
		}

		$reportEntry = $output;
		// Set vcFinancier to definitely be the last column
		if (isset($reportEntry[ToyotaAutoloadStrategy::BRAND_COLUMN_NAME])) {
			$current = $reportEntry[ToyotaAutoloadStrategy::BRAND_COLUMN_NAME];
			unset($reportEntry[ToyotaAutoloadStrategy::BRAND_COLUMN_NAME]);
			$reportEntry[ToyotaAutoloadStrategy::BRAND_COLUMN_NAME] = $current;
		} else {
			$reportEntry[ToyotaAutoloadStrategy::BRAND_COLUMN_NAME] = "";
		}
	}
}

/**
 * Replace any headers with their expected values
 *
 * @param $headers
 * @return mixed
 */
function setupCSVHeaders($headers) {
	if($search = array_search("DURATIONS_1", $headers)) {
		$headers[$search] = "durations->";
	}

	if($search = array_search("DURATIONS_2", $headers)) {
		$headers[$search] = "";
	}
	return $headers;
}

/**
 *
 *
 * @param DateTime $date
 * @param          $type
 * @return array
 */
function generateCampaignNames(DateTime $date, $type) {
	$campaignNames = ["ToyotaFS-{DATE}-1-{TYPE}", "MazdaFS-{DATE}-1-{TYPE}", "HinoFS-{DATE}-1-{TYPE}",
	                  "LexusFS-{DATE}-1-{TYPE}", "PowerTorqueFS-{DATE}-1-{TYPE}", "PowerAllianceFS-{DATE}-1-{TYPE}", "SuzukiFS-{DATE}-1-{TYPE}"];
	array_walk(
		$campaignNames, function(&$value) use ($date, $type)
	{
		$replaceType = ($type !== 'FollowUp') ? $type : 'Voice';

		$value = str_replace("{DATE}", $date->format("jFy"), $value);
		$value = str_replace("{TYPE}", $replaceType, $value);

		if ($type === 'Voice') {
			$value = TemplateBasedCascadingCampaignCreator::buildCampaignNameWithIteration($value, 1);
		} else if ($type === 'FollowUp') {
			$value = TemplateBasedCascadingCampaignCreator::buildCampaignNameWithIteration($value, 2);
		}
	}
	);
	return $campaignNames;
}

/**
 * @param $csvData
 * @param $campaignType
 * @param $tags
 * @return bool
 */
function saveCSV($csvData, $campaignType, $tags) {
	if (isset($tags['filename'])) {
		$filename = StringFunctions::parseDateTime($tags['filename'], new DateTime());
	} else {
		$filename = "ToyotaFS-" . date("dMy") . "-1-$campaignType.csv";
	}

	$tempfname = tempnam("/tmp", "toyotafs");

	file_put_contents($tempfname, $csvData);
	$options = ["hostname" => $tags["sftp-hostname"], "username" => $tags["sftp-username"],
				"password" => $tags["sftp-password"], "localfile" => $tempfname,
				"remotefile" => $tags["sftp-path"] . $filename];

	if (!api_misc_sftp_put_safe($options)) {
		print "Failed to upload to sftp location\n";
	}

	return unlink($tempfname);
}
