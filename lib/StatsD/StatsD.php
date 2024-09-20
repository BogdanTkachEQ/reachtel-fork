<?php

/**
* Sends statistics to the stats daemon over UDP
*
**/

class StatsD {

	private $socket;

	public static $host = "perf.reachtel.com.au";
	public static $port = 8125;

	/**
	* Sets one or more timing values
	*
	* @param string|array $stats The metric(s) to set.
	* @param float $time The elapsed time (ms) to log
	**/
	public function timing($stats, $time) {
		StatsD::updateStats($stats, $time, 1, 'ms');
	}

	/**
	* Sets one or more gauges to a value
	*
	* @param string|array $stats The metric(s) to set.
	* @param float $value The value for the stats.
	**/
	public function gauge($stats, $value) {
		StatsD::updateStats($stats, $value, 1, 'g');
	}

	/**
	* A "Set" is a count of unique events.
	* This data type acts like a counter, but supports counting
	* of unique occurences of values between flushes. The backend
	* receives the number of unique events that happened since
	* the last flush.
	*
	* The reference use case involved tracking the number of active
	* and logged in users by sending the current userId of a user
	* with each request with a key of "uniques" (or similar).
	*
	* @param string|array $stats The metric(s) to set.
	* @param float $value The value for the stats.
	**/
	public function set($stats, $value) {
		StatsD::updateStats($stats, $value, 1, 's');
	}

	/**
	* Increments one or more stats counters
	*
	* @param string|array $stats The metric(s) to increment.
	* @param float|1 $sampleRate the rate (0-1) for sampling.
	* @return boolean
	**/
	public function increment($stats, $sampleRate=1) {
		StatsD::updateStats($stats, 1, $sampleRate, 'c');
	}

	/**
	* Decrements one or more stats counters.
	*
	* @param string|array $stats The metric(s) to decrement.
	* @param float|1 $sampleRate the rate (0-1) for sampling.
	* @return boolean
	**/
	public function decrement($stats, $sampleRate=1) {
		StatsD::updateStats($stats, -1, $sampleRate, 'c');
	}

	/**
	* Updates one or more stats.
	*
	* @param string|array $stats The metric(s) to update. Should be either a string or array of metrics.
	* @param int|1 $delta The amount to increment/decrement each metric by.
	* @param float|1 $sampleRate the rate (0-1) for sampling.
	* @param string|c $metric The metric type ("c" for count, "ms" for timing, "g" for gauge, "s" for set)
	* @return boolean
	**/
	private function updateStats($stats, $delta=1, $sampleRate=1, $metric='c') {
		if (!is_array($stats)) { $stats = array($stats); }
		$data = array();
		foreach($stats as $stat) {
			$data[$stat] = "$delta|$metric";
		}

		StatsD::send($data, $sampleRate);
	}

	/*
	* Squirt the metrics over UDP
	**/
	private function send($data, $sampleRate=1) {

		/*
		 * DO NOT SEND METRICS FOR DEV OR TEST ENV
		 *
		 * NOTE: The following APP_ENVIRONMENT check is a copy of api_misc_is_test_environment(),
		 *       but we do not include api_misc.php in REST, so prefer to copy that check instead.
		 */
		if ((defined('APP_ENVIRONMENT') && in_array(APP_ENVIRONMENT, ['test', 'phpunit']))) {
			return;
		}

		global $socket;
		// sampling
		$sampledData = array();

		if ($sampleRate < 1) {
			foreach ($data as $stat => $value) {
				if ((mt_rand() / mt_getrandmax()) <= $sampleRate) {
					$sampledData[$stat] = "$value|@$sampleRate";
				}
			}
		} else {
			$sampledData = $data;
		}

		if (empty($sampledData)) { return; }

		// Wrap this in a try/catch - failures in any of this should be silently ignored
		try {

			if(empty($socket)) {
				$socket = fsockopen("udp://" . self::$host, self::$port, $errno, $errstr);
			}
			if (! $socket) {
				return;
			}
			foreach ($sampledData as $stat => $value) {
				fwrite($socket, "$stat:$value");
			}
		} catch (Exception $e) {
			unset($socket);
		}
	}
}