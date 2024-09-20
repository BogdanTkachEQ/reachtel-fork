<?php

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(114);

// Cron tags validation
$validations = [
	'git-remote-repo' => '/^[A-Za-z0-9-\._\/]+$/',
	'git-remote-file' => '/^[A-Za-z0-9-\._]+$/',
	'whitelist-settings' => false,
	'whitelist-tags' => false,
];

foreach ($validations as $name => $regex) {
	if (!isset($tags[$name])) {
		print "ERROR: Mandatory cron tag '{$name}' is not set\n";
		exit;
	}
	if ($regex && !preg_match($regex, $tags[$name])) {
		print "ERROR: Mandatory cron tag '{$name}' contains invalid chars\n";
		exit;
	}
}

// Proxy whitelist
$whitelist = [];
if (isset($tags['static-whitelist']) && $tags['static-whitelist']) {
	$whitelist = explode(',', $tags['static-whitelist']);
}

// Creates a dynamic SQL query
$sql = "SELECT `type`, `item`, `value` FROM `key_store` WHERE ";
$params = $conditions = [];

// Comma delimited list of keystore settings in format <TYPE>.<setting_name> (CAMPAIGNS.callback_url)
$tags['whitelist-settings'] = explode(',', $tags['whitelist-settings']);
foreach ($tags['whitelist-settings'] as $setting) {
	$parsed = parseKeyStoreTypeItem($setting);
	if (is_array($parsed)) {
		$conditions[] = "(`type` = ? AND `item` = ?)";
		$params[] = $parsed[0]; // type
		$params[] = $parsed[1]; // item
	}
}

$whitelistTags = [];
// Comma delimited list of keystore tags in format <TYPE>.<tag_name> (USERS.custom_tag)
$tags['whitelist-tags'] = explode(',', $tags['whitelist-tags']);
foreach ($tags['whitelist-tags'] as $key => $tag) {
	$parsed = parseKeyStoreTypeItem($tag);
	if (is_array($parsed)) {
		// saved tags map for parsing later
		$whitelistTags[$parsed[0]][] = $parsed[1];
		$conditions[] = "(`type` = ? AND `item` = ? AND `value` LIKE ?)";
		$params[] = $parsed[0];
		$params[] = 'tags';
		$params[] = "%{$parsed[1]}%";
	}
}
if (!$conditions) {
	print "ERROR: Whitelist settings or tags could not be parsed\n";
	exit;
}
$sql .= implode(' OR ', $conditions);
$rs = api_db_query_read(
	$sql,
	$params
);

while ($row = $rs->FetchRow()) {
	if ($row['value']) {
		// tags serializes value
		if (isset($whitelistTags[$row['type']]) && ($array = @unserialize($row['value']))) {
			foreach ($array as $k => $v) {
				if (in_array($k, $whitelistTags[$row['type']]) && ($host = getHost($v))) {
					$whitelist[] = $host;
				}
			}
		} else {
			if ($host = getHost($row['value'])) {
				$whitelist[] = $host;
			}
		}
	}
}

$whitelist = array_unique($whitelist);
exec(
	"git archive --format=tar --remote=" . escapeshellarg("ssh://git@{$tags['git-remote-repo']}") . " HEAD " . escapeshellarg($tags['git-remote-file']) . " | tar -O -xf -",
	$original,
	$error
);
if ($error > 0) {
	print "ERROR: Could not get remote file\n";
	exit;
}

$original = array_values(array_filter($original));
$whitelist = array_values(array_filter($whitelist));
sort($original);
sort($whitelist);

// We push only if we have a diff
if ($original !== $whitelist) {
	if ($diff = array_diff($whitelist, $original)) {
		print "New URLs:\n" . implode("\n", $diff) . "\n\n";
	}
	if ($diff = array_diff($original, $whitelist)) {
		print "Removing URLs:\n" . implode("\n", $diff) . "\n\n";
	}
	$whitelist = implode("\n", $whitelist);
	$tmp = sys_get_temp_dir() . '/repotmp';
	$cmd = "
		rm -fr " . escapeshellarg($tmp) . "
		git clone --depth=1 " . escapeshellarg('ssh://git@' . $tags['git-remote-repo']) . " " . escapeshellarg($tmp) . " 2>&1;
		pushd " . escapeshellarg($tmp) . ";
		git config user.name \"`whoami`-\$HOSTNAME\"
		git config user.email 'support@reachtel.com.au'
		git config push.default 'simple'
		echo " . escapeshellarg($whitelist) . " > " . escapeshellarg($tags['git-remote-file']) . ";
		git add " . escapeshellarg($tags['git-remote-file']) . "
		git commit -m 'Cron 114 - New URLs' 2>&1;
		git push 2>&1;
		popd;
		rm -fr " . escapeshellarg($tmp) . ";
	";
	exec($cmd, $out, $error);
	print implode("\n", $out) . "\n";

	if ($error > 0) {
		print "ERROR: Failed to push to repo '{$tags['git-remote-repo']}'\n";
		exec("rm -fr " . escapeshellarg($tmp));
		exit;
	}
	print "Pushed and done!\n";
} else {
	print "There are no new URLs\n";
}

/**
 * Parsing string 'TYPE.item'
 *
 * @param  string $str
 * @return false|array
 */
function parseKeyStoreTypeItem($str) {
	$str = trim($str);
	if ($str && preg_match('/^([^\.]+)\.(.*)$/', $str, $match)) {
		return [$match[1], $match[2]];
	}

	return false;
}

/**
 * @param  string $value
 * @return false|array
 */
function getHost($value) {
	if ($value && $url = @parse_url($value)) {
		// if the 'host' is set, this a valid URL/IP.
		if (isset($url['host'])) {
			return $url['host'];
		}
	}

	return false;
}
