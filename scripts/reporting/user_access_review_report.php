#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$cronId = 106;
$tags = api_cron_tags_get($cronId);

if (
    !isset($tags['sftp-username']) ||
    !isset($tags['sftp-password']) ||
    !isset($tags['sftp-hostname']) ||
    !isset($tags['sftp-path'])
) {
    print 'Required tags not found';
    exit;
}

$securityZones = api_security_zone_listall();
$groups = api_groups_listall();

$headers = [
    'Username',
    'Firstname',
    'Lastname',
    'Email',
    'Description',
    'Status',
    'Is Admin',
    'Security zones',
    'User groups',
    'Last Authenticated',
    'Group Owner',
    'IP address restrictions',
    'SMS DID',
];

$fieldHeaderMap = [
    'username' => ['pos' => 0, 'name' => $headers[0], 'value' => function($value) { return transformNull($value); }],
    'firstname' => ['pos' => 1, 'name' => $headers[1], 'value' => function($value) { return transformNull($value); }],
    'lastname' => ['pos' => 2, 'name' => $headers[2], 'value' => function($value) { return transformNull($value); }],
    'emailaddress' => ['pos' => 3, 'name' => $headers[3], 'value' => function($value) { return transformNull($value); }],
    'description' => ['pos' => 4, 'name' => $headers[4], 'value' => function($value) { return transformNull($value); }],
    'status' => ['pos' => 5, 'name' => $headers[5], 'value' => function($value) { return transformStatus($value); }],
    'groupowner' => [
        ['pos' => 6, 'name' => $headers[6], 'value' => function($value) { return transformAdmin($value); }],
        [
            'pos' => 10,
            'name' => $headers[10],
            'value' => function($value) use ($groups) { return transformGroupOwner($groups, $value); }
        ]
    ],
    'securityzones' => [
        'pos' => 7,
        'name' => $headers[7],
        'value' => function($value) use ($securityZones) {
            return transformSecurityZones($securityZones, $value);
        }
    ],
    'usergroups' => [
        'pos' => 8,
        'name' => $headers[8],
        'value' => function($value) use ($groups) {
            return transformUserGroups($groups, $value);
        }
    ],
    'lastauth' => [
        'pos' => 9,
        'name' => $headers[9],
        'value' => function($value) { return transformLastAuth($value); }
    ],
    'ipaccesslist' => ['pos' => 11, 'name' => $headers[11], 'value' => function($value) { return transformNull($value); }],
    'smsapidid' => ['pos' => 12, 'name' => $headers[12], 'value' => function($didid) {
        $name = api_sms_dids_setting_getsingle($didid, 'name');

        return $name ?: '';
    }],
];

$sql = 'SELECT id, item, value FROM `key_store` WHERE type=? AND item IN (' .
    implode(',', array_fill(0, count($fieldHeaderMap), '?')) .
    ') ORDER BY id ASC';

$rs = api_db_query_read($sql, array_merge(['USERS'], array_keys($fieldHeaderMap)));

if(!$rs || $rs->RecordCount() == 0) {
    // Highly unlikely
    print "No users found";
    exit;
}

$users = [];

while(!$rs->EOF) {
    $id = $rs->Fields('id');
    $item = $rs->Fields('item');
    $value = $rs->Fields('value');

    if (!isset($users[$id])) {
        $users[$id] = array_fill(0, count($fieldHeaderMap), '');
    }

    $field = $fieldHeaderMap[$item];

    if (isset($field[0]) && is_array($field[0])) {
        foreach ($field as $fieldItem) {
            $users[$id][$fieldItem['pos']] = $fieldItem['value']($value);
        }
    } else {
        $users[$id][$field['pos']] = $field['value']($value);
    }

    $rs->MoveNext();
}

$users = array_values($users);
array_unshift($users, $headers);
$data = api_csv_string($users);

$tempfname = tempnam("/tmp", "user_access_review");

if(!file_put_contents($tempfname, $data)) {
    print 'Failed to write to file.';
    exit;
}

$filename = "USER_ACCESS_REVIEW_" . date("dmY") . ".csv";

$options = [
    "hostname"  => $tags["sftp-hostname"],
    "username"  => $tags["sftp-username"],
    "password"  => $tags["sftp-password"],
    "localfile" => $tempfname,
    "remotefile" => $tags["sftp-path"] . $filename
];

$result = api_misc_sftp_put_safe($options);

unlink($tempfname);

if(!$result) {
    print 'Failed to upload csv.';
    exit;
}

/**
 * Maps user zone ids with the zone value
 *
 * @param $securityZonesList
 * @param $usersZones
 * @return string
 */
function transformSecurityZones($securityZonesList, $usersZones) {
    if ($usersZones === null || !unserialize($usersZones)) {
        return '';
    }

    return implode(' | ', array_map(function($zone) use ($securityZonesList) {
        return isset ($securityZonesList[$zone]) ? $securityZonesList[$zone] : '';
    }, unserialize($usersZones)));
}

/**
 * Maps user group ids to group values
 *
 * @param $groupsList
 * @param $userGroups
 * @return string
 */
function transformUserGroups($groupsList, $userGroups) {
    if ($userGroups === null || !unserialize($userGroups)) {
        return '';
    }

    return implode(' | ', array_map(function($group) use ($groupsList) {
        return isset ($groupsList[$group]) ? $groupsList[$group] : '';
    }, unserialize($userGroups)));
}

/**
 * @param $value
 * @return string
 */
function transformNull($value) {
    return $value === null ? '' : $value;
}

/**
 * @param $value
 * @return string
 */
function transformStatus($value) {
    return $value == 1 ? 'Enabled' : 'Disabled';
}

/**
 * @param $value
 * @return string
 */
function transformAdmin($value) {
    return $value == ADMIN_GROUP_OWNER_ID ? 'Yes' : 'No';
}

/**
 * @param $value
 * @return string
 */
function transformLastAuth($value) {
    if (is_null($value)) {
        return '';
    }

    $date = new DateTime();
    $date->setTimestamp($value);
    return $date->format('d-m-Y H:i:s');
}

/**
 * @param $groupsList
 * @param $value
 * @return string
 */
function transformGroupOwner($groupsList, $value) {
    return isset($groupsList[$value]) ? $groupsList[$value] : '';
}
