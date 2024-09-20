<?php

function api_queue_gearman_add($queue, $details, $notbefore = null, $errors = 0, $options = array()){

	global $gearmanClient;

	if(!in_array($queue, api_queue_get_valid_queue_items())) return api_error_raise("Sorry, that is not a valid queue type");

	if (!class_exists('GearmanClient')) {
		return api_error_raise("GearmanClient class was not found");
	}

	if(($queue == "email") AND isset($details["body"])){

		$uniqueid = "email-" . api_misc_randombytes();

		file_put_contents(SAVE_LOCATION . EMAILBODY_LOCATION . "/" . $uniqueid, $details["body"]);

		$details["bodyfile"] = $uniqueid;
		unset($details["body"]);

	}

	if(!isset($gearmanClient)) {

		$gearmanClient = new GearmanClient();

		$servers = array_map("trim", explode(",", QUEUE_GEARMAN_QUEUESERVERS));

		shuffle($servers);

		foreach($servers as $server) $gearmanClient->addServer($server, 4730);

		$gearmanClient->setTimeout(120000);

	}

	$job = array("queue" => $queue, "errors" => $errors, "details" => $details, "lodgetime" => microtime(true));

	// Certain queue jobs should always be high priority
	if(in_array($queue, array("cron", "addtarget", "pbxcomms", "filesync"))) $options["priority"] = "high";

	if(isset($options["block"]) AND $options["block"]) $result = $gearmanClient->doHigh($queue, serialize($job));
	elseif(isset($options["priority"]) AND ($options["priority"] == "high")) $result = $gearmanClient->doHighBackground($queue, serialize($job));
	elseif(isset($options["priority"]) AND ($options["priority"] == "low")) $result = $gearmanClient->doLowBackground($queue, serialize($job));
	else $result = $gearmanClient->doBackground($queue, serialize($job));

	if ($gearmanClient->returnCode() != GEARMAN_SUCCESS) return api_error_raise("Sorry, we couldn't submit that Gearman job. Error=" . $gearmanClient->error());
	else return $result;

}

function api_queue_gearman_distributor($job){

	global $statsd;

	$workload = $job->workload();
	$workload = @unserialize($workload);

	if(!is_array($workload)) return false;

	$queue = $job->functionName();
	if($queue == QUEUE_GEARMAN_DEFAULTQUEUE) $queue = $workload["queue"];

	print $queue;

	api_db_ping();

	$jobtype = "api_queue_process_" . $queue;

	$starttime = microtime(true);

	$result = $jobtype($workload["details"], $workload);

	$statsd->increment("morpheus.queue.jobs.processed." . $queue);

	if($result) {

		$queuetime = microtime(true) - $workload["lodgetime"];
		$processtime = microtime(true) - $starttime;

		$statsd->timing("morpheus.queue.processingtime." . $queue, sprintf("%01.4f", $processtime)); // The time from job submission, queue wait time to completion
		$statsd->timing("morpheus.queue.queuetime." . $queue, sprintf("%01.4f", $queuetime)); // The time to process the job
		$statsd->timing("morpheus.queue.delaytime." . $queue, sprintf("%01.4f", $queuetime - $processtime)); // The time spent waiting in the queue

		print " OK - " . sprintf("%01.4f", $processtime) . " " . sprintf("%01.4f", $queuetime) . " " . sprintf("%01.4f", $queuetime - $processtime) . "\n";

	} else {
		$lodgetime = microtime(true) - $workload["lodgetime"];
		$processtime = microtime(true) - $starttime;

		print " NOK - " . sprintf("%01.4f", $processtime) . " " . sprintf("%01.4f", $lodgetime) . " " . sprintf("%01.4f", $lodgetime - $processtime) . "\n";

		if(!api_queue_should_the_job_get_reattempted($workload)){

			file_put_contents("/tmp/job-permerror-" . uniqid() . ".txt", serialize($workload));

			api_misc_audit("JOB_PERMERROR", "Type=" . $queue . ";");

			$statsd->increment("morpheus.queue.errors.permanent." . $queue);

		} else {

			$statsd->increment("morpheus.queue.errors.retry." . $queue);

			api_queue_add($queue, $workload["details"], date("Y-m-d H:i:s", time() + pow(EVENTQUEUE_ERROR_BACKOFF, $workload["errors"]+1)), $workload["errors"]+1);

		}

	}

	return $result;

}

