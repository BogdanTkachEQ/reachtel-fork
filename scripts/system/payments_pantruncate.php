<?php

// This script should run once per week to truncate any credit card values stored in the database

require_once("Morpheus/api.php");

$result = api_payments_pantruncate();

if(is_numeric($result)) api_misc_audit("PAYMENTS_PANTRUNCATE", "Process complete. Rows affected=" . $result);
else api_misc_audit("PAYMENTS_PANTRUNCATE", "Process failed.");