<?php

use Services\Authenticators\GoogleMultiFactorAuthenticator;
use Services\Container\ContainerAccessor;

/**
 * Checks if the user is logged in and destroys session if the user has a different session saved in db
 * @param bool $extendSession
 * @return boolean
 */
function api_session_checklogin($extendSession = true){

	api_session_start();

		// Reset the expiration time upon page load
	if (isset($_COOKIE[SESSION_NAME]) AND $extendSession) setcookie(SESSION_NAME, session_id(), time() + SESSION_TIMEOUT, SESSION_PATH, $_SERVER['SERVER_NAME'], true, true);

	session_write_close();

	if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
		//Check if this is the latest session opened by the user
		if (api_session_verify_user_session_id()) {
			return true;
		}
		api_session_destroy(false);
	}

	api_misc_audit("AUTH", "DENIED");

	header("Location: /?action=nosession");
	exit;

}

// Login

function api_session_checkauth($username, $password){

	if(empty($username)) return api_error_raise("Invalid authentication details");

	$userid = api_users_checknameexists($username);

	if($userid == false) {
		api_misc_audit('auth', "Attempted login from invalid username:  $username");
		return api_error_raise("Invalid authentication details");
	}

	$multi = api_users_setting_get_multi_byitem($userid, array("saltedpassword", "status", "ipaccesslist"));

	if(empty($multi["status"]) OR ($multi["status"] != USER_STATUS_ACTIVE)) {
		switch ($multi['status']) {
			case USER_STATUS_DISABLED:
			default:
				$message = 'Sorry, your account has been suspended.';
				break;
			case USER_STATUS_INITIAL:
			case USER_STATUS_INITIAL_LEGACY:
				$message = 'Sorry, your account has not yet been activated. Please contact ReachTEL support at support@reachtel.com.au to activate your account.';
				break;
			case USER_STATUS_LOCKED:
				$message = 'Sorry, your account has been locked due to excessive incorrect password attempts.';
				break;
			case USER_STATUS_INACTIVE:
				$message = 'Your account has been suspended as it has been inactive for some time. Please contact ReachTEL support at support@reachtel.com.au to reactivate your account.';
				break;
		}
		api_misc_audit('auth', "Attempted login from suspended user: {$username}", $userid);
		return api_error_raise($message);
	}
	elseif(empty($multi["saltedpassword"]) OR (($multi["saltedpassword"] !== false) AND !password_verify($password, $multi["saltedpassword"]))) {

		$autherrors = api_users_setting_increment($userid, "autherrors");

		if($autherrors >= USER_LOGIN_MAX_ATTEMPTS) {

			api_users_setting_set($userid, "status", USER_STATUS_LOCKED);
			api_users_setting_set($userid, "autherrors", 0);

			$emailaddress = api_users_setting_getsingle($userid, "emailaddress");

			if(!empty($emailaddress)) {
				$email["to"] = $emailaddress;
				$email["bcc"] = "ReachTEL Support <support@reachtel.com.au>";
			} else {
				$email["to"] = "ReachTEL Support <support@reachtel.com.au>";
			}

			$email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";
			$email["subject"] = "[ReachTEL] User account suspension for '" . $username . "'";
			$email["textcontent"] = "Hello,\n\nOur system has disabled the following account due to multiple invalid authentication attempts:\n\n" . $username . "\n\nPlease contact ReachTEL support to organise reactivation.";
			$email["htmlcontent"] = "Hello,\n\nOur system has disabled the following account due to multiple invalid authentication attempts:\n\n" . $username . "\n\nPlease contact ReachTEL Support to organise reactivation.";
			api_email_template($email);
            api_misc_audit('auth', "User suspended for too many invalid password attempts: {$username}", $userid);
			return api_error_raise("Sorry, your account has been suspended");

		}

		if (!api_misc_is_test_environment()) {
			sleep($autherrors);
		}

		return api_error_raise("Invalid authentication details");

	// FIXME Temporarily disabled IP access list checking whilst we migrate from Brisbane to Sydney
	} elseif(0 AND !empty($multi["ipaccesslist"]) AND !api_session_ipcheck(api_misc_getip(), $multi["ipaccesslist"])) return api_error_raise("Invalid authentication details - IP address restrictions");
	else {

		if(password_needs_rehash($multi["saltedpassword"], PASSWORD_ALGO, array("cost" => PASSWORD_COST))) {
			api_users_setting_set($userid, "saltedpassword", password_hash($password, PASSWORD_ALGO, array("cost" => PASSWORD_COST)));
			api_misc_audit("AUTH", "Updated password hash algorithm for username=" . $username . "; userid=" . $userid);
		}

		api_users_setting_set($userid, "autherrors", 0);
		api_users_setting_set($userid, "lastauth", time());
		return $userid;
	}
}

