<?php

require_once("Morpheus/api.php");

$started = time();

if(!api_sms_supplier_checkidexists($argv[1])) return api_error_raise("Sorry, that is not a valid supplier");

$tags = api_sms_supplier_tags_get($argv[1]);

$hosts = explode(",", $tags["host"]);

if(!empty($argv[2]) AND !empty($hosts[$argv[2]-1])) $host = trim($hosts[$argv[2]-1]);
else $host = trim($hosts[0]);

$ports = explode(",", $tags["port"]);

if(!empty($argv[2]) AND !empty($ports[$argv[2]-1])) $port = trim($ports[$argv[2]-1]);
else $port = trim($ports[0]);

if(!empty($tags["timezone"])) {

	$timezones = explode(",", $tags["timezone"]);

	if(!empty($argv[2]) AND !empty($timezones[$argv[2]-1])) $timezone = trim($timezones[$argv[2]-1]);
	else $timezone = trim($timezones[0]);

} else $timezone = "";

if(!empty($tags["charset"])) $charset = $tags["charset"];
else $charset = null;

$options = array("name" => api_sms_supplier_setting_getsingle($argv[1], "name"),
	"supplier" => $argv[1],
	"host" => $host,
	"port" => $port,
	"username" => api_sms_supplier_tags_get($argv[1], "username"),
	"password" => api_sms_supplier_tags_get($argv[1], "password"),
	"debug" => false,
	"charset" => $charset);

smppConnect($options);

// Gearman stuff
$worker= new GearmanWorker();
$worker->setTimeout(5000);

$servers = explode(",", QUEUE_GEARMAN_QUEUESERVER_WORKERS);
shuffle($servers);
foreach($servers as $server) $worker->addServer($server, 4730);

$worker->addFunction('smpp-' . $options["supplier"], 'smppSend');

$timesinceenquire = 0;

while(1){

	$worker->work();

	if((time() - $timesinceenquire) > SMPP_TIMEOUT){

		print "Sending enquire_link: ";

		try {
			$smpp->enquireLink();
			api_db_ping();

		} catch (Exception $e){

			print "Failed. Reconnecting...";
			api_error_raise("Failed to send enquire_link on " . $options["name"] . " SMPP TX bind");
			sleep(3);
			smppConnect($options);
			continue;

		}

		$timesinceenquire = time();
		print "OK\n";

		if(api_keystore_get("SETTINGS", 0, "DAEMON_RESTART") > $started) exit;

	}

	if($worker->returnCode() == GEARMAN_SUCCESS) {

		print "OK\n";
		continue;

	} elseif($worker->returnCode() == GEARMAN_WORK_FAIL){

		print "Work failed. Reconnecting...";
		smppConnect($options);
		continue;

	} // else print "Spinning\n";

}

function smppConnect($options = array()){

	global $smpptransport, $smpp;

	require_once("Morpheus/lib/php-smpp/smppclient.class.php");
	require_once("Morpheus/lib/php-smpp/gsmencoder.class.php");
	require_once("Morpheus/lib/php-smpp/sockettransport.class.php");

	SocketTransport::$forceIpv4=true;
	if($options["debug"]) SocketTransport::$defaultDebug=true;

	$smpptransport = new SocketTransport(array($options["host"]), $options["port"]);

	$smpptransport->setRecvTimeout(SMPP_TIMEOUT * 1000);
	$smpptransport->setSendTimeout(SMPP_TIMEOUT * 1000);

	$smpp = new \Services\MorpheusSmppClient($smpptransport);

	// Activate binary hex-output of server interaction
	if($options["debug"]) $smpp->debug = true;
	if($options["debug"]) $smpptransport->debug = true;

	// Open the connection
	print "Opening transport to " .$options["host"].":".$options["port"].": ";

	try {

		$smpptransport->open();

	} catch (Exception $e){

		print "Failed to open SMPP transport";
		api_error_raise("Failed to open " . $options["name"] . " SMPP TX transport");
		sleep(10);
		exit;

	}

	print "OK\n";

	print "Binding transmitter: ";

	try {

		$smpp->bindTransmitter($options["username"], $options["password"]);

	} catch (Exception $e){

		print "Failed to bind SMPP transport";
		api_error_raise("Failed to bind " . $options["name"] . " SMPP TX transport");
		sleep(10);
		exit;
	}

	print "OK\n";

	// Optional connection specific overrides
	SmppClient::$sms_null_terminate_octetstrings = false;
	SmppClient::$csms_method = SmppClient::CSMS_8BIT_UDH;
	SmppClient::$sms_registered_delivery_flag = SMPP::REG_DELIVERY_SMSC_BOTH;

}

