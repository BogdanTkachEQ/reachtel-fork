<?php

function api_payments_verify_cardnumber($cardnumber) {

	if(empty($cardnumber)) return api_error_raise("Sorry, that is not a valid card number");

	// Strip any non-digits (useful for credit card numbers with spaces and hyphens)
	$number = preg_replace('/\D/', '', $cardnumber);

	// Set the string length and parity
	$number_length = strlen($number);
	$parity = $number_length % 2;

	// Loop through each digit and do the maths
	$total = 0;

	for ($i = 0; $i < $number_length; $i++) {

		$digit = $number[$i];

		// Multiply alternate digits by two
		if ($i % 2 == $parity) {

			$digit *= 2;

			// If the sum is two digits, add them together (in effect)
			if ($digit > 9) $digit -= 9;

		}

		// Total up the digits
		$total += $digit;
	}

	// If the total mod 10 equals 0, the number is valid
	return ($total % 10 == 0) ? true : false;


}

function api_payments_getcardtype($cardnumber){

	if(empty($cardnumber)) return api_error_raise("Sorry, that is not a valid card number");

	$cards = array(
		"visa" => "(4\d{12}(?:\d{3})?)",
		"amex" => "(3[47]\d{13})",
		"jcb" => "(35[2-8][89]\d\d\d{10})",
		"maestro" => "((?:5020|5038|6304|6579|6761)\d{12}(?:\d\d)?)",
		"solo" => "((?:6334|6767)\d{12}(?:\d\d)?\d?)",
		"mastercard" => "((2[2-7]|5[1-5])\d{14})",
		"switch" => "(?:(?:(?:4903|4905|4911|4936|6333|6759)\d{12})|(?:(?:564182|633110)\d{10})(\d\d)?\d?)");

	$names = array("Visa", "American Express", "JCB", "Maestro", "Solo", "Mastercard", "Switch");

	$matches = array();

	$pattern = "/^(?:".implode("|", $cards).")$/";

	$result = preg_match($pattern, $cardnumber, $matches);

	return ($result > 0) ? $names[sizeof($matches)-2] : false;


}

function api_payments_verify_expiry($expiry){

	if(empty($expiry)) return api_error_raise("Sorry, that is not a valid expiry");

	$expiry = preg_replace("/\D/", "", $expiry);

	if(strlen($expiry) == 3) $expiry = "0" . $expiry;

	if(!preg_match("/^(01|02|03|04|05|06|07|08|09|10|11|12)(11|12|13|14|15|16|17|18|19)$/", $expiry, $components)) return false;

	if(($components[2] < date("y")) OR (($components[1] < date("n")) AND ($components[2] == date("y")))) return false; // expired
	else return true;

}

function api_payments_verify_amount($amount){

	$amount = preg_replace("/[^0-9.]/", "", $amount);

	if($amount == 0) return false;

	if($amount > 10000) return false;

	return true;

}

function api_payments_verify_cvc($cvc, $cardnumber){

	if(empty($cvc)) return false;

	$cardtype = api_payments_getcardtype($cardnumber);

	if($cardtype == FALSE) return false;

	if($cardtype == "American Express") $digits = 4;
	else $digits = 3;

	$cvc = preg_replace("/\D/", "", $cvc);

	if(preg_match("/^[0-9]{" . $digits . "}$/", $cvc)) return true;
	else return false;

}

function api_payments_verify_name($name){

	if(preg_match("/^.{0,64}$/", $name)) return true;
	else return false;

}

function api_payments_log($transactionResponse = array()){

	$sql = "INSERT INTO `payments` (`gateway`, `process`, `username`, `reference`, `responsecode`, `response`) VALUES (?, ?, ?, ?, ?, ?)";
	$rs = api_db_query_write($sql, array($transactionResponse["gateway"], $transactionResponse["process"], $transactionResponse["username"], $transactionResponse["reference"], $transactionResponse["responsecode"], $transactionResponse["response"]));

	return true;

}

function api_payments_pantruncate(){

	// Truncates any 16 digit credit card numbers that were inserted into in the response_data table between 7 and 30 days ago

	$sql = "UPDATE `response_data` SET `value` = CONCAT(?, SUBSTRING(`value`, 13)) WHERE  `timestamp` < DATE_SUB(NOW(), INTERVAL ? DAY) AND  `timestamp` > DATE_SUB(NOW(), INTERVAL ? DAY) AND  `action` =  ? AND LENGTH(`value`) = ? AND `value` NOT LIKE ?";
	$rs = api_db_query_write($sql, array("************", 7, 30, "fullccnumber", 16, "************%"));

	if($rs) return api_db_affectedrows();
	else return false;
}

