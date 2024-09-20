#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$timezone = "Australia/Canberra";
date_default_timezone_set($timezone);

$tags = api_cron_tags_get(20);

$date = date('Ymd', strtotime(isset($tags['date']) ? $tags['date'] : 'yesterday'));

print 'Searching for campaigns with PROC-DATE: ' . $date . "\n";

print "Generating content....\n";
$content = "UNIQUEID,DESTINATION,SENT,STATUS,StatusCode,DELIVERED,UNDELIVERED,UNKNOWN,DUPLICATE,arrears,COST,MessageReason\n";

$campaigns = array();

$content .= generate_content("ActewAGL-SMS-", $campaigns, $date, isset($tags['date']));
$content .= generate_content("Iconwater-SMS-",$campaigns, $date, isset($tags['date']));

if(empty($campaigns)) {
    print "No campaigns found.";
    exit;
}

print "Content generated...\n";

$tempfname = tempnam("/tmp", "actewagl-sms");

if(!file_put_contents($tempfname, $content)) die("Failed to write file");

$filename = "ActewAGL-SMS-cumulative-return-" . $date . ".csv";

$options = array("hostname"  => $tags["sftp-hostname"],
    "username"  => $tags["sftp-username"],
    "password"  => $tags["sftp-password"],
    "localfile" => $tempfname,
    "remotefile" => $tags["sftp-path"] . $filename);

print "Sending file to sftp...\n";
$result = api_misc_sftp_put_safe($options);

unlink($tempfname);

if(!$result) {

    print "Failed to upload to SFTP\n";
    exit;

}

foreach($campaigns as $campaignid) {
    //When the date is passed, it should be for adhoc run so continue if the return timestamp is not empty.
    if (isset($tags['date']) && api_campaigns_tags_get('RETURNTIMESTAMP') !== false) {
        continue;
    }

    api_campaigns_tags_set($campaignid, array("RETURNTIMESTAMP" => time()));
}

print "Report generation completed...\n";

// TODO: Techdebt: It should be using the new api_csv library. Not using it now because of tight deadline.
function generate_content($search_name, &$campaigns, $proc_date, $skip_campaign_check = false) {
    $content = '';

    foreach(api_campaigns_list_all(true, null, 15, array("search" => $search_name)) as $campaignid => $name){

        $settings = api_campaigns_setting_getall($campaignid);
        $tags = api_campaigns_tags_get($campaignid);

        //The campaign should be ignored if proc-date is not equal to the date when the report is run or unless explicitely
        // requested ignore the campaign if it is not disabled or if it does not have a RETURNTIMESTAMP tag.
        if (
            empty($tags["PROC-DATE"]) ||
            $tags["PROC-DATE"] != $proc_date || (
                !$skip_campaign_check && (
                    !empty($tags["RETURNTIMESTAMP"]) ||
                    $settings["status"] != "DISABLED"
                )
            )
        ) {
            continue;
        }

        print "Generating report for campaign " . $name . "\n";
        $data = api_data_responses_sms_report($campaignid);

        if(!empty($data)) foreach($data as $targetid => $result) {
            $content .= isset($result['merge_data']['internal_id']) ? $result['merge_data']['internal_id'] : $result['targetkey'];

            $content .= ",+61" . substr($result["destination"], 1) . ",";

            if(isset($result["response_data"]["SENT"])) $content .= date("d/m/Y H:i:s", strtotime($result["response_data"]["SENT"])) . ",";
            else $content .= ",";

            $content .= $result["status"] . ",";

            if(isset($result["response_data"]["UNDELIVERED"])) $content .= "PTT 20,";
            else if(isset($result["response_data"]["REMOVED"])) $content .= "PTT 20,";
            else if(isset($result["response_data"]["DELIVERED"])) $content .= "PTT 4,";
            else if(isset($result["response_data"]["EXPIRED"])) $content .= "PTT 24,";
            else if(isset($result["response_data"]["UNKNOWN"])) $content .= "PTT 26,";
            else $content .= "PTT 1,";

            if(isset($result["response_data"]["DELIVERED"])) $content .= date("d/m/Y H:i:s", strtotime($result["response_data"]["DELIVERED"])) . ",";
            else $content .= ",";

            if(isset($result["response_data"]["UNDELIVERED"])) $content .= date("d/m/Y H:i:s", strtotime($result["response_data"]["UNDELIVERED"])) . ",";
            else $content .= ",";

            if(isset($result["response_data"]["UNKNOWN"])) $content .= date("d/m/Y H:i:s", strtotime($result["response_data"]["UNKNOWN"])) . ",";
            else $content .= ",";

            if(isset($result["response_data"]["DUPLICATE"])) $content .= "DUPLICATE,";
            else $content .= ",";

            if(isset($result["merge_data"]["Arrears"])) $content .= $result["merge_data"]["Arrears"] . ",";
            else $content .= ",";

            if(isset($result["cost"])) $content .= $result["cost"] . ",";
            else $content .= ",";

            $content .= "\n";

        }

        $campaigns[] = $campaignid;
    }

    return $content;
}
