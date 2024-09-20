<?php

define("SPOOLER_DEBUG", true);
define("DB_USE", "spooler");
define("PROFILE_USE", "morpheus_spooler");

require_once(__DIR__ . "/../api.php");

use Services\Campaign\Builders\CampaignSettingsDirector;
use Services\Campaign\Validators\CampaignTimingValidationService;
use Services\Container\ContainerAccessor;
use Services\Exceptions\Campaign\Validators\CampaignTimingRangeValidationFailure;
use Services\Exceptions\Campaign\Validators\CampaignTimingValidationFailure;

$abort_process = false;

if(!is_numeric($argv[1])) exit;

if(SPOOLER_PROFILE) {
	xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
	$PROFILE_STARTED = true;
}

api_misc_debug("\n. = Active Campaigns; D = Data; N = Do Not Contact; H = HLR failed; L = Limit; F = Flag; T = Time; C = Call; S = SMS; E = Email; * = Ping; G = no supplier;\n\n");

$loop = 0;

$SPOOLER_DELAY = SPOOLER_DELAY;

$DELAY = false;
$lastprofping = time();

$started = time();

pcntl_signal(SIGTERM, function($signo) use (&$abort_process) {
	$abort_process = true;
	api_misc_debug('Termination signal received');
});

/** @var CampaignSettingsDirector $campaignSettingsDirector */
$campaignSettingsDirector = ContainerAccessor::getContainer()
    ->get(CampaignSettingsDirector::class);

while(1){

	pcntl_signal_dispatch();

	$time = microtime(true);

	if( ($DELAY) AND (($time - $loop) < $SPOOLER_DELAY)) {
		usleep($SPOOLER_DELAY - ($time - $loop));
	}

	$loop = $time;

	if($DELAY AND ($SPOOLER_DELAY < SPOOLER_DELAY_MAX)) $SPOOLER_DELAY = $SPOOLER_DELAY + SPOOLER_DELAY_INCREMENT;
	elseif(!$DELAY) $SPOOLER_DELAY = SPOOLER_DELAY;

	$DELAY = TRUE;

	$campaignid = $argv[1];

	if((time() - $lastprofping) > SPOOLER_PROFILE_TIME){

		$lastprofping = time();

		api_db_ping();

		if(SPOOLER_PROFILE){

			api_misc_profiling_save();

			xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);

			$PROFILE_STARTED = true;
		}

		$status = api_campaigns_setting_getsingle($campaignid, "status");

		if($status != "ACTIVE") exit;

		if(api_keystore_get("SETTINGS", 0, "DAEMON_RESTART") > $started) exit;

		if ($abort_process) {
			exit;
		}

		api_misc_debug("\n. = Active Campaigns; D = Data; N = Do Not Contact; H = HLR failed; L = Limit; F = Flag; T = Time; C = Call; S = SMS; E = Email; * = Ping;\n\n");
	}

	// No need to check campaigns hundreds of times a second if they constantly have no data or don't have an active time period
	if(isset($skip[$campaignid]) AND ($time <= $skip[$campaignid])) continue;

	// Healthy heartbeat timestamp
	api_campaigns_setting_set($campaignid, 'heartbeattimestamp', time());

	// This needs to be re-instantiated since it is in a loop and campaign settings are likely to change
	/** @var CampaignTimingValidationService $validationService */
	$validationService = ContainerAccessor::getContainer()
		->get(CampaignTimingValidationService::class);

	// TODO: When we have timezone for individual target, move this code down so that validation is performed against
	// the target timezone and set the next attempt using next attempt service.
	try {
		$campaignSettings = $campaignSettingsDirector->buildCampaignSettings($campaignid);
		$dateTime = new DateTime('now', $campaignSettings->getTimeZone());
		$validationService->isValidDateTime($dateTime, $campaignSettings);
	} catch (CampaignTimingRangeValidationFailure $e) {
		// When time does not fit in specific or recurring times
		$skip[$campaignid] = $time + 1;
		continue;
	} catch (CampaignTimingValidationFailure $e) {
		// When timing validation fails
		// TODO: add response data stating that it has been postponed because of acma and set next attempt
		$skip[$campaignid] = $time + 1;
		continue;
	} catch (Exception $e) {
		api_error_raise('Validation error in call spooler for campaign [' . $campaignid . ']: ' . $e->getMessage());
		$skip[$campaignid] = $time + 1;
		continue;
	}

	if(isset($skip[$campaignid])) unset($skip[$campaignid]);

	$settings = api_campaigns_setting_getall($campaignid);

	if($settings["status"] != "ACTIVE") exit;

    	// Channel limitations
	if(api_restrictions_channels_checkall($campaignid, null, $settings)) continue;

    	// Get a target
	$target = api_targets_gettarget($campaignid, $settings);

	if(!is_array($target)) {
		if(!isset($lastsend[$campaignid]) OR (($time - $lastsend[$campaignid]) > 2)) $skip[$campaignid] = $time + 2;	// If we haven't sent a campaign in the last 2 seconds, delay further checks for another 2 seconds
		continue;	// No targets
	}

	if($settings["donotcontact"]) $settings["donotcontact"] = unserialize($settings["donotcontact"]);

    // Check DNC lists
    if(($settings["type"] != "wash") && api_restrictions_donotcontact_check_single($settings["type"], $target["destination"], $settings["donotcontact"], $settings["region"])) {

		api_misc_debug("N");

		api_targets_updatestatus($target["targetid"], "ABANDONED");

		$eventid = api_misc_uniqueid();

		api_data_responses_add($campaignid, $eventid, $target["targetid"], $target["targetkey"], "REMOVED", "DNC");
		api_data_callresult_add($campaignid, $eventid, $target["targetid"], "DNC");

    } else if(($settings["type"] != "wash") && api_restrictions_baddata_check_single($settings["type"], $target["destination"], null, $settings["region"])) {

		api_misc_debug("N");

		api_targets_updatestatus($target["targetid"], "ABANDONED");

		$eventid = api_misc_uniqueid();

		api_data_responses_add($campaignid, $eventid, $target["targetid"], $target["targetkey"], "REMOVED", (($settings["type"] == 'email') ? 'UNDELIVERABLE' : 'DISCONNECTED'));
		api_data_callresult_add($campaignid, $eventid, $target["targetid"], "DISCONNECTED");

	} else {

        // Spool the call
		if($settings["type"] == "phone")     $result = api_voice_generate($target, $settings);
		elseif($settings["type"] == "sms")   $result = api_sms_generatemessage($target, $settings);
		elseif($settings["type"] == "email") $result = api_email_generatemessage($target, $settings);
		elseif($settings["type"] == "wash")  $result = api_wash_generate($target, $settings);

        // Check if successful otherwise update target status
		if($result !== FALSE) {

			$lastsend[$campaignid] = microtime(true);

			$DELAY = FALSE;

			if($settings["type"] == "phone") api_misc_debug("C");
			elseif($settings["type"] == "sms") {

				api_misc_debug("S");
				api_targets_updatestatus($target["targetid"], "COMPLETE");

			} elseif($settings["type"] == "email"){

				api_misc_debug("E");
				api_targets_updatestatus($target["targetid"], "COMPLETE");

			} elseif($settings["type"] == "wash") api_misc_debug("W");

		} else {
			$statsd->increment("morpheus.spooler.errors");

			api_misc_debug("G");
			api_targets_updatestatus($target["targetid"], "REATTEMPT", time() + pow(EVENTQUEUE_ERROR_BACKOFF, $target["errors"]+1), 1);
			usleep(200000); // Back off for a small period of time

		}

	}

}
