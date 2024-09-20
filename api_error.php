<?php

function api_error_raise($message, $type = 'error', $code = 1, $values = array(), $nodb = false){

	if(!isset($ERROR)) {

		if(!defined("ERROR_STACK")) define("ERROR_STACK", "morpheus");

		$GLOBALS['ERROR'] = &PEAR_ErrorStack::singleton(ERROR_STACK);

	}

	if(!empty($_SESSION['userid'])) $userid = $_SESSION['userid'];
	else $userid = null;

	if(($type == "error") AND ($nodb == false)) api_misc_audit("error", $message, $userid);

	$GLOBALS['ERROR']->push($code, $type, $values, $message);

	return false;

}

function api_error_purge(){

	global $ERROR;

	if(!isset($ERROR)) return true;

	$ERROR->getErrors(true);

	return true;

}

function api_error_checkforerror(){

	global $ERROR;

	if(!isset($ERROR)) return false;

	return $ERROR->hasErrors();

}

function api_error_geterrors(){
    $errors = [];
    if(api_error_checkforerror()) {
        global $ERROR;
        if($ERROR->hasErrors()){
            while($e = $ERROR->pop()) {
                $errors[] = $e["message"];
            }
        }
    }
    return $errors;
}

function api_error_notifyiferror(){

	global $ERROR;

	if(!isset($ERROR)) return false;

	if($ERROR->hasErrors()){

		while($e = $ERROR->pop()) api_templates_notify("error", $e["message"]);

		return true;

	} else return false;

}

function api_error_printiferror($options = array()){

	global $ERROR;

	if(!isset($ERROR)) return false;

	if($ERROR->hasErrors()){

		while($e = $ERROR->pop()) {

			if(isset($options["return"])) return $e["message"];
			else print $e["message"] . "\n";
		}

		return true;

	} else return false;


}

function api_error_audit($action, $value = null, $userid = null){

	if((!preg_match("/^[0-9]+$/", $userid)) AND ($userid != NULL)) return false;
	if(strlen($action) > 255) return false;
	if(strlen($value) > 1024) return false;

	$msg = "<22>Morpheus " . $_SERVER['SCRIPT_FILENAME'] . ": IP: " . api_misc_getip() . ". User ID: " . $userid . ". Action: " . $action . ". Value: " . $value;

	syslog(LOG_WARNING, $msg);

	return true;

}

?>