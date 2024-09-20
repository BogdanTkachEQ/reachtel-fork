#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$groupId = 735;
$cronId = 87;
$tags = api_cron_tags_get($cronId);

if (!$tags || !isset($tags['campaign-tag-filter'])) {
    fwrite(
        STDOUT,
        "[ERROR] Cron tag 'campaign-tag-filter' not found\n"
    );
    exit;
}

if (isset($tags['date'])) {
	$datestring = $tags['date'];
	api_cron_tags_set($cronId, ['date' => 'today']);
} else {
	$datestring = 'today';
}

$startTime = strtotime($datestring);
$endTime = min($startTime + (60 * 60 * 24) - 1, time());

if ($datestring != 'today') {
	print "Running report for {$datestring}...\n";
	if ($startTime > time()) {
		print "Cannot run future dated reports!\n";
		exit;
	}
}

$campaigns = [];

foreach(api_campaigns_list_all(true) as $campaignId => $name){
    $settings = api_campaigns_setting_getall($campaignId);

    if($settings['type'] !== 'email'
        || !isset($settings['created'])
        // today's campaigns only
        || $settings['created'] < $startTime
        || $settings['created'] > $endTime
        // Transurban group id only
        || $settings['groupowner'] != $groupId
        // campaigns with filter tag only
        || !api_campaigns_tags_get($campaignId, $tags['campaign-tag-filter'])) {
            continue;
    }

    $campaigns[] = $campaignId;
}

if(!$campaigns) {
    print 'No records to return';
    exit;
}

$data = api_campaigns_report_cumulative_array($campaigns, 'email');

if (!$data) {
	print 'No records to return';
	exit;
}

// output CSV header order
$mandatory = [
	'targetkey', 'destination', 'status', 'Debtor_Amount', 'Debtor_FirstName', 'Debtor_FullName', 'Debtor_Invoice',
	'Debtor_LPN', 'Debtor_Surname', 'INFRINGEMENT_VALUE', 'LTI_ACCOUNT_ID', 'LTI_ADDRESS1',
	'LTI_ADDRESS2', 'LTI_AGE', 'LTI_CITY', 'LTI_CLEARED_DATE', 'LTI_CURR_BALANCE',
	'LTI_DISPUTE', 'LTI_DL', 'LTI_DOB', 'LTI_EMAIL', 'LTI_FEES_VALUE',
	'LTI_FEES_WAIVED', 'LTI_FIRST_NAME', 'LTI_LAST_NAME', 'LTI_LATEST_TRIP_DATE', 'LTI_LETTERSTAGE',
	'LTI_LPN', 'LTI_MOBILE_NUMBER', 'LTI_NOM_VALUE', 'LTI_OLDEST_TRIP_DATE',
	'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1', 'LTI_PAYPLAN', 'LTI_PAYPLAN_END_DATE',
	'LTI_PAYPLAN_USER_ID', 'LTI_PER_ID', 'LTI_PHONE_NUMBER', 'LTI_POSTCODE',
	'LTI_REFERRED_DATE', 'LTI_REFERRED_VALUE', 'LTI_SPECIAL_PLATE_TYPE', 'LTI_STATE',
	'LTI_STATUS', 'LTI_TN1_DATE', 'LTI_TN1_DUE_DATE', 'LTI_TN2_DATE', 'LTI_TN2_DUE_DATE',
	'LTI_TN2_INFRINGEMENT_DATE', 'LTI_TRIPS_VALUE', 'LTI_TRIPS_WAIVED', 'LTI_TRIP_COUNT',
	'LTI_VEHICLE_CLASS', 'REPORT_TYPE', 'RUN_DATE', 'R_ABN_ACN', 'R_ACCOUNT_ID', 'R_ACCOUNT_NAME',
	'R_CURRENT_STATUS', 'R_CURR_BALANCE', 'R_DISPUTE', 'R_INVOICE_ADDRESS1', 'R_INVOICE_ADDRESS2',
	'R_INVOICE_ADDRESS3', 'R_INVOICE_BUSPHONE', 'R_INVOICE_CITY', 'R_INVOICE_EMAIL', 'R_INVOICE_FAX',
	'R_INVOICE_FIRST_NAME', 'R_INVOICE_HOME_PHONE', 'R_INVOICE_LAST_NAME', 'R_INVOICE_MOB',
	'R_INVOICE_OTHER_PHONE', 'R_INVOICE_PERSON_ID', 'R_INVOICE_POSTCODE', 'R_INVOICE_STATE',
	'R_INVOICE_TITLE', 'R_LAST_PAY_AMT', 'R_LAST_PAY_METHOD', 'R_LAST_PAY_TIME', 'R_LOCATED_ADDRESS1',
	'R_LOCATED_ADDRESS2', 'R_LOCATED_CITY', 'R_LOCATED_POSTCODE', 'R_LOCATED_STATE',
	'R_MAILING_ADDRESS1', 'R_MAILING_ADDRESS2', 'R_MAILING_CITY', 'R_MAILING_POSTCODE',
	'R_MAILING_STATE', 'R_PAYPLAN', 'R_PAYPLAN_END_DATE', 'R_PRIM_BUSPHONE', 'R_PRIM_DL',
	'R_PRIM_DOB', 'R_PRIM_EMAIL', 'R_PRIM_FAX', 'R_PRIM_FIRST_NAME', 'R_PRIM_HEARING_IMPAIRED',
	'R_PRIM_HOME_PHONE', 'R_PRIM_LAST_NAME', 'R_PRIM_MOB', 'R_PRIM_OTHER_PHONE', 'R_PRIM_PERSON_ID',
	'R_PRIM_TITLE', 'R_SA_TYPE', 'R_SEC_BUSPHONE', 'R_SEC_EMAIL', 'R_SEC_FAX', 'R_SEC_FIRST_NAME',
	'R_SEC_HOME_PHONE', 'R_SEC_LAST_NAME', 'R_SEC_MOB', 'R_SEC_OTHER_PHONE', 'R_SEC_PERSON_ID',
	'R_SEC_TILE', 'R_SERVICE_AGREEMENT_ID', 'R_TAG_COUNT', 'R_TRADING_NAME', 'COST',
	'campaign',

	// forced status columns
	'HARDBOUNCE', 'HARDBOUNCEREASON',  'SOFTBOUNCE',  'BOUNCED',  'DUPLICATE',  'UNSUBSCRIBED',
	'UNSUBSCRIBE',  'DNC',  'CLICK',  'TRACK',  'WEBVIEW',  'REMOVED',  'SENT',
];