function api_session_checkuserid($userid){

	if($userid == false) return api_error_raise("Invalid authentication details");
	elseif(!api_users_setting_getsingle($userid, "status")) return api_error_raise("Sorry, your account has been suspended.");
	else {
		api_users_setting_set($userid, "autherrors", 0);
		api_users_setting_set($userid, "lastauth", time());
		return $userid;
	}

}

/**
 * @return string
 */
function api_session_generate_new_id() {
	return bin2hex(openssl_random_pseudo_bytes(8));
}

/**
 * Logs in a user and saves the session id
 * @param array $request
 * @return boolean
 */
function api_session_login($request = array()){

		// Kill any existing session
	api_session_destroy();

	session_id(api_session_generate_new_id());

	if(empty($request) OR empty($request["type"])) return api_error_raise("Invalid authentication details");

	if ($request["type"] == "smsrequest") {
		$userid = api_session_checkauth($request["username"], $request["password"]);

		if (empty($userid)) {
			return false;
		}

		if (!($response = api_session_2fa_sms_request($userid))) {
			return api_error_raise("Invalid authentication details");
		}

		header("Location: /?" . http_build_query($response));
		exit;
	} else if ($request["type"] == "sms") {
		$userid = api_session_2fa_sms_response(api_users_checknameexists($request["username"]), $request["token"], $request["hmac"]);
	} else if ($request["type"] == "token") {
		$userid = api_session_2fa_token_response($request["username"], $request["password"], $request["token"]);
	} else {
		return api_error_raise("Invalid authentication details");
	}

	// if userid is false, we've already got an error message on the stack so just return
	if ($userid === false) {
		return false;
	}

	if(!api_session_checkuserid($userid)) return api_error_raise("Invalid authentication details");

	if(isset($userid) AND is_numeric($userid)){

		if (api_users_has_password_expired($userid)) {
			$sent = api_users_password_resetrequest($userid);

			$action = 'resetpassword';
			if (!$sent) {
				// no emailaddress found
				$action = 'resetpasswordsupport';
			}

			header("Location: /?action={$action}");
			exit;
		}

		api_session_start();

		api_misc_audit("AUTH", "LOGIN", $userid);

		$_SESSION['loggedin'] = true;
		$_SESSION[SESSION_KEY_USERID] = $userid;
		$_SESSION['username'] = api_users_setting_getsingle($userid, "username");
		$_SESSION['usertags'] = api_users_tags_get($userid);
		$_SESSION['displayname'] = api_users_setting_getsingle($userid, "firstname") . " " . api_users_setting_getsingle($userid, "lastname");
		$_SESSION['csrftoken'] = hash("sha512", mt_rand(0,mt_getrandmax()));
		session_write_close();
		api_users_store_session_id($userid, session_id(), true);

		return true;

	} else {

		api_misc_audit("AUTH", "INVALID");

		header("Location: /?action=invalid");
		exit;
	}

}

/**
 * Logs a user out and destroys session. Session id is removed from db only if the user is logging out from the same
 * session that was saved. This will avoid destroying the stored session id when the user logs out of previous sessions.
 * @return boolean
 */
function api_session_logout() {
	api_session_start();

	// This will avoid destroying the stored session id when the user logs out of previous sessions.
	return api_session_destroy(api_session_verify_user_session_id());
}

/**
 * Destroys existing session
 * @param boolean $destroy_user_session_id Setting this to true will destroy the saved session id for the user
 * @return boolean
 */
