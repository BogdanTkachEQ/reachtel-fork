#!/usr/bin/php
<?php

require_once ("Morpheus/api.php");

$tags = api_cron_tags_get(64);

$reports = [];
foreach(api_campaigns_list_all(true, null, 15, array("search" => "CollectionHouse-*-prod-" . date("Ymd", strtotime("yesterday")))) as $campaignid => $name) {

    $data = api_data_responses_phone_report(
        $campaignid,
        date("Y-m-d 00:00:00", strtotime("yesterday")),
        date("Y-m-d 23:59:59", strtotime("yesterday"))
    );

    if ($data) {
        $reports[$campaignid] = $data;
    }
}
if(!$reports) {
    print "No data to report on. Exiting\n";
    exit;
}

$accounts = ['actioned' => [], 'not_actioned' => []];
$nonRPC = [
    'Unverified Customer' => 0,
    'Terminated no attempt' => 0,
    'Failed and Terminated' => 0,
    'Failed and Transferred' => [
        'Transfer unsuccessful' => 0,
        'Transfer successful' => 0
    ],
    'Customer not available' => 0,
    'Invalid Contact' => 0,
];
$RPC = [
    'Verified RPC' => 0,
    'Verification Bypassed' => 0,
    'Terminated without input' => 0,
    'Paid' => 0,
    'Paid Arrears on Paid' => 0,
    'PTPs' => 0,
    'PTP' => [
        'Arrears on PTP' => 0,
        'Balance on PTP' => 0,
        'PTP Terminated' => 0,
        'PTP Options' => 0,
        'PTP Transferred' => [
            'Transfer unsuccessful' => 0,
            'Transfer successful' => 0,
        ],
        'DD_RERUN' => 0,
    ],
    'Transferred' => [
        'Transfer unsuccessful' => 0,
        'Transfer successful' => 0
    ]
];