function api_payments_gateways_ematters_payment($transaction = array()){

	if(empty($transaction["username"]) OR empty($transaction["password"])) return api_error_raise("Sorry, we need authentication details for the request");
	elseif(!api_payments_verify_name($transaction["name"])) return api_error_raise("Sorry, that is not a valid name");
	elseif(!api_payments_verify_cardnumber($transaction["cardnumber"])) return api_error_raise("Sorry, that is not a valid card number");
	elseif(!api_payments_verify_expiry($transaction["expiry"])) return api_error_raise("Sorry, that is not a valid expiry date");
	elseif(!api_payments_verify_cvc($transaction["cvc"], $transaction["cardnumber"])) return api_error_raise("Sorry, that is not a valid CVC code");
	elseif(!api_payments_verify_amount($transaction["amount"])) return api_error_raise("Sorry, that is not a valid amount");
	elseif(empty($transaction["reference"])) $transaction["reference"] = api_misc_randombytes();

	$xml = new SimpleXMLElement("<ematters />");

	$xml->readers = $transaction["username"];
	$xml->password = $transaction["password"];
	$xml->Name = $transaction["name"];
	$xml->CreditCardHolderName = $transaction["name"];
	$xml->CreditCardNumber = $transaction["cardnumber"];
	$xml->CreditCardExpiryMonth = substr($transaction["expiry"], 0, 2);
	$xml->CreditCardExpiryYear = substr($transaction["expiry"], 2, 2);
	$xml->CVV = $transaction["cvc"];
	$xml->UID = $transaction["reference"];
	$xml->FinalPrice = $transaction["amount"];
	$xml->Action = "Process";

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, EMATTERS_XMLAPI_URL_PAYMENT);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml->asXML());
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

	$response = curl_exec($ch);

	$info = curl_getinfo($ch);

	if(($response === false) OR ($info["http_code"] != 200)) {
		return api_error_raise("Failed to process eMatters payment: " . curl_error($ch));
	}

	$xml_response = new SimpleXMLElement($response);

	$transactionResponse = array("gateway" => "ematters",
		"process" => "payment",
		"username" => $transaction["username"],
		"response" => serialize($response),
		"responsecode" => (string)$xml_response->emattersRcode,
		"reference" => (string)$xml_response->emattersMainID,
		"bankauthcode" => (string)$xml_response->emattersAuthCode);

	if(!empty($xml_response) AND ($xml_response->emattersRcode == "08")) $transactionResponse["success"] = true;
	else $transactionResponse["success"] = false;

	api_payments_log($transactionResponse);

	return $transactionResponse;

}

function api_payments_gateways_ematters_tokenise($transaction = array()){

	if(empty($transaction["username"])) return api_error_raise("Sorry, we need authentication details for the request");
	elseif(!api_payments_verify_name($transaction["name"])) return api_error_raise("Sorry, that is not a valid name");
	elseif(!api_payments_verify_cardnumber($transaction["cardnumber"])) return api_error_raise("Sorry, that is not a valid card number");
	elseif(!api_payments_verify_expiry($transaction["expiry"])) return api_error_raise("Sorry, that is not a valid expiry date");
	elseif(!api_payments_verify_cvc($transaction["cvc"], $transaction["cardnumber"])) return api_error_raise("Sorry, that is not a valid CVC code");
	elseif(empty($transaction["reference"])) $transaction["reference"] = api_misc_randombytes();

	$xml = new SimpleXMLElement("<ematters />");

	$xml->Action = "Add";
	$xml->UID = $transaction["reference"];
	$xml->Readers = $transaction["username"];
	$xml->CustomerName = $transaction["name"];
	$xml->CreditCardNumber = $transaction["cardnumber"];
	$xml->CreditCardExpiryMonth = substr($transaction["expiry"], 0, 2);
	$xml->CreditCardExpiryYear = substr($transaction["expiry"], 2, 2);
	$xml->CreditCardHolderName = $transaction["name"];
	$xml->CVV = $transaction["cvc"];

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, EMATTERS_XMLAPI_URL_TOKENISE);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml->asXML());
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

	$response = curl_exec($ch);

	$info = curl_getinfo($ch);

	if(($response === false) OR ($info["http_code"] != 200)) {
		return api_error_raise("Failed to process eMatters tokenisation: " . curl_error($ch));
	}

	$xml_response = new SimpleXMLElement($response);

	$transactionResponse = array("gateway" => "ematters",
		"process" => "tokenise",
		"username" => $transaction["username"],
		"response" => serialize($response),
		"responsecode" => (string)$xml_response->response,
		"reference" => $transaction["reference"]);

	if(!empty($xml_response) AND ($xml_response->response == "01")) $transactionResponse["success"] = true;
	else $transactionResponse["success"] = false;

	api_payments_log($transactionResponse);

	return $transactionResponse;

}

