<?php

function api_accounting_xero_request($method, $parameters = null){

	require_once(__DIR__ . "/lib/Xero/xero.php");

	$xero = new Xero(XERO_API_CONSUMERKEY, XERO_API_CONSUMERSECRET, XERO_API_PUBLICCERT, XERO_API_PRIVATEKEY);

	$request = $xero->$method($parameters);

	return $request;
}

function api_accounting_xero_contact($id){

	if(!is_numeric($id)) return api_error_raise("Sorry, that is not a valid group");

	$settings = api_groups_setting_getall($id);

	$contact = new SimpleXMLElement('<Contact/>');

	if(!empty($settings["xeroref"])) $contact->ContactID = $settings["xeroref"];

	$lines = explode("\n", trim($settings["customeraddress"]));

	if(is_array($lines) AND count($lines)) {
		$addresses = $contact->addChild("Addresses");
		$address = $addresses->addChild("Address");

		$address->AddressType = "STREET";

		for($i = 0; $i < 4; $i++){
			$item = "AddressLine" . ($i+1);
			if(isset($lines[$i])) $address->$item = trim($lines[$i]);
			else $address->$item = "";
		}
	}

	if(!empty($settings["abn"])) $contact->TaxNumber = $settings["abn"];

	$contactcount = 0;

	$contactPersons = $contact->addChild("ContactPersons");

	if(!empty($settings["invoiceemailto"])){

		foreach(imap_rfc822_parse_adrlist($settings["invoiceemailto"], "reachtel.com.au") as $value){
			if ($contactcount == 5) break;

			if ($contactcount == 0) $contact->EmailAddress = $value->mailbox . "@" . $value->host;
			else {

				$contactPerson = $contactPersons->addChild("ContactPerson");
				$contactPerson->EmailAddress = $value->mailbox . "@" . $value->host;
				$contactPerson->IncludeInEmails = "true";

			}

			$contactcount++;
		}
	}

	if(!empty($settings["invoiceemailcc"])){

		foreach(imap_rfc822_parse_adrlist($settings["invoiceemailcc"], "reachtel.com.au") as $value){
			if ($contactcount == 5) break;

			if ($contactcount == 0) $contact->EmailAddress = $value->mailbox . "@" . $value->host;
			else {

				$contactPerson = $contactPersons->addChild("ContactPerson");
				$contactPerson->EmailAddress = $value->mailbox . "@" . $value->host;
				$contactPerson->IncludeInEmails = "true";

			}

			$contactcount++;
		}
	}

	$contact->Name = $settings['customername'];
	$contact->ContactNumber = $id;

	return $contact;

}

