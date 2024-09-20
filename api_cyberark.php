<?php

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/api_misc.php');

use Equifax\CyberArkWrapper\PasswordAccessor;

/**
 * Get CyberArk Password object
 *
 * @param string $appId
 * @param string $safe
 * @param string $object
 *
 * @return Equifax\CyberArkWrapper\Models\Password|false
 */
function api_cyberark_get_password($appId, $safe, $object) {
	// set max retries and retry interval
	PasswordAccessor::setRetryIntervalMs(CYBERARK_RETRY_INTERVAL_MS);
	PasswordAccessor::setRetryMaxAttempts(CYBERARK_RETRY_MAX);

	// set event handlers for logging
	PasswordAccessor::addEventHandler(
		PasswordAccessor::EVENT_MAX_ATTEMPTS,
		function($payload) {
			$e = $payload['exception'];
			api_misc_audit('CYBERARK', 'Reached max attempts due to password change in process: ' . $e->getMessage());
		}
	);
	PasswordAccessor::addEventHandler(
		PasswordAccessor::EVENT_CHANGE_RETRY,
		function($payload) {
			$e = $payload['exception'];
			$pass_attempt = $payload['attemptNumber'];
			api_misc_audit('CYBERARK', sprintf('Retry (#%d) due to password change in process: %s', $pass_attempt, $e->getMessage()));
		}
	);
	PasswordAccessor::addEventHandler(
		PasswordAccessor::EVENT_ERROR,
		function($payload) {
			$e = $payload['exception'];
			api_misc_audit('CYBERARK', 'Something went wrong fetching password: ' . $e->getMessage());
		}
	);

	$password = PasswordAccessor::get($appId, $safe, $object);
	return $password;
}