function api_payments_gateways_ezidebit_payment($transaction = array()){

	if(empty($transaction["username"])) return api_error_raise("Sorry, we need authentication details for the request");
	elseif(!api_payments_verify_name($transaction["name"])) return api_error_raise("Sorry, that is not a valid name");
	elseif(!api_payments_verify_cardnumber($transaction["cardnumber"])) return api_error_raise("Sorry, that is not a valid card number");
	elseif(!api_payments_verify_expiry($transaction["expiry"])) return api_error_raise("Sorry, that is not a valid expiry date");
	elseif(!api_payments_verify_cvc($transaction["cvc"], $transaction["cardnumber"])) return api_error_raise("Sorry, that is not a valid CVC code");
	elseif(!api_payments_verify_amount($transaction["amount"])) return api_error_raise("Sorry, that is not a valid amount");
	elseif(empty($transaction["reference"])) $transaction["reference"] = api_misc_randombytes();


	$parameters = array("DigitalKey" => $transaction["username"],
		"CreditCardNumber" => $transaction["cardnumber"],
		"CreditCardExpiryMonth" => substr($transaction["expiry"], 0, 2),
		"CreditCardExpiryYear" => "20" . substr($transaction["expiry"], 2, 2),
		"CreditCardCVV" => $transaction["cvc"],
		"NameOnCreditCard" => $transaction["name"],
		"PaymentAmountInCents" => $transaction["amount"] * 100,
		"CustomerName" => $transaction["name"],
		"PaymentReference" => $transaction["reference"]);

	try {

		$client = new SoapClient(EZIDEBIT_URL_PAYMENT);
		$response = $client->ProcessRealtimeCreditCardPayment($parameters);

	} catch (Exception $e) {
		return api_error_raise("Failed to process Ezidebit payment: " . $e->getMessage());
	}

	$transactionResponse = array("gateway" => "ezidebit",
		"process" => "payment",
		"username" => $transaction["username"],
		"response" => serialize($response),
		"responsecode" => $response->ProcessRealtimeCreditCardPaymentResult->Error);

	if(!empty($response->ProcessRealtimeCreditCardPaymentResult->Data)) {
		$transactionResponse["responsecode"] = (string)$response->ProcessRealtimeCreditCardPaymentResult->Data->PaymentResult;
		$transactionResponse["reference"] = (string)$response->ProcessRealtimeCreditCardPaymentResult->Data->ExchangePaymentID;
		$transactionResponse["bankauthcode"] = (string)$response->ProcessRealtimeCreditCardPaymentResult->Data->PaymentResultCode;
	}

	if(!empty($response->ProcessRealtimeCreditCardPaymentResult->Data->PaymentResult) AND ($response->ProcessRealtimeCreditCardPaymentResult->Data->PaymentResult == "A")) {

		$transactionResponse["success"] = true;

	} else $transactionResponse["success"] = false;

	api_payments_log($transactionResponse);

	return $transactionResponse;

}

function api_payments_gateways_ezidebit_tokenise($transaction = array()){

	if(empty($transaction["username"])) return api_error_raise("Sorry, we need authentication details for the request");
	elseif(!api_payments_verify_name($transaction["name"])) return api_error_raise("Sorry, that is not a valid name");
	elseif(!api_payments_verify_cardnumber($transaction["cardnumber"])) return api_error_raise("Sorry, that is not a valid card number");
	elseif(!api_payments_verify_expiry($transaction["expiry"])) return api_error_raise("Sorry, that is not a valid expiry date");
	elseif(!api_payments_verify_cvc($transaction["cvc"], $transaction["cardnumber"])) return api_error_raise("Sorry, that is not a valid CVC code");
	elseif(empty($transaction["reference"])) $transaction["reference"] = api_misc_randombytes();

	// The API requires a separate first and last name when we were only provided the full name
	if(strpos($transaction["name"], " ") !== false) {

		$position = strpos($transaction["name"], " ");
		$transaction["firstname"] = substr($transaction["name"], 0, $position);
		$transaction["lastname"] = substr($transaction["name"], $position + 1);

	} else {
		$transaction["firstname"] = $transaction["name"];
		$transaction["lastname"] = "";
	}

	$parameters = array("PublicKey" => $transaction["username"],
		"FirstName" => $transaction["firstname"],
		"LastName" => $transaction["lastname"],
		"CreditCardNumber" => $transaction["cardnumber"],
		"CreditCardExpiryMonth" => substr($transaction["expiry"], 0, 2),
		"CreditCardExpiryYear" => "20" . substr($transaction["expiry"], 2, 2),
		"ContractStartDate" => date("Y-m-d"),
		"CreditCardCCV" => $transaction["cvc"],
		"NameOnCreditCard" => $transaction["name"],
		"PaymentAmount" => 0,
		"PaymentReference" => $transaction["reference"]);

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, EZIDEBIT_URL_TOKENISE . "?" . http_build_query($parameters));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

	$response = curl_exec($ch);

	$info = curl_getinfo($ch);

	if(($response === false) OR ($info["http_code"] != 200)) {
		return api_error_raise("Failed to process Ezidebit tokenisation: " . curl_error($ch));
	}

	$json_response = json_decode($response);

	$transactionResponse = array("gateway" => "ezidebit",
		"process" => "tokenise",
		"username" => $transaction["username"],
		"response" => $response);

	if(!empty($json_response) AND !empty($json_response->Data->CustomerRef)) {

		$transactionResponse["success"] = true;
		$transactionResponse["responsecode"] = $json_response->Error;
		$transactionResponse["reference"] = $json_response->Data->CustomerRef;

	} else {
		$transactionResponse["success"] = false;
		$transactionResponse["responsecode"] = $json_response->Error;
		$transactionResponse["reference"] = false;
	}

	api_payments_log($transactionResponse);

	return $transactionResponse;

}