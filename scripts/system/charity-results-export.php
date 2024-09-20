<?php

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(70);

if(!isset($tags['response_filter'])) {
	print "Please define a 'response_filter' tag (comma separated column names)\n";
	exit;
}
foreach(['sftp-out-hostname', 'sftp-out-username', 'sftp-out-password', 'sftp-out-path', 'sftp-failure-notification'] as $tagname) {
	if(!isset($tags[$tagname])) {
		print "Please define a '{$tagname}' tag\n";
		exit;
	}
}

$start = date("Y-m-d 00:00:00", strtotime("yesterday"));
$finish = date("Y-m-d 23:59:59", strtotime("yesterday"));

$sql = "SELECT * FROM `response_data` WHERE `timestamp` >= ? AND `timestamp` <= ? AND `action` = ?";
$rs = api_db_query_read($sql, array($start, $finish, "CHARITY"));

if($rs->RecordCount() == 0) {
        print "No records to return\n";
        exit;
}

$data_all = $mergedata_all = $responsedata_all = $mergedata = $responsedata = [];
$regexp_filter = "/^(Q[0-9a-c]+|" . implode('|', array_map('trim', array_filter(explode(',', $tags['response_filter'] )))). ")$/i";

while($row = $rs->FetchRow()){
    $charity = $row['value'];
    $d = [
        "response_data" => api_data_responses_getall($row["targetid"], $row["eventid"]),
        "merge_data" => api_data_merge_get_all($row["campaignid"], $row["targetkey"]),
        "target" => api_targets_getinfo($row["targetid"]),
    ];

    $data[$charity][$row["eventid"]] = $d;
    $data_all[$row["eventid"]] = $d;

    foreach($data[$charity][$row["eventid"]]["response_data"] as $key => $value) {
        if (!preg_match($regexp_filter, $key)) {
            if (!isset($responsedata[$charity]) || !in_array($key, $responsedata[$charity])) {
                $responsedata[$charity][] = $key;
            }
        }
    }

    foreach($data[$charity][$row["eventid"]]["merge_data"] as $key => $value) {
        if(!isset($mergedata[$charity]) || !in_array($key, $mergedata[$charity])) {
            $mergedata[$charity][] = $key;
        }
    }

    foreach($data_all[$row["eventid"]]["response_data"] as $key => $value) {
        if (!preg_match($regexp_filter, $key)) {
            if(!in_array($key, $responsedata_all)) {
                $responsedata_all[] = $key;
            }
        }
    }

    foreach($data_all[$row["eventid"]]["merge_data"] as $key => $value) {
        if(!in_array($key, $mergedata_all)) {
            $mergedata_all[] = $key;
        }
    }
}

print "Preparing report...\n";
$attachments = [];
foreach($data as $charity => $charity_data) {
    print " > Charity {$charity} file\n";

    $content = "targetkey,destination,";

    foreach($mergedata[$charity] as $elements) {
        $content .= "{$elements},";
    }
    foreach($responsedata[$charity] as $elements) {
        $content .= "{$elements},";
    }

    $content .= "\n";

    foreach($charity_data as $eventid => $target){

        $content .= $target["target"]["targetkey"] . ",";
        $content .= $target["target"]["destination"] . ",";

        foreach($mergedata[$charity] as $elements) {
            $d = ((isset($target["merge_data"][$elements])) ? $target["merge_data"][$elements] : "") . ",";
            $content .= $d;
        }

        foreach($responsedata[$charity] as $elements) {
            $d = ((isset($target["response_data"][$elements])) ? $target["response_data"][$elements] : "") . ",";
            $content .= $d;
        }

        $content .= "\n";
    }

    $charity = strtolower($charity);
    $attachments[] = ["content" => $content, "filename" => "ReachTEL-charity-results-{$charity}-" . date("Y-m-d", strtotime("yesterday")) . ".csv"];
}

print "Preparing report with all data...\n";
$content = "targetkey,destination,";

foreach($mergedata_all as $elements) $content .= $elements . ",";
foreach($responsedata_all as $elements) $content .= $elements . ",";

$content .= "\n";

foreach($data_all as $eventid => $target){

    $content .= $target["target"]["targetkey"] . ",";
    $content .= $target["target"]["destination"] . ",";

    foreach($mergedata_all as $elements) {
        $content .= ((isset($target["merge_data"][$elements])) ? $target["merge_data"][$elements] : "") . ",";
    }

    foreach($responsedata_all as $elements) {
        $content .= ((isset($target["response_data"][$elements])) ? $target["response_data"][$elements] : "") . ",";
    }


    $content .= "\n";
}
$attachments[] = ["content" => $content, "filename" => "ReachTEL-charity-results-ALL-DATA-" . date("Y-m-d", strtotime("yesterday")) . ".csv"];

foreach($attachments as $attachment) {
	$filename = $attachment["filename"];
	print "Uploading file: {$filename} ... ";

	if(!($tmpfname = tempnam("/tmp", $filename))) {
		print "ERROR: Failed to create temp file\n";
		continue;
	}

	if(!(file_put_contents($tmpfname, $attachment["content"]))) {
		print "ERROR: Failed to add content to temp file {$tmpfname}\n";
		continue;
	}

	$options = array(
		"hostname" => $tags["sftp-out-hostname"],
		"username" => $tags["sftp-out-username"],
		"password" => $tags["sftp-out-password"],
		"localfile" => $tmpfname,
		"remotefile" => $tags["sftp-out-path"].$filename
	);

	if (!api_misc_sftp_put($options)) {
		$email["to"] = $tags["sftp-failure-notification"];
		$email["cc"] = "ReachTEL Support <support@ReachTEL.com.au>";
		$email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";
		$email["subject"] = "[ReachTEL] SFTP file upload error - Charity - {$filename}";
		$email["textcontent"] = "Hello,\n\nThe following file could not be uploaded:\n\n{$filename}\n";
		$email["htmlcontent"] = nl2br($email["textcontent"]);
		api_email_template($email);

		print "ERROR: SFTP upload failed!\n";
		continue;
	}

	print "OK\n";
}
