<?php

function api_hlr_send_supplier_9($msisdn){

	// Tyntec

	global $statsd;

	$supplier = 9;

	$tags = api_hlr_supplier_tags_get($supplier);

	$starttime = microtime(true);

	try {

		$client = new SoapClient($tags["host"], array('login' => $tags["username"], 'password' => $tags["password"], 'trace' => 1));

	} catch (Exception $e){

		api_misc_audit("TYNTEC_ERROR", "Exception: MSISDN:" . $msisdn . ". Message: " . $e->getMessage());

		return false;
	}

	$request = new stdClass();
	$request->AllNetworkQuery = new stdClass();
	$request->AllNetworkQuery->Destination = new stdClass();
	$request->AllNetworkQuery->Destination->Number = $msisdn;

	try {

		$response = $client->CheckRequest($request);


	} catch (Exception $e){

		api_misc_audit("TYNTEC_ERROR", "Exception: MSISDN:" . $msisdn . ". Message: " . $e->getMessage());

		return false;

	}

	$result = array("msisdn" => $msisdn, "status" => "INDETERMINATE", "active" => false, "hlrcode" => 997, "response" => serialize($response), "carriercode" => null, "supplierid" => $response->MessageRef);

	switch($response->CheckState->value) {

		case "Success":

			switch($response->AllNetworkInfo->SS7ErrorCode) {

				case "1b":

					$result["status"] = "CONNECTED";
            		$result["active"] = true;
					$result["hlrcode"] = 6;
					$result["carriercode"] = $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MCC . $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MNC;
					break;

				case "b":

					$result["status"] = "DISCONNECTED";
            		$result["active"] = false;
					$result["hlrcode"] = 11;
					break;

				case "c001":

					$result["status"] = "CONNECTED";
					$result["active"] = true;
					$result["hlrcode"] = 0;
					$result["carriercode"] = $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MCC . $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MNC;
					break;

				case "c002":

					$result["status"] = "CONNECTED";
					$result["active"] = true;
					$result["hlrcode"] = 6;
					$result["carriercode"] = $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MCC . $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MNC;
					break;

				case "c003":

					$result["status"] = "CONNECTED";
					$result["active"] = true;
					$result["hlrcode"] = 6;
					$result["carriercode"] = $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MCC . $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MNC;
					break;

				case "c005":

					$result["status"] = "CONNECTED";
					$result["active"] = true;
					$result["hlrcode"] = 6;
					$result["carriercode"] = $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MCC . $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MNC;
					break;

				case "c006":

					$result["status"] = "CONNECTED";
					$result["active"] = true;
					$result["hlrcode"] = 6;
					$result["carriercode"] = $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MCC . $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MNC;
					break;

				case "c007":

					$result["status"] = "CONNECTED";
					$result["active"] = true;
					$result["hlrcode"] = 6;
					$result["carriercode"] = $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MCC . $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MNC;
					break;

				case "0":

					if($response->AllNetworkInfo->ServicingNetwork->NetworkInfo->MNC == "Unknown"){

						api_misc_audit("TYNTEC_ERROR", "POSSIBLE_CONNECTED=" . $msisdn);

						$result["status"] = "DISCONNECTED";
						$result["active"] = false;
						$result["hlrcode"] = 997;
						break;

					} else {

						$result["status"] = "CONNECTED";
						$result["active"] = true;
						$result["hlrcode"] = 0;
						$result["carriercode"] = $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MCC . $response->AllNetworkInfo->SubscriptionNetwork->HLRInformation->NetworkInfo->MNC;
						break;
					}

				default:

				api_misc_audit("TYNTEC_ERROR", "UNHANDLED SUCCESS RESPONSE: Code=" . $response->AllNetworkInfo->SS7ErrorCode . "; MSISDN:" . $msisdn);
				return false;
			}

			break;

		case "Failure":

			switch($response->NetworkErrorCode->value) {

				case "1":

					// Call barred
					if(isset($response->NetworkErrorCode->NetworkErrorDescription->hexErrorCode) AND ($response->NetworkErrorCode->NetworkErrorDescription->hexErrorCode == "d")) {

						$result["status"] = "CONNECTED";
						$result["active"] = true;
						$result["hlrcode"] = 13;

					} else {

						$result["status"] = "DISCONNECTED";
						$result["active"] = false;
						$result["hlrcode"] = 996;
					}

					break;

				case "2":

					if(isset($response->NetworkErrorCode->NetworkErrorDescription->hexErrorCode) AND ($response->NetworkErrorCode->NetworkErrorDescription->hexErrorCode == "d002")) {

						api_misc_audit("TYNTEC_ERROR", "FAILURE RESPONSE: Code=" . $response->NetworkErrorCode->NetworkErrorDescription->hexErrorCode . "; MSISDN:" . $msisdn);
						return false;

					} else {

						$result["status"] = "DISCONNECTED";
						$result["active"] = false;
						$result["hlrcode"] = 1;
						break;

					}

				default:

				api_misc_audit("TYNTEC_ERROR", "UNHANDLED FAILURE RESPONSE: Code=" . $response->NetworkErrorCode->value . "; MSISDN:" . $msisdn);
				return false;

			}

			break;

		default:

			api_misc_audit("TYNTEC_ERROR", "UNHANDLED RESPONSE: Code=" . $response->CheckState->value . "; MSISDN:" . $msisdn);
			return false;

			break;

	}

	$statsd->timing("morpheus.sms.hlrlookup.latency." . $tags["shortname"], (microtime(true) - $starttime)*1000);

	if(in_array($result["status"], array("CONNECTED", "DISCONNECTED"))) return $result;
	else return false;

}