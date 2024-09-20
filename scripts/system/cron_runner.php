<?php

// This script should run once per minute on one of the management servers.

require_once("Morpheus/api.php");

// Provide some protection that we aren't calling this script in multiple locations. Check that the last run was more than 30 seconds ago
$lastrun = api_cron_setting_getsingle(0, "lastrun");

if($lastrun > (time() - 30)) { // lastrun should be less than so if it is greater than, log and error and fail

	// Log an error and exit
	api_misc_audit("CRON", "Scheduling error. Last run=" . date("Y-m-d H:i:s", $lastrun) . "; Current time=" . date("Y-m-d H:i:s"));
	exit;
}

// Set the lastrun time to now
api_cron_setting_set(0, "lastrun", time());

// Schedule any crons
api_cron_run();