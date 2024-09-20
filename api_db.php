<?php

// These files modify the way adodb works, so still need to be included
// vendor/adodb/adodb-php/adodb.inc.php is autoloaded
require_once("vendor/adodb/adodb-php/adodb-exceptions.inc.php");
require_once("vendor/adodb/adodb-php/adodb-perf.inc.php");
require_once(__DIR__ . '/api_misc.php');

// Connect

function api_db_read_connect($username = null, $password = null, $database = null, $hostname = null) {

	if(defined("DB_SINGLE_CONNECTION") AND (DB_SINGLE_CONNECTION == "1")) return true;

	global $DB_READ;
	global $DB_ATTEMPTS;

	if($DB_ATTEMPTS > 10) {

		return api_error_raise("Too many failed database connection attempts");
	}

	$DB_ATTEMPTS = $DB_ATTEMPTS + 1;

	if(!defined("DB_CONNECTION")) define("DB_CONNECTION", "mysqli");
	if(!defined("DB_MYSQL_READ_USERNAME")) define("DB_MYSQL_READ_USERNAME", "morpheus_read");
	if(!defined("DB_MYSQL_READ_PORT")) define("DB_MYSQL_READ_PORT", "3306");
	if(!defined("DB_MYSQL_READ_HOST")) define("DB_MYSQL_READ_HOST", "read.db.reachtel.com.au");
	if(!defined("DB_MYSQL_DATABASE")) define("DB_MYSQL_DATABASE", "morpheus");

	if (!$username) {
		$username = DB_MYSQL_READ_USERNAME;
	}
	if (!$password) {
		$password = DB_MYSQL_READ_PASSWORD;
	}
	if (!$database) {
		$database = DB_MYSQL_DATABASE;
	}
	if (!$hostname) {
		$hostname = DB_MYSQL_READ_HOST;
	}

	try {

		$DB_READ = ADOnewConnection(DB_CONNECTION);

		if (!defined("DB_CONNECTION_NO_SSL")) {
			$DB_READ->setConnectionParameter('clientflags', MYSQL_CLIENT_SSL);
		}

		$DB_READ->SetFetchMode(ADODB_FETCH_ASSOC);
		$DB_READ->connect($hostname, $username, api_db_get_db_read_password($password), $database) or api_error_raise("Failed to connect to the database");

	} catch (Exception $e){

		@api_error_raise("Database authentication error");

	}

	$DB_READ->Execute("SET time_zone = '+10:00';");

}

/**
 * Connect to write DB
 *
 * @param string $username
 * @param string $password
 * @param string $database
 * @param string $hostname
 * @return void
 */
function api_db_write_connect($username = null, $password = null, $database = null, $hostname = null) {

	global $DB_WRITE;
	global $DB_ATTEMPTS;

	if($DB_ATTEMPTS > 10) {

		return api_error_raise("Too many failed database connection attempts");
	}

	$DB_ATTEMPTS = $DB_ATTEMPTS + 1;

	if(!defined("DB_CONNECTION")) define("DB_CONNECTION", "mysqli");
	if(!defined("DB_MYSQL_WRITE_USERNAME")) define("DB_MYSQL_WRITE_USERNAME", "morpheus_write");
	if(!defined("DB_MYSQL_WRITE_PORT")) define("DB_MYSQL_WRITE_PORT", "3306");
	if(!defined("DB_MYSQL_WRITE_HOST")) define("DB_MYSQL_WRITE_HOST", "write.db.reachtel.com.au");
	if(!defined("DB_MYSQL_DATABASE")) define("DB_MYSQL_DATABASE", "morpheus");

	if (!$username) {
		$username = DB_MYSQL_WRITE_USERNAME;
	}
	if (!$password) {
		$password = DB_MYSQL_WRITE_PASSWORD;
	}
	if (!$database) {
		$database = DB_MYSQL_DATABASE;
	}
	if (!$hostname) {
		$hostname = DB_MYSQL_WRITE_HOST;
	}

	try {

		$DB_WRITE = ADOnewConnection(DB_CONNECTION);

		if (!defined("DB_CONNECTION_NO_SSL")) {
			$DB_WRITE->setConnectionParameter('clientflags', MYSQL_CLIENT_SSL);
		}

		$DB_WRITE->SetFetchMode(ADODB_FETCH_ASSOC);
		$DB_WRITE->connect($hostname, $username, api_db_get_db_write_password($password), $database) or api_error_raise("Failed to connect to the database");

	} catch (Exception $e){

		@api_error_raise("Database authentication error");
		throw $e;
	}

	$DB_WRITE->Execute("SET time_zone = '+10:00';");
}

