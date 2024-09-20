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

while (1) {
	// Read SMS and output
	$sms = $smpp->readSMS();
	if (!empty($sms)) {
		// Check if it looks like a delivery receipt in a message
		// SmppDeliveryReceipt::parseDeliveryReceipt does a stronger check with regex later
		$is_delivery_receipt = $sms instanceof SmppDeliveryReceipt;
		if (!$is_delivery_receipt && substr($sms->message, 0, 3) === 'id:') {
			// create new SmppDeliveryReceipt object, and parse delivery receipt
			try {
				$smsdr = new SmppDeliveryReceipt(
					$sms->id,
					$sms->status,
					$sms->sequence,
					$sms->body,
					$sms->service_type,
					$sms->source,
					$sms->destination,
					$sms->esmClass,
					$sms->protocolId,
					$sms->priorityFlag,
					$sms->registeredDelivery,
					$sms->dataCoding,
					$sms->message,
					$sms->tags,
					$sms->scheduleDeliveryTime,
					$sms->validityPeriod,
					$sms->smDefaultMsgId,
					$sms->replaceIfPresentFlag,
					$sms->udhi
				);
				$smsdr->parseDeliveryReceipt();

				// replace the $sms object with our new delivery receipt
				$sms = $smsdr;
				$is_delivery_receipt = true;
				api_misc_audit("SMSDR_CONTENT_PARSE", "Parsed delivery receipt from content. Id: " . $sms->id . "; Message: " . $sms->message);
			} catch (InvalidArgumentException $e) {
				// Do nothing, it wasn't really a delivery receipt
				// Leave $sms alone and process as usual
				api_misc_audit("SMSDR_CONTENT_PARSE", "Could not parse delivery receipt from content. Id: " . $sms->id . "; Message: " . $sms->message);
			}
		}

		if ($is_delivery_receipt) {
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

			if (!empty($timezone)) {
				$date = new DateTime('now', new DateTimeZone($timezone));
				$offset = ($date->getOffset() * -1);

				$smsdate = $sms->doneDate + $offset;
			} else {
				$smsdate = $sms->doneDate;
			}

			if ((time() - $smsdate) < 60) {
				$supplierdate = time();
			} else {
				$supplierdate = $smsdate;
			}

			print date("c") . ": MsgId=" . $sms->id . "; Status=" . $status . "; Err=" . $sms->err . "; Date=" . $supplierdate . "; DoneDate=" . $smsdate . "; Save=";

			if ($status == "SUBMITTED") {
				print "OK\n";
			} else {
				$deliveryReceipt = new \Models\SmsDeliveryReceipt();
				$deliveryReceipt
					->setSmsId($sms->id)
					->setSupplierId($options["supplier"])
					->setStatus(\Services\Utils\Sms\GenericSmsReceiptStatus::byValue($status))
					->setErrorCode($sms->err)
					->setStatusUpdateDateTime((new DateTime())->setTimestamp($supplierdate));

				$processor = new \Services\Sms\SmsReceiptProcessor();
				if ($processor->saveReceipt($deliveryReceipt)) {
					print "OK\n";
				} else {
					print "NOK\n";
				}
			}
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
				$message = $sms->message;
				try {
					$pci_validator = new Services\PCI\PCIValidator();
					if ($match = $pci_validator->matchAllPANData($message)) {
						foreach ($match as $pci) {
							$message = str_replace(
								$pci,
								$pci_validator->maskPANData($pci),
								$message
							);
						}
						api_error_audit("PCI_SMS_RECEIVE", "Message contains PCI data from SMSDID: {$sms_account}");
					}
				} catch (Exception $e) {
					// PCIValidator should not throw exceptions, this being defensive
					api_error_audit("PCI_SMS_RECEIVE", "CRITICAL ERROR: " . $e->getMessage());
				}

				if(isset($sms->udhi->concat)){ // This is a multi-part message. Store it and reassemble later

					if(api_sms_concat_receive($sms_account, $from, $to, $sms->udhi->concat->identifier, $sms->udhi->concat->part, $sms->udhi->concat->parts, $message, $sms->source->value)) print "; Concat SMS; OK\n";

				} else if(api_sms_receive(time(), null, $sms_account, $from, $message, $sms->source->value)) print "OK\n";
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
