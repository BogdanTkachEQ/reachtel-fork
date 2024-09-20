<?php

require_once("Morpheus/api.php");

$cronId = 105;
$tags = api_cron_tags_get($cronId);

$regexp = '^BOC-SMS.*$';

// get a reasonable number of matching campaigns to ensure we get all created in the last week
// (but still limit the number we need to check)
$campaignIds = api_campaigns_list_all(false, false, 50, ['regex' => $regexp]);

// get the full campaign data so we can check created date
$campaigns = array_map(
    function($campaignId) {
        return ['id' => $campaignId] + api_campaigns_setting_getall($campaignId);
    },
    $campaignIds
);

// default to current day run, but allow override with tag
$dayOfWeek = $tags && array_key_exists('day-of-week', $tags) ? $tags['day-of-week'] : date('D');

// if it's today, end last night at midnight
if (date('D') === date('D', strtotime($dayOfWeek))) {
    $endTime = strtotime('today -1sec'); // 23:59:59 yesterday
} else {
    $endTime = strtotime('last ' . $dayOfWeek . ' -1sec'); // 23:59:59 the day before the most recent chosen day
    if (!$endTime) {
        print "Cron tag 'day-of-week'='{$tags['day-of-week']}' is invalid (e.g: 'Sat' or 'Sun')\n";
        exit;
    }
}

// only get campaigns created in the week ending the most recent day
$startTime = DateTime::createFromFormat('U', $endTime)
    ->sub(new DateInterval('P1W'))
    ->add(new DateInterval('PT1S')) // start at midnight, not 23:59:59
    ->format('U');

$filteredCampaigns = array_filter(
    $campaigns,
    function($campaign) use ($startTime, $endTime) {
        return $campaign['created'] >= $startTime
            && $campaign['created'] <= $endTime;
    }
);

// get back to a list of ids
$campaignIds = array_map(
    function($campaign) {
        return $campaign['id'];
    },
    $filteredCampaigns
);

if (!$campaignIds) {
    print 'No campaign ids found';
    exit;
}

$data = api_campaigns_report_cumulative(
    $campaignIds,
    'sms'
);

if (!$data) {
    print 'No data to return';
    exit;
}

// $endTime is 23:59:59 the previous day so add the second back
$reportDate = $endTime + 1;

$email["to"]	      = $tags['destination-email'];
$email["subject"]     = "[ReachTEL] BOC Weekly SMS report - " . date("d-m-Y", $reportDate);
$email["textcontent"] = "Hello,\n\nPlease find attached your SMS report for this week.\n\n";
$email["htmlcontent"] = nl2br($email["textcontent"]);
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $data, "filename" => "BOC-REACHTEL-SMS-" . date("dmY", $reportDate) . ".csv");

api_email_template($email);
