<?php

require_once("Morpheus/api.php");

$cronid = getenv('CRON_ID');
if (!$cronid) {
    die("ERROR: Invalid env var CRON_ID\n");
}
$tags = api_cron_tags_get($cronid);

$groups = isset($tags['groupids']) ? explode(',', $tags['groupids']) : array_keys(api_groups_listall());

if (!isset($tags['run_ids'])) {
    $startDate = isset($tags['start']) ?
        DateTime::createFromFormat('d-m-Y H:i:s', $tags['start']) :
        new DateTime('yesterday 00:00:00');

    $endDate = isset($tags['end']) ?
        DateTime::createFromFormat('d-m-Y H:i:s', $tags['end']) :
        new DateTime('yesterday 23:59:59');

    if ($endDate < $startDate) {
        print "Invalid dates passed";
        exit;
    }
}

print "Starting to insert feed for each groups\n";

// It is memory intensive, so forking it.
$pid = pcntl_fork();
if ($pid == -1) {
    exit("Error forking...\n");
} else if ($pid == 0) {
    print "Forked process...\n";
    if (isset($tags['run_ids'])) {
        runBilling($tags, $groups);
    } else {
        $dayInterval = new DateInterval('P1D');
        $period = new DatePeriod($startDate, $dayInterval, $endDate);
        foreach ($period as $i => $p) {
            $start = clone $p->setTime(0, 0, 0);
            $end = $p->setTime(23, 59, 59);
            api_db_ping();
            runBilling($tags, $groups, $start, $end);
        }
    }
    api_error_printiferror();
    print "Billing run completed...\n";
    exit();
} else {
    pcntl_wait($status); //Protect against Zombie children
}
api_db_reset_connection();
// Check if the run was completed successfully
if (!isset($tags['run_ids']) && !api_billing_transactions_has_billing_run_for_the_day($endDate)) {
    print "Something went wrong since the billing run was not completed. Check logs for more details.\n";
    billing_transactions_notify_failure($tags);
}

print "Job finished";

function runBilling(array $tags, array $groups, $start = null, $end = null) {
    try {
        if (!isset($tags['run_ids'])) {
            $message = "Billing run started for date range" . $start->format('d-m-Y H:i:s') . " and " . $end->format('d-m-Y H:i:s');
            api_misc_audit($message);
            print $message . "\n";
            $run_id = api_billing_transactions_run_billing($groups, $start, $end);
            $message = "Billing run complete. Run id: $run_id";
            api_misc_audit($message);
            print $message . "\n";
            $run_ids = [$run_id];
        } else {
            $run_ids = explode(',', $tags['run_ids']);
        }

        print "Starting to export billing for run ids: " . implode(', ', $run_ids) . "...\n";
        $tempfname = tempnam("/tmp", "selcomm_export_" . (new DateTime())->getTimestamp());
        api_billing_transactions_selcomm_export($run_ids, $tempfname);
        $filename = 'REACHTEL' . (new DateTime())->format('Ymdhis') . '.dat';
        $options = [
            "hostname"  => $tags["sftp-hostname"],
            "username"  => $tags["sftp-username"],
            "password"  => $tags["sftp-password"],
            "localfile" => $tempfname,
            "remotefile" => $tags["sftp-path"] . $filename
        ];

        print "Uploading file to sftp....\n";

        if (api_misc_sftp_put_safe($options)) {
            print "Uploaded file to sftp....\n";
            print "Billing runs exported\n";
        } else {
            print "Failed to upload file to sftp\n";
        }

        if (isset($tags['save-file']) && $tags['save-file']) {
            if (rename($tempfname, SAVE_LOCATION. "/" . INVOICES_LOCATION . "/" . $filename)) {
                print "Transaction file saved locally.\n";
                return;
            }
            print "Failed to save file locally.\n";
        }

        unlink($tempfname);
        print "Temporary file removed...\n";
    } catch (Exception $e) {
        api_error_raise($e->getMessage());
        print $e->getMessage() . "\n";
        billing_transactions_notify_failure($tags);
    }
}

function billing_transactions_notify_failure($tags) {
    $email["to"] = $tags["failure-notification-email"];
    $email["cc"] = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["subject"] = "[ReachTEL] Selcomm failure";
    $email["textcontent"] = "Hello,\n\nSelcomm billing job has failed. Please take a look at the logs on the cron.\n";
    $email["htmlcontent"] = nl2br($email["textcontent"]);
    api_email_template($email);
}