function api_queue_gearman_getjobcount(){

	$i = 0;
	$status["total"] = array("jobs" => 0, "activejobs" => 0, "workers" => 0);

	$servers = array_map("trim", explode(",", QUEUE_GEARMAN_QUEUESERVERS));

	foreach($servers as $queue_server){

		if(strpos($queue_server, ":") !== FALSE) $queue_server = "[" . trim($queue_server) . "]";

		$socket = fsockopen(trim($queue_server), 4730, $errno, $errstr, 5);

		if($socket) {

			fwrite($socket, "status\n");

			do {
				$line = trim(fgets($socket));

				if($line == ".") break;

				list($queue, $jobs, $activejobs, $workers) = explode("\t", $line);

				if(isset($status[$queue])) {
					$status[$queue]["jobs"] += $jobs;
					$status[$queue]["activejobs"] += $activejobs;
					$status[$queue]["workers"] += $workers;
				} else $status[$queue] = array("jobs" => $jobs, "activejobs" => $activejobs, "workers" => $workers);

				$status["total"]["jobs"] += $jobs;
				$status["total"]["activejobs"] += $activejobs;
				if($workers > $status["total"]["workers"]) $status["total"]["workers"] = $workers;

				$i++;

			} while(1);

		}

		fclose($socket);
	}

	return $status;

}

function api_queue_gearman_workermanagement(){

	$status = api_queue_gearman_getjobcount();

	if(!in_array($status)) return true;

	if(($status[QUEUE_GEARMAN_DEFAULTQUEUE]["jobs"] > (2 * $status[QUEUE_GEARMAN_DEFAULTQUEUE]["workers"])) AND ($status[QUEUE_GEARMAN_DEFAULTQUEUE]["workers"] < EVENTQUEUE_SPOOLER_CHILDREN)){

		// Increase workers

	} elseif(($status[QUEUE_GEARMAN_DEFAULTQUEUE]["jobs"] < $status[QUEUE_GEARMAN_DEFAULTQUEUE]["workers"]) AND ($status[QUEUE_GEARMAN_DEFAULTQUEUE]["workers"] > EVENTQUEUE_SPOOLER_CHILDREN)){

		// Decrease workers

	}

}

function api_queue_process_time($details){

	print sprintf("%01.4f", (microtime(true) - $details["time"])) . "\n";

	return true;

}

function api_queue_gearman_worker($queues = array()){

	global $validqueues;

	$started = time();
	$lastcheck = time();

	$gearmanWorker = new GearmanWorker();

	$gearmanWorker->addOptions(GEARMAN_WORKER_NON_BLOCKING);

	$servers = array_map("trim", explode(",", QUEUE_GEARMAN_QUEUESERVER_WORKERS));

	foreach($servers as $server) $gearmanWorker->addServer($server, 4730);

	$gearmanWorker->setTimeout(10000);

	if(count($queues) > 0) foreach($queues as $queue) $gearmanWorker->addFunction($queue, "api_queue_gearman_distributor");
	elseif(is_array($validqueues)) foreach($validqueues as $queue) $gearmanWorker->addFunction($queue, "api_queue_gearman_distributor");

	$j = 1;

	print "[" . $j . "] ";

	while(1){

		// Some code to check if we should exit and let the service tools restart us.
		if((time() - $lastcheck) > 10){

			$lastcheck = time();

			print ".";
			api_db_ping();

			if(api_keystore_get("SETTINGS", 0, "DAEMON_RESTART") > $started) exit;

		}

		$ret = $gearmanWorker->work();

		if ($gearmanWorker->returnCode() == GEARMAN_SUCCESS) {
			$j++;
			print "[" . $j . "] ";
		}

		if($gearmanWorker->returnCode() == GEARMAN_TIMEOUT) {
			print "TIMEOUT\n";
			api_db_ping();
			continue;
		} elseif($j >= EVENTQUEUE_SPOOLER_CHILDREN_MAXJOBS) exit;
		elseif(!@$gearmanWorker->wait()){

		}
	}

	exit;

}

?>