/**
 * Decrypt and return password
 *
 * @param string $password
 *
 * @return string
 *
 * @throws Exception Missing encryption details in environment variables.
 */
function api_db_get_db_password($password) {
	if (defined('DB_PASSWORD_ENCRYPTED') && DB_PASSWORD_ENCRYPTED) {
		$crypto_key = getenv('DB_PASSWORD_ENCRYPT_CRYPTO_KEY');
		$crypto_iv = getenv('DB_PASSWORD_ENCRYPT_CRYPTO_IV');

		if (!$crypto_key || !$crypto_iv) {
			throw new Exception('DB encryption details missing in environment');
		}

		// returns false if decryption failed - fall back to cleartext on failure
		$decrypted_password = api_misc_decrypt_base64($password, $crypto_key, $crypto_iv);
		return $decrypted_password ?: $password;
	}

	return $password;
}

/**
 * Decrypt and return read password
 *
 * @param string $password
 *
 * @return string
 */
function api_db_get_db_read_password($password = DB_MYSQL_READ_PASSWORD) {
	return api_db_get_db_password($password);
}

/**
 * Decrypt and return write password
 *
 * @param string $password
 *
 * @return string
 */
function api_db_get_db_write_password($password = DB_MYSQL_WRITE_PASSWORD) {
	return api_db_get_db_password($password);
}

// Close

function api_db_close(){

	global $DB_READ;
	global $DB_WRITE;
	global $ADODB_PERF_MIN;
	global $DB_ATTEMPTS;

	$DB_READ = null;
	$DB_WRITE = null;
	$DB_ATTEMPTS = 0;
}

// Ping

function api_db_ping($username = null, $password = null, $database = null, $hostname = null){

	global $DB_READ;
	global $DB_WRITE;

	if((!isset($DB_READ)) OR (!$DB_READ->IsConnected())) api_db_read_connect($username, $password, $database, $hostname);
	if((!isset($DB_WRITE)) OR (!$DB_WRITE->IsConnected())) api_db_write_connect($username, $password, $database, $hostname);

	$sql = "SELECT 1";
	$rs = api_db_query_read($sql);
	$rs = api_db_query_write($sql);

	return $rs;
}

/**
 * Query - Read
 *
 * @param string $query
 * @param array $params
 *
 * @return ADORecordSet_mysqli|false
 */
function api_db_query_read($query, $params = array()){

	global $DB_READ;

	if(defined("DB_SINGLE_CONNECTION") AND (DB_SINGLE_CONNECTION == "1")) return api_db_query_write($query, $params);

	if((!isset($DB_READ)) OR (!$DB_READ->IsConnected())) api_db_read_connect();

	try {

		$rs = $DB_READ->Execute($query, $params);

	} catch (Exception $e){

		api_error_raise("Query Read failed - Q=" . $query . "; P=" . serialize($params) . "; ErrorNo=" . $DB_READ->ErrorNo() . "; ErrorMsg=" . $DB_READ->ErrorMsg() . "; [A_D_Q_R_CE]");

		// If the "MySQL server has gone away", delete the connection handle and sleep for a second to back off
		if($DB_READ->ErrorNo() == 2006) {

			api_misc_audit("DB_ERROR", "Dropping read connection");

			unset($GLOBALS["DB_READ"]);
			unset($rs);

			sleep(1);

		}

	}

	if(isset($rs)) return $rs;
	else return false;

}



