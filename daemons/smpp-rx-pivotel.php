<?php

require_once("Morpheus/api.php");
require_once("Morpheus/lib/php-smpp/smppclient.class.php");
require_once("Morpheus/lib/php-smpp/sockettransport.class.php");
require_once("Morpheus/lib/php-smpp/gsmencoder.class.php");

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

$options = array("name" => api_sms_supplier_setting_getsingle($argv[1], "name"),
	"supplier" => $argv[1],
	"host" => $host,
	"port" => $port,
	"username" => $tags["username"],
	"password" => $tags["password"],
	"debug" => false);

// Construct transport and client
$transport = new SocketTransport(array($options["host"]), $options["port"]);
$transport->setRecvTimeout(SMPP_TIMEOUT * 1000); // for this example wait up to 60 seconds for data
$transport->setSendTimeout(SMPP_TIMEOUT * 1000); // for this example wait up to 60 seconds for data
$smpp = new SmppClient($transport);

// Activate binary hex-output of server interaction
//$smpp->debug = true;
//$transport->debug = true;

// Open the connection
print "Opening transport to " . $host . ":" . $port . ": ";
try {
	$transport->open();

} catch (Exception $e){

	print "Failed to open SMPP transport\n";
	api_error_raise("Failed to open " . $options["name"] . " SMPP RX transport");
	sleep(10);
	exit;
}

print "OK\n";

print "Binding receiver: ";
try {
	$smpp->bindReceiver($options["username"], $options["password"]);

} catch (Exception $e){

	print "Failed to bind SMPP transport\n";
	api_error_raise("Failed to bind " . $options["name"] . " SMPP RX transport");
	sleep(10);
	exit;
}

print "OK\n";

$timesincelastenquire = 0;

while(1){

	// Read SMS and output
	$sms = $smpp->readSMS();

	if(!empty($sms)){

		if(!empty($sms->err) || $sms->err === '0'){

			switch (trim($sms->stat)) {
				case "ACKED":
					$status = "SUBMITTED";
					break;
				case "DELIVRD":
					$status = "DELIVERED";
					break;
				case "EXPIRED":
					$status = "EXPIRED";
					break;
				case "DELETED":
					$status = "UNKNOWN";
					break;
				case "UNDELIV":
					$status = "UNDELIVERED";
					break;
				case "ACCEPTD":
					$status = "SUBMITTED";
					break;
				case "UNKNOWN":
					$status = "UNKNOWN";
					break;
				case "REJECTD":
					$status = "UNKNOWN";
					break;
				case "FAILED":
					$status = "UNDELIVERED";
					break;
				default:
					file_put_contents("/tmp/dr-" . api_misc_randombytes() . ".txt", serialize($sms));
					$status = "UNKNOWN";
					break;
			}

			if(!empty($timezone)){

				$date = new DateTime('now', new DateTimeZone($timezone));
				$offset = ($date->getOffset() * -1);

				$smsdate = $sms->doneDate + $offset;

			} else $smsdate = $sms->doneDate;

			if((time() - $smsdate) < 60) $supplierdate = time();
			else $supplierdate = $smsdate;

			print date("c") . ": MsgId=" . $sms->id . "; Status=" . $status . "; Err=" . $sms->err . "; Date=" . $supplierdate . "; DoneDate=" . $smsdate . "; Save=";

			$dr = array("supplier" => $options["supplier"], "supplieruid" => $sms->id, "status" => $status, "code" => $sms->err, "supplierdate" => $supplierdate);

			if($status == "SUBMITTED") print "OK\n";
			elseif(api_queue_add("smsdr", $dr)) print "OK\n";
			else print "NOK\n";

		} else {

            // Inbound SMS

			print date("c") . ": MsgId=" . $sms->id . "; From=" . $sms->source->value . "; To=" . $sms->destination->value . "; Save=";

			if(preg_match("/^614[0-9]{8}$/", $sms->source->value)) $from = "0" . substr($sms->source->value, 2);
			elseif(preg_match("/^64/", $sms->source->value)) $from = "0" . substr($sms->source->value, 2);
			else $from = $sms->source->value; // We have an alphanumeric sender

			if(preg_match("/^614[0-9]{8}$/", $sms->destination->value)) $to = "0" . substr($sms->destination->value, 2);

			$sms_account = api_sms_dids_checkexists($sms->destination->value);

			if(!is_numeric($sms_account)) $sms_account = api_sms_dids_checkexists($to);

			if(!is_numeric($sms_account)) api_misc_audit("SMSDID_ERROR", "Unidentified message recieved. Source: " . $sms->source->value . "; Destination=" . $sms->destination->value);
			else {

				if(isset($sms->udhi->concat)){ // This is a multi-part message. Store it and reassemble later

					if(api_sms_concat_receive($sms_account, $from, $to, $sms->udhi->concat->identifier, $sms->udhi->concat->part, $sms->udhi->concat->parts, $sms->message, $sms->source->value)) print "; Concat SMS; OK\n";

				} else if(api_sms_receive(time(), null, $sms_account, $from, $sms->message, $sms->source->value)) print "OK\n";
			}
		}
	}

	if((time() - $timesincelastenquire) > (SMPP_TIMEOUT/2)){

		print "Sending enquire_link: ";

		try{
			$smpp->enquireLink();

		} catch (Exception $e){

			print "Failed to send enquire_link on " . $options["name"] . " SMPP bind";
			api_error_raise("Failed to send enquire_link on " . $options["name"] . " SMPP bind");
			sleep(10);
			exit;
		}

		api_db_ping();
		$timesincelastenquire = time();
		print "OK\n";

		if(api_keystore_get("SETTINGS", 0, "DAEMON_RESTART") > $started) exit;
	}

}
// Close connection
$smpp->close();

?>
