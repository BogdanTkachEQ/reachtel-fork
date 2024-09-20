<?php

require_once(__DIR__ . "/../../api.php");

// cron 120
$cronId = getenv(CRON_ID_ENV_KEY);
$tags = api_cron_tags_get($cronId);
$reporting_destination = isset($tags['reporting-destination']) ? $tags['reporting-destination'] : 'ReachTEL IT Support <AUReachTELITSupport@equifax.com>';
$filename = isset($tags['filename-override']) ? $tags['filename-override'] : 'Data Extract for ReachTEL ' . date('mY') . '.zip';

// Get BSV data file from SFTP
const BSV_LOCATION = '/bsv';

$downloadPath = SAVE_LOCATION . BSV_LOCATION;

if (!is_dir($downloadPath)) {
	$dirCreated = mkdir($downloadPath);

	if (!$dirCreated) {
		echo "Couldn't create BSV data download path\n";
		exit();
	}
}

// always reset permissions of download location to minimum possible
$result = chmod($downloadPath, 0700);

// if the chmod failed for whatever reason and we still can't write, fail out
if (!$result) {
	echo "Can't write to BSV data download path\n";
	exit();
}

$filepath = $downloadPath . '/' . $filename;

// Only download the file again if it doesn't exist
if (!file_exists($filepath)) {
	$options = [
		"hostname" => $tags["sftp-hostname"],
		"username" => $tags["sftp-username"],
		"password" => isset($tags["sftp-password"]) ? $tags['sftp-password'] : '',
		"localfile" => $filepath,
		"remotefile" => $tags["sftp-path"] . basename($filepath)
	];

	$result = api_misc_sftp_get_large($options);

	if (!$result) {
		echo "Failed to download BSV data extract from SFTP:\n";
		api_error_printiferror();
		exit();
	}
}

// Process the import
try {
	$bsvImporter = new Services\Cron\BsvImporter($filepath);

	if (!isset($tags['skip-process']) || $tags['skip-process'] !== '1') {
		$bsvImporter
			->process();
	}

	if (!isset($tags['skip-replace-table']) || $tags['skip-replace-table'] !== '1') {
		$bsvImporter
			->replaceTable();
	}

	api_db_reset_connection();

	// send a success email
	$summary = $bsvImporter->getSummary();

	$date = date("Y-m-d H:i:s");
	$email = [];
	$email['to'] = $reporting_destination;
	$email['subject'] = "[ReachTEL] Plotter BSV import - $date";
	$email['content'] = "BSV import complete.\n\nHere is a summary.\n\n$summary";

	if ($bsvImporter->hasErrors()) {
		$errorFile = $bsvImporter->getErrorFile();
		$sqlErrorFile = $bsvImporter->getSqlErrorFile();

		$sqlErrorContent = file_get_contents($sqlErrorFile);
		$errorContent = file_get_contents($errorFile);

		$email['attachments'][] = [
			'content' => $sqlErrorContent,
			'filename' => 'sql_error_ids.csv'
		];
		$email['attachments'][] = [
			'content' => $errorContent,
			'filename' => 'error_ids.csv'
		];
	}

	api_email_template($email);

} catch (Exception $e) {
	$exceptionMessage = get_class($e) . ': ' . $e->getMessage();
	echo $exceptionMessage . "\n";
	api_error_printiferror();

	$date = date("Y-m-d H:i:s");
	$email = [];
	$email['to'] = $reporting_destination;
	$email['subject'] = "[ReachTEL] Plotter BSV import FAILED - $date";
	$email['content'] = "BSV import FAILED. Data file has not been removed, someone needs to look into this and re-run.\n\nHere is the exception message:\n\n$exceptionMessage";

	api_email_template($email);
	exit();
}

// Remove the processed file
unlink($filepath);
echo "Success!\n";