$liveAnswers = $amd = $calltime = $nbcalls = $callback = 0;
foreach($reports as $campaignid => $report) {
    foreach($report as $targetid => $result) {
        $mergedata = $result['merge_data'];
        $responsedata = isset($result['response_data']) ? $result['response_data'] : [];

        // Accounts Received
        $account = $mergedata['ClientReferenceNumber'];

        if(isset($responsedata["CALLBACK"]) && $responsedata["CALLBACK"]) {
            $callback++;
        }

        if($result["status"] == "READY") {
            $accounts['not_actioned'][(int) $account] = $account;
        } else {
            $accounts['actioned'][(int) $account] = $account;
        }

        // Number of Attempts
        if(has_event($result['events'], 'ANSWER')) {
            if (isset($responsedata['0_AMD']) && ($responsedata['0_AMD'] != 'HUMAN')) {
                $amd++;
            }
        }

        // Non-RPC (IS NOT CUSTOMER)
        if (isset($responsedata['1_OPTION']) && $responsedata['1_OPTION'] != '1_ISCUSTOMER') {
            // Press 1 on Greeting, but doesn’t complete Identification successfully
            if ($responsedata['1_OPTION'] == '2_NOTCUSTOMER') {
                $nonRPC['Unverified Customer']++;

                if (isset($responsedata['TRANSFER_TIMESTAMP'])) {
                    $nonRPC['Failed and Transferred']++;
                    if(isset($responsedata['TRANSFER_OUTCOME']) && ($responsedata['TRANSFER_OUTCOME'] != "ACCEPTED")) {
                        $nonRPC['Failed and Transferred']['Transfer unsuccessful']++;
                    } else {
                        $nonRPC['Failed and Transferred']['Transfer successful']++;
                    }
                } elseif (!isset($responsedata['2_OPTION'])) {
                    $nonRPC['Terminated no attempt']++;
                } elseif ($responsedata['2_OPTION'] == 'INVALID') {
                    $nonRPC['Invalid Contact']++;
                }
            }
            // TODO  Customer not available (Press 2 on Greeting) no sample data

        // RPC (IS CUSTOMER)
        } elseif (isset($responsedata['1_OPTION']) && $responsedata['1_OPTION'] == '1_ISCUSTOMER') {
            $RPC['Verified RPC']++;
            // TODO Verification Bypassed ??

            /* Terminated without input */
            if (!isset($responsedata['2_DEBTOPTIONS']) && !isset($responsedata['TRANSFER_REASON'])) {
                $RPC['Terminated without input']++;
            }

            if (isset($responsedata['2_DEBTOPTIONS']) && $responsedata['2_DEBTOPTIONS'] == '3_DD_RERUN') {
                $RPC['PTP']['DD_RERUN']++;
            } elseif(isset($responsedata['2_DEBTOPTIONS']) && $responsedata['2_DEBTOPTIONS'] == '1_PTP48HOURS') {
                $RPC['Paid']++;
                $RPC['Paid Arrears on Paid'] += $mergedata['ArrearsAmount'];
                $RPC['PTP']['Balance on PTP'] += $mergedata['TotalOwed'];
                $RPC['PTPs']++;
                $RPC['PTP']['PTP Terminated']++;
            } elseif (isset($responsedata['2_DEBTOPTIONS']) &&  $responsedata['2_DEBTOPTIONS'] == '2_PTP7DAYS') {
                $RPC['PTPs']++;
                $RPC['PTP']['Arrears on PTP'] += $mergedata['ArrearsAmount'];
                $RPC['PTP']['Balance on PTP'] += $mergedata['TotalOwed'];
                $RPC['PTP']['PTP Terminated']++;
            } elseif (isset($responsedata['TRANSFER_REASON'])
                || (isset($responsedata['2_DEBTOPTIONS']) && in_array($responsedata['2_DEBTOPTIONS'], ['3_DD_RERUN', '3_TRANSFER', '4_TRANSFER']))) {
                if(isset($responsedata['TRANSFER_OUTCOME']) && ($responsedata['TRANSFER_OUTCOME'] != "ACCEPTED")) {
                    $RPC['PTP']['PTP Transferred']['Transfer unsuccessful']++;
                } else {
                    $RPC['PTP']['PTP Transferred']['Transfer successful']++;
                }
            } else {
                $RPC['PTPs']++;
                $RPC['PTP']['PTP Terminated']++;
            }
        }

        // Non-RPC (IS NOT CUSTOMER)
        if (array_key_exists('1_TRANSDUR', $responsedata)) {
            // Press 1 on Greeting, but doesn’t complete Identification successfully
            if($responsedata['1_TRANSDUR'] > 0) {
                $RPC['Transferred']['Transfer successful']++;
            } else {
                $RPC['Transferred']['Transfer unsuccessful']++;
            }
        }

        // Total Call time
        if (isset($result['events']) && count($result['events']) && isset(current($result['events'])['duration'])) {
            $nbcalls++;
            $calltime += current($result['events'])['duration'];
        }

        // Average Call time
    }
}

$content = "Measure,Description\n";

// Accounts Received
$content .= "Accounts Received," . (count($accounts['actioned']) + count($accounts['not_actioned'])) . "\n";
$content .= "   Accounts not actioned," . count($accounts['not_actioned']) . "\n";
$content .= "   Accounts Actioned," . count($accounts['actioned']) . "\n";


// Number of Attempts
$json = api_data_target_status_phone_json($campaignid);
$json = $json['status'];
$content .= "Number of Attempts," . ($json['calls']) . "\n";
$content .= "   Calls not answered," . ($json['busy'] + $json['ringout'] + $json['disconnected'] + $json['chanunavail']) . "\n";
$content .= "       Busy," . ($json['busy']) . "\n";
$content .= "       Ringouts," . ($json['ringout']) . "\n";
$content .= "       Disconnected," . ($json['disconnected']) . "\n";
$content .= "       Call issue," . ($json['chanunavail']) . "\n";
$content .= "   Calls answered," . ($json['answered']) . "\n";


// AMD
$content .= "Answering Machine Detected,$amd\n";
$content .= "Callback,$callback\n";