function api_db_query_write($query, $params = array()){

	global $DB_WRITE;

	if((!isset($DB_WRITE)) OR (!$DB_WRITE->IsConnected())) api_db_write_connect();

	try {

		$rs = $DB_WRITE->Execute($query, $params);

	} catch (Exception $e){

		api_error_raise("Query Write failed - Q=" . $query . "; P=" . serialize($params) . "; ErrorNo=" . $DB_WRITE->ErrorNo() . "; ErrorMsg=" . $DB_WRITE->ErrorMsg() . "; [A_D_Q_W_CE]");

		// If the "MySQL server has gone away", delete the connection handle and sleep for a second to back off
		if($DB_WRITE->ErrorNo() == 2006) {

			api_misc_audit("DB_ERROR", "Dropping write connection");

			unset($GLOBALS["DB_WRITE"]);
			unset($rs);

			sleep(1);

		}


	}

	if(isset($rs)) return $rs;
	else return false;

}



// Replace

function api_db_replace($table, $fields, $keys, $autoReplace = false){

	global $DB_WRITE;

	if((!isset($DB_WRITE)) OR (!$DB_WRITE->IsConnected())) api_db_write_connect();

	try {

		$rs = $DB_WRITE->Replace($table, $fields, $keys, $autoReplace);

	} catch (Exception $e){

		api_error_raise("Query failed - Q=" . $query . "; P=" . serialize($fields) . "; E=" . $DB_WRITE->ErrorMsg() . ". [A_D_R_CE]");

		// If the "MySQL server has gone away", delete the connection handle and sleep for a second to back off
		if($DB_WRITE->ErrorNo() == 2006) {

			unset($DB_WRITE);

			sleep(1);

		}

	}

	if(isset($rs)) return $rs;
	else return false;

}

// Return last inserted ID

function api_db_lastid(){

	global $DB_WRITE;

	if((!isset($DB_WRITE)) OR (!$DB_WRITE->IsConnected())) api_db_write_connect();

	if($DB_WRITE->hasInsertID) $id = $DB_WRITE->Insert_ID();
	else return false;

	return $id;

}

// Start a transaction

function api_db_starttrans(){

	global $DB_WRITE;

	if((!isset($DB_WRITE)) OR (!$DB_WRITE->IsConnected())) api_db_write_connect();

	try{

		$result = $DB_WRITE->StartTrans();

	} catch (Exception $e){

		return api_error_raise("Transaction failed - " . $DB_WRITE->ErrorMsg() . ". [A_D_S_Q]");

	}

	return $result;

}

// End a transaction

function api_db_endtrans(){

	global $DB_WRITE;

	if((!isset($DB_WRITE)) OR (!$DB_WRITE->IsConnected())) api_db_write_connect();

	try{

		$result = $DB_WRITE->CompleteTrans();

	} catch (Exception $e){

		return api_error_raise("Transaction failed - " . $DB_WRITE->ErrorMsg() . ". [A_D_E_Q]");

	}

	return $result;

}

// Fail a transaction

function api_db_failtrans(){

	global $DB_WRITE;

	if((!isset($DB_WRITE)) OR (!$DB_WRITE->IsConnected())) api_db_write_connect();

	try{

		$result = $DB_WRITE->FailTrans();

	} catch (Exception $e){

		return api_error_raise("Transaction failed - " . $DB_WRITE->ErrorMsg() . ". [A_D_F_Q]");

	}

	return $result;

}

// Turn debugging on

