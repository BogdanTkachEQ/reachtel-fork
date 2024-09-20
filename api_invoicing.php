<?php

// This function is retained to look up old invoices generated pre-selcomm
function api_invoicing_lookup($invoicenumber){

	$invoicenumber = preg_replace("/[^0-9\-]/", "", filter_var(strip_tags($invoicenumber), FILTER_SANITIZE_NUMBER_INT));

	if($invoicenumber == "") return api_error_raise("Sorry, that is not a valid invoice number");

	if(file_exists(READ_LOCATION . INVOICES_LOCATION . "/" . $invoicenumber . ".serialized")){

		$invoice = file_get_contents(READ_LOCATION . INVOICES_LOCATION . "/" . $invoicenumber . ".serialized");

		$invoice = unserialize($invoice);

		if(!isset($invoice["groupid"]) AND preg_match("/^([0-9]+)\-([0-9]+)\-([0-9]+)$/", $invoicenumber, $matches)) {

			$invoice["groupid"] = $matches[1];

		}

		return $invoice;

	} else return api_error_raise("Sorry, couldn't find that invoice number");

}