function api_accounting_xero_invoice_add($invoicedata, $pdf = null){

	if(!is_array($invoicedata)) return api_error_raise("Sorry, that is not a valid invoice");

	if(empty($invoicedata["items"]) OR (count($invoicedata["items"]) == 0)) return true; //return api_error_raise("Sorry, I can't insert an empty invoice");

	$invoice = new SimpleXMLElement('<Invoice/>');

	$invoice->Type = "ACCREC";
	$invoice->Status = "DRAFT";
	$invoice->Date = date("Y-m-d", strtotime($invoicedata["date"]));
	$invoice->DueDate = date("Y-m-d", $invoicedata["duedate"]);
	$invoice->InvoiceNumber = $invoicedata["invoicenumber"];

	api_misc_xml_adopt($invoice, api_accounting_xero_contact($invoicedata["groupid"]));

	$lineitems = $invoice->addChild("LineItems");

	/*
		Xero calculated tax on the line item and we calculate it on the sub total. We need to account for the difference
		so keep track of the difference and then add in an adjustment
	*/

	if($invoicedata["gst"] == 0) $taxrate = 0;
	else $taxrate = 10;

	$invoicedata["xerotax"] = 0;
	if($taxrate) foreach($invoicedata["items"] as $line => $item) $invoicedata["xerotax"] += round(($item["cost"] / 10), 2);

	$xerodiscrepancy = round($invoicedata["xerotax"] - $invoicedata["gst"], 2);
	$xerodiscrepancyclaimed = false;

	foreach($invoicedata["items"] as $line => $item){

		$lineitem = $lineitems->addChild("LineItem");

		$lineitem->Description = $item["name"];
		$lineitem->Quantity = 1;
		$lineitem->UnitAmount = $item["cost"];
		$lineitem->AccountCode = api_accounting_xero_accountmap($item["type"]);

		if(!$taxrate) {

			$lineitem->TaxType = "EXEMPTEXPORT"; // If the tax rate is 0, set the tax type to "GST Free Exports"
			$lineitem->TaxAmount = 0;

		} else if(($xerodiscrepancy !== 0) AND (round(($item["cost"] / 10), 2) > 0)) { // Find invoice lines to apply the tax adjustment to until there is no discrepancy remaining
			if((round(($item["cost"] / 10), 2) - $xerodiscrepancy) >= 0) {
				$lineitem->TaxAmount = round(($item["cost"] / 10), 2) - $xerodiscrepancy;
				$xerodiscrepancy = 0;
			} else {
				$lineitem->TaxAmount = 0;
				$xerodiscrepancy = $xerodiscrepancy - round(($item["cost"] / 10), 2);
			}
		}

	}

	$response = api_accounting_xero_request("invoices", $invoice);

	if(!empty($response["Status"]) AND ($response["Status"] == "OK") AND (!empty($response["Invoices"]["Invoice"]["InvoiceID"]))) {
		api_groups_setting_set($invoicedata["groupid"], "xeroref", $response["Invoices"]["Invoice"]["Contact"]["ContactID"]);

		if(!empty($pdf)) {
			api_accounting_xero_attachment_upload($response["Invoices"]["Invoice"]["InvoiceID"], array("endpoint" => "Invoices", "content" => $pdf, "name" => $invoicedata["pdffilename"]));
		}

		return $response["Invoices"]["Invoice"]["InvoiceID"];
	} else {
		var_dump($response["Elements"]["DataContractBase"]["ValidationErrors"]);
		return false;
	}
}

function api_accounting_xero_accountmap($category){

	// This function takes a category name and maps it to a numeric Xero account number

	if(empty($category)) return "200";

	switch ($category) {

		case "phone":
		return "211";
		break;
		case "sms":
		return "221";
		break;
		case "email":
		return "231";
		break;
		case "wash":
		return "241";
		break;
		case "voice usage":
		return "211";
		break;
		case "voice service":
		return "212";
		break;
		case "phone service":
		return "212";
		break;
		case "sms service":
		return "222";
		break;
		case "sms usage":
		return "222";
		break;
		case "email service":
		return "232";
		break;
		case "portal service":
		return "251";
		break;
		case "portal set up":
		return "252";
		break;
		case "data validation":
		return "241";
		break;
		default:
		return "200";
		break;
	}

}

function api_accounting_xero_attachment_upload($id, $file){

	if(empty($file["name"])) return api_error_raise("Sorry, that is not a valid file name");

	if(empty($file["content"])) return api_error_raise("Sorry, that is not valid file contents");

	$endpoints = array("Invoices");

	if(!in_array($file["endpoint"], $endpoints)) return api_error_raise("Sorry, that is not valid Xero attachment endpoint");

	$method = "attachments";

	$parameters = array("endpoint" => $file["endpoint"], "guid" => $id, "filename" => $file["name"], "content" => $file["content"]);

	$response = api_accounting_xero_request($method, $parameters);

	if(!empty($response) AND !empty($response["Status"]) AND ($response["Status"] == "OK") AND !empty($response["Id"])) return $response["Id"];
	else api_misc_audit("XERO_UPLOAD_ERROR", serialize($response));

	return api_error_raise("Error uploading attachment to Xero");

}

function api_accounting_xero_attachment_download($id){

}

?>