function api_db_debug_on(){

	global $DB_READ;
	global $DB_WRITE;

	if((!isset($DB_READ)) OR (!$DB_READ->IsConnected())) api_db_read_connect();
	if((!isset($DB_WRITE)) OR (!$DB_WRITE->IsConnected())) api_db_write_connect();

	$DB_READ->debug = true;
	$DB_WRITE->debug = true;

	return true;

}

// Turn debugging off

function api_db_debug_off(){

	global $DB_READ;
	global $DB_WRITE;

	if((!isset($DB_READ)) OR (!$DB_READ->IsConnected())) api_db_read_connect();
	if((!isset($DB_WRITE)) OR (!$DB_WRITE->IsConnected())) api_db_write_connect();

	$DB_READ->debug = false;
	$DB_WRITE->debug = false;

	return true;

}

// Change DB

function api_db_changedb($newdb){

	global $DB_READ;
	global $DB_WRITE;

	if((!isset($DB_READ)) OR (!$DB_READ->IsConnected())) api_db_read_connect();
	if((!isset($DB_WRITE)) OR (!$DB_WRITE->IsConnected())) api_db_write_connect();

	api_db_query_read("USE " . $newdb);
	api_db_query_write("USE " . $newdb);

	return true;

}

// Quote string

function api_db_qstr($string){

	global $DB_WRITE;

	if((!isset($DB_WRITE)) OR (!$DB_WRITE->IsConnected())) api_db_write_connect();

	return $DB_WRITE->qstr($string);

}

// Get next auto increment ID

function api_db_nextid($table){

	global $DB_WRITE;

	if(empty($table)) return false;

	$sql = "SELECT `AUTO_INCREMENT` as `nextid` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = ? AND `TABLE_NAME` = ?";
	$rs = api_db_query_write($sql, array(DB_MYSQL_DATABASE, $table));

	if($rs and ($rs->RecordCount() > 0)) return $rs->Fields("nextid");
	else return false;

}

// Return affected rows

function api_db_affectedrows(){

	global $DB_WRITE;

	if((!isset($DB_WRITE)) OR (!$DB_WRITE->IsConnected())) api_db_write_connect();

	return $DB_WRITE->Affected_Rows();

}

/**
 * Get last error message from DB on the write connection
 *
 * @param boolean $forUI
 *
 * @return string
 */
function api_db_last_error_write($forUI = false) {
	global $DB_WRITE;
	if (!isset($DB_WRITE) || (!$DB_WRITE->isConnected())) {
		api_db_write_connect();
	}

	if ($forUI) {
		$code = $DB_WRITE->MetaError();
		if ($message = adodb_errormsg($code)) {
			return ucfirst($message);
		}
	}

	return $DB_WRITE->ErrorMsg();
}

/**
 * Get last error message from DB on the read connection
 *
 * @param boolean $forUI
 *
 * @return string
 */
function api_db_last_error_read($forUI = false) {
	global $DB_READ;
	if (defined('DB_SINGLE_CONNECTION') && (DB_SINGLE_CONNECTION === '1')) {
		return api_db_last_error_write();
	}

	if (!isset($DB_READ) || (!$DB_READ->isConnected())) {
		api_db_read_connect();
	}

	if ($forUI) {
		$code = $DB_WRITE->MetaError();
		if ($message = adodb_errormsg($code)) {
			return ucfirst($message);
		}
	}

	return $DB_READ->ErrorMsg();
}

/**
 * Close DB connections and reopen with given credentials
 *
 * @param string $username
 * @param string $password
 * @param string $database
 * @param string $hostname
 *
 * @return void
 */
function api_db_switch_connection($username, $password, $database = null, $hostname = null) {
	api_db_close();

	api_db_read_connect($username, $password, $database, $hostname);
	api_db_write_connect($username, $password, $database, $hostname);
}

/**
 * Close DB connections and reopen with standard credentials
 *
 * @return void
 */
function api_db_reset_connection() {
	api_db_close();

	api_db_read_connect();
	api_db_write_connect();
}
