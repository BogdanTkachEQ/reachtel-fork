<?php

require_once __DIR__ . "/../../api.php";

date_default_timezone_set('Australia/Melbourne');

$filename = @trim($argv[1]);

if (empty($filename)) {
	print "Filename must be specified!\n";
	exit();
}

$base_email = [
	'to' => 'ReachTEL Support <support@ReachTEL.com.au>',
	'cc' => 'ReachTEL Support <support@ReachTEL.com.au>',
	'from' => 'ReachTEL Support <support@ReachTEL.com.au>',
	'subject' => '[ReachTEL] Auto-load error - Simply Energy - ' . $filename,
];

// cron 129
$cron_id = getenv('CRON_ID');

$tags = api_cron_tags_get($cron_id);
$tags = $tags ?: [];
$expected_tags = [
	'sftp-failure-notification',
	'sftp-hostname',
	'sftp-username',
	'sftp-password',
	'sftp-path',
];

$missing_tags = array_diff($expected_tags, array_keys($tags));
if (!$tags || !empty($missing_tags)) {
	$missing_tags_string = implode(', ', $missing_tags);
	print "Mandatory tags are missing: $missing_tags_string";
	$email = $base_email;
	$email['content'] = <<<EOF
Hello,

Mandatory tags are missing for cron ID $cron_id. The following file has not been processed:

$filename

Missing tags are: $missing_tags_string

The auto-load process has failed.
EOF;
	api_email_template($email);
	exit();
}

$run_date = false;
if (isset($tags['run-date'])) {
	$run_date = strtotime($tags['run-date']);
}

if (!$run_date) {
	$run_date = time();
}

$possible_files = [
	'/SEDEBTMISSEDSMS01N_%d/' => 'SimplyEnergy-DEBTMISSEDSMS01N-%s',
	'/SEDEBTPENDSMS01N_%d/' => 'SimplyEnergy-DEBTPENDSMS01N-%s',
	'/SEDISCORMDSMS02N_%d/' => 'SimplyEnergy-DISCORMDSMS02N-%s',
	'/SEDISCOSMS03N_%d/' => 'SimplyEnergy-DISCOSMS03N-%s',
	'/SEFINSMS01N_%d/' => 'SimplyEnergy-FINSMS01N-%s',
	'/SEHSHIPMISSEDSMS01N_%d/' => 'SimplyEnergy-HSHIPMISSEDSMS01N-%s',
	'/SEHSHIPPENDSMS01N_%d/' => 'SimplyEnergy-HSHIPPENDSMS01N-%s',
	'/SEPROMPTSMS02N_%d/' => 'SimplyEnergy-PROMPTSMS02N-%s',
	'/SERMDSMS03N_%d/' => 'SimplyEnergy-RMDSMS03N-%s',
	'/SEURGS_%d/' => 'SimplyEnergy-URGSSMS-%s',
	'/SEPENDREF_%d/' => 'SimplyEnergy-PENDREFSMS-%s',
	'/SEHSHIPMISSED2WAYSMS01N_%d/' => 'SimplyEnergy-HSHIPMISSED2WAYSMS01N-%s',
];

// Emails to customer
$customer_base_email = $base_email;
$customer_base_email['to'] = isset($tags['sftp-failure-notification']) ? $tags['sftp-failure-notification'] : 'ReachTEL Support <support@ReachTEL.com.au>';

if (date('N') > 5) {
	print "Stopping because it is a weekend\n";
	$email = $customer_base_email;
	$email['content'] = <<<EOF
Hello,

The following file was uploaded, but wasn't processed as it is a weekend:

$filename

The auto-load process has failed. If this is unexpected, please contact ReachTEL support.
EOF;
	api_email_template($email);
	exit();
}

if (api_misc_ispublicholiday()) {
	print "Stopping because it is a public holiday\n";
	$email = $customer_base_email;
	$email['content'] = <<<EOF
Hello,

The following file was uploaded, but wasn't processed as it is a public holiday:

$filename

The auto-load process has failed. If this is unexpected, please contact ReachTEL support.
EOF;
	api_email_template($email);
	exit();
}

$campaign_name = '';
$date_string = date('Ymd', $run_date);
foreach ($possible_files as $possible_file => $campaign_pattern) {
	if (preg_match(sprintf($possible_file, $date_string), $filename)) {
		$campaign_name = sprintf($campaign_pattern, $date_string);
		$campaign_search = sprintf($campaign_pattern, '*');
		break;
	}
}

