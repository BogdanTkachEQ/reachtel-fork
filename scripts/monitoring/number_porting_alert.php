<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

require_once("Morpheus/api.php");

$cronId = getenv('CRON_ID');
$tags = api_cron_tags_get($cronId);

$expected_tags = [
    'destination',
    'carriercode',
    'subject',
    'alert-destination'
];

$tags = $tags ?: [];
$missing_tags = array_diff($expected_tags, array_keys($tags));
if (!$tags || !empty($missing_tags)) {
    $missing_tags_string = implode(', ', $missing_tags);
    print "Mandatory tags are missing: $missing_tags_string";
    exit();
}

$message = '';

$hlr = api_hlr_process($tags['destination']);
if (empty($hlr['carriercode'])) {
    $message .= "Unable to confirm DID has not been ported. Did not receive a carrier code for the number.\n";
    print "Unable to confirm DID has not been ported. Did not receive a carrier code for the number.\n";
} elseif ($hlr['carriercode'] != $tags['carriercode']) {
    $message .= "DID appears to have been ported to another carrier ({$hlr['carriercode']})! Investigate immediately!\n";
    print "DID appears to have been ported to another carrier ({$hlr['carriercode']})! Investigate immediately!\n";
} else {
    print "Confirmed DID has not been ported.\n";
}

if ($message) {
    number_porting_alert_send_email($message, $tags);
    print "Sent notification email.\n";
}

function number_porting_alert_send_email($message, array $tags)
{
    $default_recipients = isset($tags['email']) ? $tags['email'] : 'support@reachtel.com.au';
    $email = [];
    $email["to"]          = @$tags["alert-destination"];
    if (empty($email["to"])) {
        $email["to"]  = $default_recipients;
    } else {
        $email["cc"]  = $default_recipients;
    }
    $email["subject"]     = $tags['subject'];
    $email["textcontent"] = <<<EOT
Hello,

{$message}

Please investigate immediately!

EOT;
    $email["htmlcontent"] = nl2br($email["textcontent"]);
    $email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

    api_email_template($email);
}
