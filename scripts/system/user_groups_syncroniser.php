<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 *
 * Automatically adds all but any excluded user groups to the given user id
 * Used to keep users like the monitor system user in sync with any new user groups
 *
 * tags
 *  - monitor-user-id = The user id to add groups to
 *  - exclude-groups = Comma separated list of group names to exclude from this process
 *
 * $tags['monitor-user-id'] = 3;
 * $tags["exclude-groups"] = "Admin Group";
 */

require_once("Morpheus/api.php");

$cronid = getenv('CRON_ID');
$tags = api_cron_tags_get($cronid);

// Setup user
if (!isset($tags['monitor-user-id'])) {
    print "No user id provided";
    exit(1);
}

$userid = $tags['monitor-user-id'];

if (!api_users_checkidexists($userid)) {
    print "User id does not exist";
    exit(1);
}

// Excluded groups
$mustExcludeGroups = [];
if (isset($tags['exclude-groups'])) {
    $mustExcludeGroups = explode(",", $tags['exclude-groups']);
    $mustExcludeGroups = array_map("trim", $mustExcludeGroups);
}

// Fetch group lists
$allGroups = api_groups_listall();
$userGroups = api_groups_listall_for_user($userid);

// Determine user's missing groups and build new group list
$missingGroups = array_diff($allGroups, $userGroups);
$filteredMissingGroups = array_diff($missingGroups, $mustExcludeGroups);
foreach ($filteredMissingGroups as $groupId => $groupname){
    if (api_groups_checkgroupexists($groupname)) {
        $userGroups[$groupId] = $groupname;
    }
}

// Save group list if there are missing groups
if(!empty($filteredMissingGroups)) {
    print "Adding groups to user id {$userid}:\n";
    foreach ($filteredMissingGroups as $id => $group) {
        print "{$id}: {$group}\n";
    }

    $serializedGroups = serialize(array_keys($userGroups));
    api_users_setting_set($userid, "usergroups", $serializedGroups);
} else {
    print "No new groups\n";
}


