#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(121);

if (!isset($tags["reporting-destination"])) {
	echo "No 'reporting-destination' tag is set.\n";
}

$nb = 0;
$content = '';
foreach (api_dialplans_listall() as $id => $name) {
	$o = api_dialplans_object("[{$name}]\n" . api_dialplans_get($id));
	if (isset($o['errors']) && $o['errors']) {
		$nb += count($o['errors']);
		$content .= "\n";
		$content .= sprintf(
			'<a href="https://morpheus.reachtel.com.au/admin_listdialplan.php?name=%s">%s</a> (id#%s):',
			$name,
			$name,
			$id
		);
		$content .= "\n > " . implode("\n > ", $o['errors']) . "\n";
	}
}

echo "Found {$nb} error(s)\n";
if ($content) {
	if (isset($tags["reporting-destination"])) {
		$email = [];
		$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
		$email["to"]      = $tags["reporting-destination"];
		$email["subject"] = "[ReachTEL] {$nb} Dial Plan Syntax Error(s) - " . date('Y-m-d');
		$email["htmlcontent"] = "Hey! We found {$nb} syntax error(s)!<br/>" . nl2br($content);
		$email["textcontent"] = strip_tags($email["htmlcontent"]);
		if (api_email_template($email)) {
			echo "Email sent to {$tags["reporting-destination"]}\n";
		} else {
			echo "ERROR: Failed to send email to {$tags["reporting-destination"]}\n";
		}
	} else {
		echo strip_tags($content);
	}
}