// MOR-1304 force columns
$headers = current($data);
array_walk($data, function(&$row, $i) use($mandatory, $headers) {
	if (0 === $i) { // header
		$row = $mandatory;
		return;
	}

	$row = array_combine($headers, $row);
	$new = [];
	foreach($mandatory as $column) {
		$new[$column] = array_key_exists($column, $row) ? $row[$column] : null;
	}

	$row = $new;
});

$content = api_csv_string($data);

$tempfname = tempnam("/tmp", "transurban-email");

if(!file_put_contents($tempfname, $content)) {
    fwrite(STDOUT, "\033[0;31m[Failed to write to file]\033[0m\n");
}

$filename = "TRANSURBAN-REACHTEL-EMAIL-" . date("dmY-His", $endTime) . ".csv";

if (! empty($tags['pgp-keys'])) {
    print "Trying to encrypt file: {$tempfname}...";
    $pgpfile['content'] = file_get_contents($pgpfile['filename'] = $tempfname);
    $pgpfile = api_misc_pgp_encrypt($pgpfile, $tags['pgp-keys']);
    if ($pgpfile) {
        unlink($tempfname);
        file_put_contents($tempfname = $pgpfile['filename'], $pgpfile['content']);
        $filename .= '.pgp';
    }
}

$options = [
    "hostname"  => $tags["sftp-hostname"],
    "username"  => $tags["sftp-username"],
    "password"  => $tags["sftp-password"],
    "localfile" => $tempfname,
    "remotefile" => $tags["sftp-path"] . $filename
];

$result = api_misc_sftp_put_safe($options);

unlink($tempfname);

if(!$result) {
    fwrite(STDOUT, "\033[0;31m[Failed to upload to SFTP]\033[0m\n");
    exit;
}