if (!$campaign_name) {
	print "Unexpected file $filename for date $date_string!\n";
	$email = $customer_base_email;
	$email['content'] = <<<EOF
Hello,

The following file was uploaded, but couldn't be matched to one of the expected filenames for run-date $date_string:

$filename

The auto-load process has failed. If this is unexpected, please contact ReachTEL support.
EOF;
	api_email_template($email);
	exit();
}

$path = "/tmp/";

print "Downloading file...";

$options = array(
	"hostname" => $tags["sftp-hostname"],
	"username" => $tags["sftp-username"],
	"password" => $tags["sftp-password"],
	"localfile" => $path . $filename,
	"remotefile" => $tags["sftp-path"] . $filename
);

if (!api_misc_sftp_get($options)) {
	print "Failed to fetch file '" . $filename . "'\n";

	$email = $base_email;
	$email['content'] = <<<EOF
Hello,

The following file could not be downloaded from the specified server:

$filename

The auto-load process has failed.
EOF;
	api_email_template($email);
	exit();
} else {
	print "OK\n";
}

print "Creating campaign...";

print $campaign_name;
$exists = api_campaigns_checknameexists($campaign_name);

if (is_numeric($exists)) {
	unlink($path . $filename);

	print "\nFailed. The campaign already exists.\n";
	$email = $base_email;
	$email['content'] = <<<EOF
Hello,

The following campaign could not be created as it already exists:

$campaign_name

The auto-load process has failed.
EOF;
	api_email_template($email);
	exit();
}

$previous_campaigns = api_campaigns_list_all(
	true,
	null,
	null,
	[
		"search" => $campaign_search,
	]
);

if (!$previous_campaigns) {
	unlink($path . $filename);

	print "\nFailed. No previous campaigns found for search: $campaign_search\n";
	$email = $base_email;
	$email['content'] = <<<EOF
Hello,

The following campaign could not be created as no previous campaigns could be found:

Campaign: $campaign_name
Campaign Search: $campaign_search

The auto-load process has failed.
EOF;
	api_email_template($email);
	exit();
}

$campaign_id = api_campaigns_add(
	$campaign_name,
	null,
	key($previous_campaigns)
);

if (!is_numeric($campaign_id)) {
	unlink($path . $filename);

	print "\nFailed to create campaign.\n";
	$email = $base_email;
	$email['content'] = <<<EOF
Hello,

The following campaign could not be created:

$campaign_name

The auto-load process has failed.
EOF;
	api_email_template($email);
	exit();
}

print " OK\n";

print "Uploading data...";

$result = api_targets_fileupload($campaign_id, $path . $filename, $filename);

if (!is_array($result)) {
	unlink($path . $filename);

	print "Failed to process file\n";
	$error = api_error_printiferror(['return' => true]);

	// This does not look good but there is no exception that I can catch to determine the failure reason :(
	// The error message comes from api_targets_fileupload()
	if ($error !== "Sorry, there was no data uploaded") {
		$email = $customer_base_email;
		$email['content'] = <<<EOF
Hello,

The following file could not be processed successfully:

$filename

The auto-load process has failed. If this is unexpected, please contact ReachTEL support.
EOF;
		api_email_template($email);
		exit();
	}

	print "No data was uploaded to the campaign\n";
}

print "OK\n";

unlink($path . $filename);

// set pacing if tag set, otherwise keep copied value (aka do nothing)
if (isset($tags['send-rate-base-hours'])) {
	print "Setting pacing...";
	$send_rate_base_hours = $tags['send-rate-base-hours'];
	$targets_added = $result['good'];
	$send_rate  = ceil($targets_added / $send_rate_base_hours);
	print "$targets_added / $send_rate_base_hours = " . $send_rate . "...";
	api_campaigns_setting_set($campaign_id, 'sendrate', (int) $send_rate);
	print "OK\n";
}

print "Activating campaign...";

if (!api_campaigns_setting_set($campaign_id, "status", "ACTIVE")) {
	print "Failed!\n";
	$email = $base_email;
	$email['content'] = <<<EOF
Hello,

The following campaign could not be activated:

$campaign_name ($campaign_id)

The auto-load process has failed.
EOF;
	api_email_template($email);
	exit();
}

// Remove run-date if successful
if (isset($tags['run-date'])) {
	api_cron_tags_delete($cron_id, ['run-date']);
}

print "OK\n";

print "Job done!\n";