// Non-RPC (IS NOT CUSTOMER)
$content .= "Non-RPC (IS NOT CUSTOMER)," . ($nonRPC['Unverified Customer'] + $nonRPC['Invalid Contact'] + $nonRPC['Customer not available']) . "\n";
$content .= "   Invalid Contact," . $nonRPC['Invalid Contact'] . "\n";
$content .= "   Unverified Customer," . $nonRPC['Unverified Customer'] . "\n";
$content .= "       Terminated no attempt," . $nonRPC['Terminated no attempt'] . "\n";
$content .= "       Failed and Terminated," . $nonRPC['Failed and Terminated'] . "\n";
$content .= "       Failed and Transferred," . array_sum($nonRPC['Failed and Transferred']) . "\n";
$content .= "           Transfer unsuccessful," . $nonRPC['Failed and Transferred']['Transfer unsuccessful'] . "\n";
$content .= "           Transfer successful," . $nonRPC['Failed and Transferred']['Transfer successful'] . "\n";


// RPC (IS CUSTOMER)
// TODO Sum of Terminated ????
$content .= "RPC (IS CUSTOMER)," . ($RPC['Verified RPC'] + $RPC['Terminated without input'] + $RPC['Paid'] + $RPC['PTPs'] + ($RPC['PTP']['PTP Transferred']['Transfer unsuccessful'] + $RPC['PTP']['PTP Transferred']['Transfer successful'])) . "\n";
$content .= "   Verified RPC," . $RPC['Verified RPC'] . "\n";
$content .= "   Verification Bypassed," . $RPC['Verification Bypassed'] . "\n";
$content .= "   Terminated without input," . $RPC['Terminated without input'] . "\n";
$content .= "   Paid," . $RPC['Paid'] . "\n";
$content .= "   Paid Arrears on Paid,$" . $RPC['Paid Arrears on Paid'] . "\n";
$content .= "   PTP," . $RPC['PTPs'] . "\n";
$content .= "       Arrears on PTP,$" . $RPC['PTP']['Arrears on PTP'] . "\n";
$content .= "       Balance on PTP,$" . $RPC['PTP']['Balance on PTP'] . "\n";
$content .= "       PTP Terminated," . $RPC['PTP']['PTP Terminated'] . "\n";
$content .= "       PTP Transferred," . ($RPC['PTP']['PTP Transferred']['Transfer unsuccessful'] + $RPC['PTP']['PTP Transferred']['Transfer successful']) . "\n";
$content .= "           Transfer unsuccessful," . $RPC['PTP']['PTP Transferred']['Transfer unsuccessful'] ."\n";
$content .= "           Transfer successful," . $RPC['PTP']['PTP Transferred']['Transfer successful'] ."\n";
$content .= "       DD_RERUN," . $RPC['PTP']['DD_RERUN']. "\n";
$content .= "   Transferred Error," . ($RPC['Transferred']['Transfer unsuccessful'] + $RPC['Transferred']['Transfer successful']) . "\n";
$content .= "       Transfer unsuccessful," . $RPC['Transferred']['Transfer unsuccessful'] ."\n";
$content .= "       Transfer successful," . $RPC['Transferred']['Transfer successful'] ."\n";
$content .= "Total Call time," . $calltime/60 ."\n";
$content .= "Average Call time," . ($nbcalls ? ($calltime / $nbcalls) : 0) ."\n";

$email["to"]          = $tags["report-destination"];
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";
$email["subject"]     = "[ReachTEL] St George daily report - " . date("Y-m-d");

$email["textcontent"] = "Hello,\n\nPlease find attached the St George report.\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the St George report.<br /><br />";

$email["attachments"][] = array("content" => $content, "filename" => "StGeorge-" . date("Ymd") . ".csv");

api_email_template($email);

print "Report sent. Exiting.\n";
exit;

function has_event(array $events, $name) {
    foreach($events as $event) {
        foreach($event as $e) {
            if (is_array($e) && strtolower($e['value']) == strtolower($name)) {
                return true;
            }
        }
    }

    return false;
}
