<?php

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(102);

if(!isset($tags['response_filter'])) {
    print "Please define a 'response_filter' tag (comma separated column names)\n";
    exit;
}


$start = date("Y-m-d 00:00:00", strtotime("yesterday"));
$finish = date("Y-m-d 23:59:59", strtotime("yesterday"));

$sql = "SELECT * FROM `response_data` WHERE `timestamp` >= ? AND `timestamp` <= ? AND `action` = ?";
$rs = api_db_query_read($sql, array($start, $finish, "JOINUNION"));

if($rs->RecordCount() == 0) {
        print "No records to return\n";
        exit;
}

$data_all = $mergedata_all = $responsedata_all = $mergedata = $responsedata = [];
$regexp_filter = "/^(Q[0-9a-c]+|" . implode('|', array_map('trim', array_filter(explode(',', $tags['response_filter'] )))). ")$/i";

while($row = $rs->FetchRow()){
    $union = $row['value'];
    $d = [
        "response_data" => api_data_responses_getall($row["targetid"], $row["eventid"]),
        "merge_data" => api_data_merge_get_all($row["campaignid"], $row["targetkey"]),
        "target" => api_targets_getinfo($row["targetid"]),
    ];

    $data[$union][$row["eventid"]] = $d;
    $data_all[$row["eventid"]] = $d;

    foreach($data[$union][$row["eventid"]]["response_data"] as $key => $value) {
        if (!preg_match($regexp_filter, $key)) {
            if (!isset($responsedata[$union]) || !in_array($key, $responsedata[$union])) {
                $responsedata[$union][] = $key;
            }
        }
    }

    foreach($data[$union][$row["eventid"]]["merge_data"] as $key => $value) {
        if(!isset($mergedata[$union]) || !in_array($key, $mergedata[$union])) {
            $mergedata[$union][] = $key;
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
foreach($data as $union => $union_data) {
    print " > Union {$union} file\n";

    $content = "targetkey,destination,";

    foreach($mergedata[$union] as $elements) {
        $content .= "{$elements},";
    }
    foreach($responsedata[$union] as $elements) {
        $content .= "{$elements},";
    }

    $content .= "\n";

    foreach($union_data as $eventid => $target){

        $content .= $target["target"]["targetkey"] . ",";
        $content .= $target["target"]["destination"] . ",";

        foreach($mergedata[$union] as $elements) {
            $d = ((isset($target["merge_data"][$elements])) ? $target["merge_data"][$elements] : "") . ",";
            $content .= $d;
        }

        foreach($responsedata[$union] as $elements) {
            $d = ((isset($target["response_data"][$elements])) ? $target["response_data"][$elements] : "") . ",";
            $content .= $d;
        }

        $content .= "\n";
    }

    $union = strtolower($union);
    $attachments[] = ["content" => $content, "filename" => "ReachTEL-union-results-{$union}-" . date("Y-m-d", strtotime("yesterday")) . ".csv"];
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
$attachments[] = ["content" => $content, "filename" => "ReachTEL-union-results-ALL-DATA-" . date("Y-m-d", strtotime("yesterday")) . ".csv"];




$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";
$email["to"]          = $tags["reporting-destination"];
$email["subject"]     = "[ReachTEL] Union results export for " . date("d/m/Y", strtotime("yesterday"));
$email["textcontent"] = "Hello,\n\nPlease find attached the union results export.";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the union results export.";

$email["attachments"] = $attachments;

api_email_template($email);

print "Returned " . count($data) . " rows.\n";
