#!/usr/bin/php
<?php

require_once(__DIR__ . '/../../api.php');

$base_email = [
	'to' => 'ReachTEL Support <support@ReachTEL.com.au>',
	'cc' => 'ReachTEL Support <support@ReachTEL.com.au>',
	'from' => 'ReachTEL Support <support@ReachTEL.com.au>',
	'subject' => '[ReachTEL] Report error - Simply Energy',
];

// cron 132
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

Mandatory tags are missing for cron ID $cron_id.

Missing tags are: $missing_tags_string

The report process has failed.
EOF;
	api_email_template($email);
	exit();
}

$run_date = new DateTime('yesterday');
if (isset($tags['run-date'])) {
	$run_date = new DateTime($tags['run-date']);
}

// Emails to customer
$customer_base_email = $base_email;
$customer_base_email['to'] = isset($tags['sftp-failure-notification']) ? $tags['sftp-failure-notification'] : 'ReachTEL Support <support@ReachTEL.com.au>';

$campaigns = [
	'SimplyEnergy-DEBTMISSEDSMS01N-%s',
	'SimplyEnergy-DEBTPENDSMS01N-%s',
	'SimplyEnergy-DISCORMDSMS02N-%s',
	'SimplyEnergy-DISCOSMS03N-%s',
	'SimplyEnergy-FINSMS01N-%s',
	'SimplyEnergy-HSHIPMISSEDSMS01N-%s',
	'SimplyEnergy-HSHIPPENDSMS01N-%s',
	'SimplyEnergy-PROMPTSMS02N-%s',
	'SimplyEnergy-RMDSMS03N-%s',
	'SimplyEnergy-URGSSMS-%s',
	'SimplyEnergy-PENDREFSMS-%s',
	'SimplyEnergy-HSHIPMISSED2WAYSMS01N-%s'
];

$skippedCampaigns = [];
foreach ($campaigns as $campaign_pattern) {
	$campaign_search = sprintf($campaign_pattern, $run_date->format('Ymd'));
	$campaign_id = api_campaigns_nametoid($campaign_search);

	print "Generating report for $campaign_search...";

	if (!$campaign_id) {
		print "No campaign found for $campaign_search\n";
		$skippedCampaigns[] = $campaign_search;
		continue;
	}

	generate_report($campaign_id, $tags, $base_email, $run_date);
	print "OK\n";
}

if (!empty($skippedCampaigns)) {
	$skippedCampaignsString = implode("\n", $skippedCampaigns);
	$email = $customer_base_email;
	$email['content'] = <<<EOF
Hello,

The following campaigns were not found for reporting:

$skippedCampaignsString

If this is unexpected, please contact ReachTEL support.
EOF;
	api_email_template($email);
}

print "Job done!\n";
// Remove run-date if successful
if (isset($tags['run-date'])) {
	api_cron_tags_delete($cron_id, ['run-date']);
}
api_error_printiferror();

/**
 * Generate a report and upload to SFTP
 *
 * @param integer  $campaign_id
 * @param array    $tags
 * @param array    $base_email
 * @param DateTime $run_date
 *
 * @return void
 */
function generate_report($campaign_id, array $tags, array $base_email, DateTime $run_date) {
	$data = api_campaigns_report_summary_sms_array($campaign_id, ['return_target_id' => true]);

	if (!$data) {
		print 'No records to return';
		return;
	}

	$settings = api_campaigns_setting_get_multi_byitem(
		$campaign_id,
		[
			CAMPAIGN_SETTING_NAME
		]
	);

	$campaign_name = $settings[CAMPAIGN_SETTING_NAME];

	$max_length = 400;

	$data = array_map(
		function($row) use($max_length) {
			$status_text = 'Unknown';
			if (!empty($row['DELIVERED'])) {
				$status_text = 'Delivered: ' . $row['DELIVERED'];
			} elseif (!empty($row['UNDELIVERED'])) {
				$status_text = 'Undelivered: ' . $row['UNDELIVERED'];
			}
			$sms = api_sms_find_sms_sent_by_targetid($row['TARGETID']);

			$message = $sms ? trim($sms['contents']) : '';

			$text = sprintf(
				'ReachTEL: %s Sent %s %s',
				$message,
				$row['SENT'],
				$status_text
			);

			$text_length = strlen($text);
			if ($text_length > $max_length) {
				$suffix = '...';
				$message = substr($message, 0, ((strlen($message) - ($text_length - $max_length)) - strlen($suffix)));

				$text = sprintf(
					'ReachTEL: %s Sent %s %s',
					$message . $suffix,
					$row['SENT'],
					$status_text
				);
			}

			return [
				$row['UNIQUEID'],
				$text,
			];
		},
		$data
	);

	$tempfname = tempnam("/tmp", "simplyenergy-sms");

	if (!api_csv_file($tempfname, $data)) {
		print("Failed to create csv");
		$email = $base_email;
		$email['content'] = <<<EOF
Hello,

The temporary CSV report for the following campaign could not be created:

$campaign_name

The report process has failed.
EOF;
		api_email_template($email);
		return;
	}

	$snake_campaign_name = str_replace('-', '_', $campaign_name);
	$filename = sprintf("AddBulkNotes_%s_%s.csv", $run_date->format('Ymd'), $snake_campaign_name);

	$options = [
		"hostname"  => $tags["sftp-hostname"],
		"username"  => $tags["sftp-username"],
		"password"  => $tags["sftp-password"],
		"localfile" => $tempfname,
		"remotefile" => $tags["sftp-path"] . $filename
	];

	$result = api_misc_sftp_put_safe($options);

	unlink($tempfname);

	if (!$result) {
		print("Failed to upload to SFTP\n");
		unset($options['password']); // just in case!
		$sftp_options = var_export($options, true);
		$email = $base_email;
		$email['content'] = <<<EOF
Hello,

The report for the following campaign could not be uploaded to SFTP:

$campaign_name

$sftp_options

The report process has failed.
EOF;
		api_email_template($email);
		exit(); // if one fails, they'll all fail so die
	}
}
