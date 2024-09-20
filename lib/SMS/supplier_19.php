<?php

function api_sms_send_supplier_19($from, $to, $content, $eventid = null, $options = array()) {
	global $statsd;

	$supplier = 19;

	$starttime = microtime(true);

	$job = array('to' => $to, 'from' => $from, 'content' => $content, 'eventid' => $eventid, 'generated' => microtime(true));

	if (isset($options['deliveryreceipt'])) {
		$job['deliveryreceipt'] = $options['deliveryreceipt'];
	} else {
		$job['deliveryreceipt'] = true;
	}

	if (isset($options['expiry']) && is_numeric($options['expiry'])) {
		$job['expiry'] = $options['expiry'];
	}

	$job = serialize($job);

	$gearmanClient = new GearmanClient();

	$servers = explode(',', QUEUE_GEARMAN_QUEUESERVER_WORKERS);

	shuffle($servers);

	foreach ($servers as $server) {
		$gearmanClient->addServer($server, 4730);
	}

	$gearmanClient->setTimeout(120000);

	$result = $gearmanClient->do('smpp-' . $supplier, $job);

	if ($result === false) {
		$statsd->increment('morpheus.sms.send.clxtelstraqld.errors.smpp');
		return api_error_raise('SMS send failed - CLX-Telstra SMPP QLD');
	} else {
		$result = unserialize($result);
	}

	$statsd->timing('morpheus.sms.send.clxtelstraqld', (microtime(true) - $starttime) * 1000);

	return $result;
}