function api_session_destroy($destroy_user_session_id = true) {

	api_session_start();

	$userid = isset($_SESSION[SESSION_KEY_USERID]) ? $_SESSION[SESSION_KEY_USERID] : false;

	api_misc_audit("AUTH", "DESTROYSESSION");

	$_SESSION = array();

	if (isset($_COOKIE[session_name()])) setcookie(session_name(), '', time()-42000, SESSION_PATH, $_SERVER['SERVER_NAME'], true, true);

	if (session_destroy()) {
		if ($destroy_user_session_id && $userid) {
			if (!api_users_destroy_session_id($userid)) {
				return api_error_raise('Unable to destroy session id for the user id ' . $userid);
			}
		}

		return true;
	}

	return false;
}

function api_session_start() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
		session_name(SESSION_NAME);
		// Use secure cookie when it is not test env or htts is enabled
		$use_secure_cookie = !(api_misc_is_test_environment() && empty($_SERVER['HTTPS']));
		session_set_cookie_params(SESSION_TIMEOUT, SESSION_PATH, $_SERVER['SERVER_NAME'], true, true);
		session_start();
	}
}

/**
 * Verifies if it is the latest session opened by the user by checking if the session id is what has been saved
 * @return boolean
 */
function api_session_verify_user_session_id() {
	if (session_id() == '') {
		throw new \RuntimeException('Unable to verify user session id');
	}

	if (!isset($_SESSION[SESSION_KEY_USERID])) {
		return false;
	}

	return api_users_fetch_session_id($_SESSION[SESSION_KEY_USERID]) === session_id();
}

function api_session_ipcheck($ip, $accesslist){

	if(defined("SKIP_IP_CHECK")) return true;
	elseif(empty($accesslist)) return true;
	elseif(empty($ip)) return true;
	else {

		$allowedips = explode(",", $accesslist);

		foreach($allowedips as $allowedip){

			if(empty($allowedip)) continue;
			else if(preg_match("/" . trim($allowedip) . "/", $ip)) return true;
			else {

				$hosts = dns_get_record($allowedip, DNS_A + DNS_AAAA);

				if(!empty($hosts) AND is_array($hosts))
				foreach($hosts as $host) {
					if((!empty($host['ip']) AND ($ip == $host['ip'])) OR (!empty($host['ipv6']) AND ($ip == $host['ipv6']))) return true;
				}

			}
		}

		return false;
	}

}

function api_session_2fa_sms_request($userid, $options = []){

	if(!api_users_checkidexists($userid)) return false;

	$destination = api_data_numberformat(api_users_setting_getsingle($userid, "smstokendestination"), api_users_getregion($userid));

	if(empty($destination["destination"]) OR !preg_match("/mobile$/", $destination["type"])) return api_error_raise("Sorry, that user does not have a valid two factor token on their account");

	$username = api_users_setting_getsingle($userid, "username");

	if (api_misc_is_test_environment()) {

		// If we are in the test environment, we hardcode the SMS token value
		$token = "000111";

	} else {

		$token = str_pad(mt_rand(0,999999), 6, "0");
	}

	$message = SECURITY_TOKEN_TEXT . $token;

	api_sms_apisend("+" . $destination["destination"], $message, 1209);

	api_users_setting_set($userid, "smstokenvalue", $token);
	api_users_setting_set($userid, "smstokentimeout", time()+300);

	$hmac = hash_hmac("sha256", $username . $token, HMAC_HASH_BASE . "sms2fa");

	return array("username" => $username, "hmac" => $hmac);

}

function api_session_2fa_sms_response($userid, $token, $hmac, $options = array()){

	if(!api_users_checkidexists($userid)) return api_error_raise("Sorry, that user id is not valid");

	$username = api_users_setting_getsingle($userid, "username");

	$expectedhmac = hash_hmac("sha256", $username . $token, HMAC_HASH_BASE . "sms2fa");

	if($expectedhmac != $hmac) return api_error_raise("Invalid authentication details");

	$expectedtoken = api_users_setting_getsingle($userid, "smstokenvalue");

	if(empty($expectedtoken)) return api_error_raise("Invalid authentication details");

	if (api_misc_is_test_environment()) {

		// If we are in the test environment, we override the SMS token value
		$expectedtoken = "000111";

	}

	$tokentimeout = api_users_setting_getsingle($userid, "smstokentimeout");

	api_users_setting_delete_single($userid, "smstokenvalue");
	api_users_setting_delete_single($userid, "smstokentimeout");

	if(($expectedtoken == $token) AND ($tokentimeout > time())) return $userid;
	else return false;

}

