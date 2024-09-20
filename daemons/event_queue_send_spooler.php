<?php

require_once('Morpheus/api.php');

$started = time();

while(1) {

	$jobcount =  api_queue_getjobcount();

	if($jobcount > 0) {

		do{
			$result = api_queue_getjob();

		} while($result);
	}

	if(api_keystore_get("SETTINGS", 0, "DAEMON_RESTART") > $started) exit;

	usleep(500000);
}

?>
