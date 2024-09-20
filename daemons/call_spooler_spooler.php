<?php

require_once(__DIR__ . "/../api.php");

$started = time();
$call_spooler_boost_max = defined('CALL_SPOOLER_BOOST_MAX') && (int) CALL_SPOOLER_BOOST_MAX > 1 ? (int) CALL_SPOOLER_BOOST_MAX : 1;

$spooler_command = "/usr/bin/php " . __DIR__ . "/call_spooler_one.php";

// This will contain processes that has been sent a termination signal. Since call_spooler_one.php shuts down gracefully
// by taking some time, this list of processes will allow us to not send termination signal again.
$sigtermed_processes = [];

while(1) {
	$forked = array();
	unset($processes);

	exec("pgrep -fal '$spooler_command'", $processes);

	if(!empty($processes) AND is_array($processes))
		foreach($processes as $process) {
			if(preg_match("/(\d+) " . preg_quote($spooler_command, '/') . " (\d+)$/", $process, $matches)) {
				if (!isset($forked[$matches[2]])) {
					$forked[$matches[2]] = [];
				}

				$forked[$matches[2]][] = $matches[1];
			}
		}

	foreach ($sigtermed_processes as $campaignid => &$pids) {
		// Remove the processes that are not in both forked and terminated list and make it the new teminated list
		// so that the new terminated list will not have redundant data(those porcesses that are already killed).
		if (isset($forked[$campaignid])) {
			$pids = array_intersect($pids, $forked[$campaignid]);

			if (count($pids)) {
				continue;
			}
		}
		unset($sigtermed_processes[$campaignid]);

	}

	$campaigns = api_campaigns_list_active();
	foreach($campaigns as $campaignid) {
		$max_processes_count = (
			api_campaigns_setting_getsingle($campaignid, CAMPAIGN_SETTING_BOOST_SPOOLER) ===
			VALUE_ON
		) ? $call_spooler_boost_max : 1;

		$existing_processes_count = isset($forked[$campaignid]) ? count($forked[$campaignid]) : 0;

		if ($max_processes_count === $existing_processes_count) {
			continue;
		}

		$spool_campaign_command = $spooler_command . " " . $campaignid;

		if ($max_processes_count > $existing_processes_count) {
			$remaining_processes_count = ($max_processes_count - $existing_processes_count);
			print "FORKED: " . $campaignid;
			for ($i = 0; $i < $remaining_processes_count; $i++) {
				exec($spool_campaign_command . " > /dev/null 2>&1 &");
			}

			if ($i) {
				print " [$i time(s)]";
			}

			print "\n";
		} else {
			// This is required since the campaign booster setting could be turned off but daemons would not have restarted
			$excess_process_count = ($existing_processes_count - $max_processes_count);
			$campaign_processes = $forked[$campaignid];
			for ($i = 0; $i < $excess_process_count; $i++) {
				if (isset($sigtermed_processes[$campaignid]) && in_array($campaign_processes[$i], $sigtermed_processes[$campaignid])) {
					// Termination signal has already been sent to this process. So don't do anything.
					continue;
				}
				$process_output = [];
				// Check if the process is still active
				exec("ps -o cmd fp " . $campaign_processes[$i], $process_output);
				if (
					isset($process_output[1]) &&
					$process_output[1] === $spool_campaign_command
				) {
					if (posix_kill($campaign_processes[$i], SIGTERM)) {
						if (!isset($sigtermed_processes[$campaignid])) {
							$sigtermed_processes[$campaignid] = [];
						}
						$sigtermed_processes[$campaignid][] = $campaign_processes[$i];
						print "Sent termination signal to process " . $campaign_processes[$i] . ' for campaign id ' . $campaignid . "\n";
					} else {
						print "Failed to kill process " . $campaign_processes[$i] . ' for campaign id ' . $campaignid . "\n";
					}
				}
			}
		}
	}

	unset($forked);

	sleep(1);

	if(api_keystore_get("SETTINGS", 0, "DAEMON_RESTART") > $started) exit;
}

?>