function api_session_2fa_token_response($username, $password, $token){

	$userid = api_session_checkauth($username, $password);

	// if userid is false, we already have an error on the stack, so return
	if ($userid === false) {
		return false;
	}

	$authenticator = ContainerAccessor::getContainer()->get(GoogleMultiFactorAuthenticator::class);

	if (!$authenticator->checkCode($userid, $token)) {
		return api_error_raise("Invalid token received");
	}

	return $userid;

}

function api_session_csrf_check(){

	// Checks to see if there is a valid cross-site request forgery token in either $_GET['csrftoken'] or $_POST['csrftoken']. If not, log and fail.

	if(isset($_GET['csrftoken']) AND isset($_SESSION['csrftoken']) AND ($_GET['csrftoken'] == $_SESSION['csrftoken'])) return true;
	elseif(isset($_POST['csrftoken']) AND isset($_SESSION['csrftoken']) AND ($_POST['csrftoken'] == $_SESSION['csrftoken'])) return true;
	else {

		if(isset($_SESSION[SESSION_KEY_USERID])) $userid = $_SESSION[SESSION_KEY_USERID];
		else $userid = "";

		if(isset($_SERVER['REQUEST_URI'])) $uri = $_SERVER['REQUEST_URI'];
		else $uri = "";

		api_misc_audit("CSRF_FAILURE", "Userid=" . $userid . "; URL=" . $uri);

		print "CSRF attempt blocked";
		exit;
	}

}

function api_session_token_create($userid) {

	if(!api_session_checkuserid($userid)) return api_error_raise("Sorry, that is not a valid user id");

	$access_token = api_misc_randombytes(32);
	$refresh_token = api_misc_randombytes(32);

	$sql = "INSERT INTO `rest_tokens` (`userid`, `access_token`, `refresh_token`) VALUES (?, ?, ?)";
	$rs = api_db_query_write($sql, array($userid, $access_token, $refresh_token));

	if($rs) return array("access_token" => $access_token, "refresh_token" => $refresh_token, "token_type" => "Bearer", "expires_in" => REST_TOKEN_VALIDITYPERIOD * 60);
	else return api_error_raise("Sorry, we couldn't create that token");

}

function api_session_token_check($access_token) {

	if(empty($access_token)) return api_error_raise("Sorry, that is not a valid token");

	// We want to check if we have a token for this user ID that has a timestamp in the last REST_TOKEN_VALIDITYPERIOD minutes.
	$sql = "SELECT * FROM `rest_tokens` WHERE `access_token` = ? AND `timestamp` >= DATE_SUB(NOW(), INTERVAL ? MINUTE)";
	$rs = api_db_query_write($sql, array($access_token, REST_TOKEN_ACCESS_VALIDITYPERIOD));

	if(!$rs OR !$rs->RecordCount()) { // If the query failed or there are no records return false

		return false;

	}

	$userid = $rs->Fields("userid");

	if(!api_session_checkuserid($userid)) return api_error_raise("Sorry, that is not a valid user id");

	return $userid;

}

function api_session_token_refresh($refresh_token) {

	// Update the token access time to now
	$sql = "SELECT * FROM `rest_tokens` WHERE `refresh_token` = ? AND `timestamp` >= DATE_SUB(NOW(), INTERVAL ? MINUTE)";
	$rs = api_db_query_write($sql, array($refresh_token, REST_TOKEN_REFRESH_VALIDITYPERIOD));

	if($rs AND $rs->RecordCount() AND api_session_checkuserid($rs->Fields("userid"))) return api_session_token_create($rs->Fields("userid"));
	else return false;
}

function api_session_token_revoke($userid, $access_token) {

	if(!is_numeric($userid)) return api_error_raise("Sorry, that is not a valid user id");

	if(empty($access_token)) return api_error_raise("Sorry, that is not a valid token");

	$sql = "DELETE FROM `rest_tokens` WHERE `userid` = ? AND `access_token` = ? AND `timestamp` >= DATE_SUB(NOW(), INTERVAL ? MINUTE)";
	$rs = api_db_query_write($sql, array($userid, $access_token, REST_TOKEN_VALIDITYPERIOD));

	if($rs AND api_db_affectedrows()) return true;
	else return false;

}