function smppSend($job){

	global $smpp, $options;

	$job = unserialize($job->workload());
    print "SMPPSend function called.\n";

	// Prepare message
	if (! is_null($options["charset"])) {
		$encodedMessage = iconv("UTF-8", $options["charset"] . "//TRANSLIT//IGNORE", $job["content"]);
	} else {
		$encodedMessage = GsmEncoder::utf8_to_gsm0338($job["content"]);
	}

	if(preg_match("/^1[38]00[0-9]{6}$/", $job["from"]) OR preg_match("/^1[38][0-9]{4}$/", $job["from"])) $from = new SmppAddress($job["from"], SMPP::TON_NATIONAL, SMPP::NPI_E164);
	elseif(is_numeric($job["from"])) $from = new SmppAddress($job["from"], SMPP::TON_INTERNATIONAL, SMPP::NPI_E164);
	else $from = new SmppAddress($job["from"], SMPP::TON_ALPHANUMERIC, SMPP::NPI_UNKNOWN);

	if(!empty($job["deliveryreceipt"]) AND ($job["deliveryreceipt"] == false)) {
		SmppClient::$sms_registered_delivery_flag = SMPP::REG_DELIVERY_NO;
		$adddr = true;
	} else {
		SmppClient::$sms_registered_delivery_flag = SMPP::REG_DELIVERY_SMSC_BOTH;
		$adddr = false;
	}

	$to = new SmppAddress($job["to"], SMPP::TON_INTERNATIONAL, SMPP::NPI_E164);

	$tags = array();

	if(!empty($job["tags"]) AND is_array($job["tags"])) foreach($job["tags"] as $tagid => $value) $tags[] = new SmppTag($tagid, $value, strlen($value));

	if(isset($job["expiry"]) AND is_numeric($job["expiry"]) AND ($job["expiry"] < 14400) AND ($job["expiry"] > 0)){

		$validityPeriod = "0000" . sprintf("%02d", $job["expiry"] / 1440) . sprintf("%02d", $job["expiry"] / 60) . sprintf("%02d", $job["expiry"] % 60) . "00000R";

		print "Set validityPeriod=" . $validityPeriod . "\n";

	} else $validityPeriod = null;

	// Send
	try {
       print "SMPP SEND SMS FROM:" .$job["from"]. " AND TO: ".$job["to"]."\n";
		$supplierid = $smpp->sendSMS($from, $to, $encodedMessage, $tags, SMPP::DATA_CODING_DEFAULT, 0x00, null, $validityPeriod);
       print "SMPP Send completed: FROM:" .$job["from"]. " AND TO: ".$job["to"]. "\n";

	} catch (Exception $e){

		$result = array();

		if($e->getMessage() == "Invalid Dest Addr"){ // This represents an invalid destination

			$result["supplierid"] = api_sms_supplier_increment($options["supplier"], "errorcounter");
			$result["dr"] = array("supplier" => $options["supplier"], "supplieruid" => $result["supplierid"], "status" => "UNDELIVERED", "code" => "0A", "supplierdate" => time());

			print date("c") . ": MsgId=" . $result["supplierid"] . "; From=" . $job["from"] . "; To=" . $job["to"] . "; Invalid Dest Addr; Status=";

			return serialize($result);

		} else {

			print "Failed to send message on " . $options["name"] . " SMPP bind; Error=" . $e->getMessage();
			api_error_raise("Failed to send message on " . $options["name"] . " SMPP bind; Msg=" . $job["content"] . "; Err=" . $e->getMessage() . "; Source=" . $job["from"]);
			return false;
		}

	}

	$supplierid = trim($supplierid);

	if(!empty($supplierid)) {

		$result = array("supplierid" => $supplierid);

		if($adddr) $result["dr"] = array("supplier" => $options["supplier"], "supplieruid" => $supplierid, "status" => "DELIVERED", "code" => "0", "supplierdate" => time());

		print date("c") . ": MsgId=" . $supplierid . "; From=" . $job["from"] . "; To=" . $job["to"] . "; Status=";

		return serialize($result);

	} else {

		api_misc_audit("SMPP_SEND_ERROR", "Supplier=" . $options["name"] . "; Received=" . serialize($result));
		return false;
	}

}
