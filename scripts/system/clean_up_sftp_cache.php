<?php

require_once(__DIR__ . '/../../api.php');
$cronid = 126;
$tags = api_cron_tags_get($cronid);

print "Starting to clear sftp cache" . "\n";

$sql = 'SELECT `id`, `hostname`, `username`, `password`, `filename`, `remotefile` from `sftp_cache`';
print "Reading all cached data\n";
$rs = api_db_query_read($sql);

if (!$rs->RecordCount()) {
    print "Nothing to fetch\n";
    exit;
}

$idsToBeRemoved = [];
$failedFiles = [];
$results = $rs->GetAssoc();

foreach ($results as $id => $result) {
    $localfile = api_misc_get_sftp_cache_absolute_path($result['filename']);

    if (!$localfile) {
        $failedFiles[] = $result['filename'];
        continue;
    }

    if (!file_exists($localfile)) {
        if (!file_exists('/tmp/' .  $result['filename'])) {
            $failedFiles[] = $result['filename'];
            continue;
        }
        $localfile = '/tmp/' .  $result['filename'];
    }

    $options = [
        'hostname' => $result['hostname'],
        'localfile' => $localfile,
        'remotefile' => $result['remotefile'],
        'username' => $result['username'],
    ];

    if ($result['password']) {
        $options['password'] = api_misc_decrypt_base64($result['password']);
    }

    if (!api_misc_sftp_put($options)) {
        $failedFiles[] = $result['filename'];
        print 'Failed to send sftp for file ' . $localfile . "\n";
        continue;
    }

    print "Successfully uploaded file " . $localfile . "\n";

    if (!unlink($localfile)) {
        print "Failed to remove file " . $localfile . "\n";
    }

    $idsToBeRemoved[] = $id;
}

if ($idsToBeRemoved) {
    print "Removing all successful uploads from cache table\n";

    $sql = sprintf(
        'DELETE FROM `sftp_cache` WHERE id IN (%s)',
        implode(',', array_fill(0, count($idsToBeRemoved), '?'))
    );

    if (!api_db_query_write($sql, $idsToBeRemoved)) {
        print "Failed to remove cached sftp data from table\n";


        $email = [];

        $email["to"]          = $tags['reporting-destination'];
        $email["subject"]     = "[ReachTEL] SFTP CACHE CLEAN UP FAILURE " . date("Y-m-d");
        $email["textcontent"] = "Hello,\n\nThere has been a failure when attempting to clear sftp cache. The ids are as follows,\n\n" . implode(',', $idsToBeRemoved) . "\n\n";
        $email["htmlcontent"] = "Hello,<br /><br />There has been a failure when attempting to clear sftp cache. The ids are as follows,<br /><br />" . implode(',', $idsToBeRemoved) . "<br/><br/>";
        $email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

        api_email_template($email);
    }
}

if ($failedFiles) {
    $email = [];

    $email["to"]          = $tags['reporting-destination'];
    $email["subject"]     = "[ReachTEL] SFTP CACHED FILE UPLOAD FAILURE " . date("Y-m-d");
    $email["textcontent"] = "Hello,\n\nThere has been a failure when attempting to perform sftp operation of cached files. The filenames are as follows,\n\n" . implode(',', $failedFiles) . "\n\n";
    $email["htmlcontent"] = "Hello,<br /><br />There has been a failure when attempting to perform sftp operation of cached files. The filenames are as follows,<br /><br />" . implode(',', $failedFiles) . "<br/><br/>";
    $email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

    api_email_template($email);
}

print "Job done";
