<?php

function api_reports_overall_sms_volume_from_campaigns(
	array $groupids,
	array &$did_map,
	DateTime $start_time,
	DateTime $end_time
) {
	$start = $start_time->format('Y-m-d H:i:s');
	$end = $end_time->format('Y-m-d H:i:s');

	$start_timestamp = (new DateTime($start))->getTimestamp();

	$sql = 'SELECT s.sms_account as did FROM key_store k1 
	  JOIN key_store k2 ON (k1.id=k2.id AND k1.type=k2.type AND k1.type="CAMPAIGNS" 
	  AND k2.item="groupowner" AND k1.item="type") JOIN key_store k3 ON 
	  (k3.id=k2.id AND k3.type=k2.type AND k3.item="lastsend") 
	  JOIN call_results c on (c.campaignid=k1.id) JOIN sms_sent s 
	  ON (s.eventid=c.eventid) WHERE k2.value in (' .
	  implode(',', array_fill(0, count($groupids), '?')) .
	  ') AND k1.value="sms" AND k3.`value`>=? AND c.value="SENT" AND c.timestamp between ? AND ?';

	$rs = api_db_query_read(
		$sql,
		array_merge($groupids, [$start_timestamp, $start, $end])
	);

	if (!$rs) {
		return false;
	}

	return _api_reports_get_overall_sms_volumes($did_map, $rs);
}

function api_reports_overall_sms_volume_from_legacy_api(
	array $users,
	array &$did_map,
	DateTime $start_time,
	DateTime $end_time
) {
	$start = $start_time->format('Y-m-d H:i:s');
	$end = $end_time->format('Y-m-d H:i:s');

	$sql = 'SELECT ss.sms_account as did FROM `sms_api_mapping` sa JOIN `sms_sent` ss 
	  ON (sa.rid=ss.eventid) WHERE sa.userid in (' .
	  implode(',', array_fill(0, count($users), '?')) .
	  ') AND ss.timestamp BETWEEN ? AND ?';

	$rs = api_db_query_read(
		$sql,
		array_merge($users, [$start, $end])
	);

	if (!$rs) {
		return false;
	}

	return _api_reports_get_overall_sms_volumes($did_map, $rs);
}

function api_reports_overall_sms_volume_from_rest_api(
	array $users,
	DateTime $start_time,
	DateTime $end_time
) {
	$start = $start_time->format('Y-m-d H:i:s');
	$end = $end_time->format('Y-m-d H:i:s');

	$sql = 'SELECT IF(`from`/`from`, "numeric", "alpha") AS `name`, count(*) as count 
	  FROM `sms_out` WHERE userid IN (' .
	  implode(',', array_fill(0, count($users), '?')) .
	  ') AND timestamp between ? and ? GROUP BY `name`';

	$rs = api_db_query_read(
		$sql,
		array_merge($users, [$start, $end])
	);

	if (!$rs) {
		return false;
	}

	$numeric = 0;
	$alphacode = 0;

	if ($rs->RecordCount() > 0) {
		foreach ($rs->GetAssoc() as $name => $value) {
			if ($name === 'numeric') {
				$numeric += $value;
				continue;
			}
			$alphacode += $value;
		}
	}

	return ['alphacode' => $alphacode, 'numeric' => $numeric];
}

function api_reports_overall_answered_call_volume(
	array $groupids = [],
	DateTime $start_time,
	DateTime $end_time,
	$excludeAnsweringMachine = true
) {
	$start = $start_time->format('Y-m-d H:i:s');
	$end = $end_time->format('Y-m-d H:i:s');
	$start_timestamp = (new DateTime($start))->getTimestamp();

	$sql = 'SELECT count(DISTINCT c.eventid) as `count` FROM `call_results` c ' .
	($excludeAnsweringMachine ? 'JOIN response_data r ON (c.eventid=r.eventid AND c.targetid=r.targetid 
	AND c.campaignid=r.campaignid AND r.action="0_AMD")' : '') .
	' WHERE c.campaignid IN (SELECT k1.id FROM key_store k1 ' .
	($groupids ? 'JOIN key_store k2 ON 
	(k1.id=k2.id AND k1.type=k2.type AND k2.item="groupowner" AND k1.type="CAMPAIGNS" AND k1.item="type") ' : '') .
	'JOIN key_store k3 ON (k3.id=k1.id AND k3.type=k1.type AND k3.item="lastsend" AND k1.type="CAMPAIGNS" AND k1.item="type") 
	WHERE ' .
	($groupids ? 'k2.value IN (' . implode(',', array_fill(0, count($groupids), '?')) . ') AND ' : '') .
	'k1.value="phone" AND k3.value>=?) AND c.timestamp BETWEEN ? AND ? AND c.value="ANSWER"' .
	($excludeAnsweringMachine ? ' AND r.value="HUMAN"' : '');

	$rs = api_db_query_read(
		$sql,
		array_merge($groupids, [$start_timestamp, $start, $end])
	);

	if (!$rs) {
		return false;
	}

	return $rs->RecordCount() ? $rs->FetchRow('count')['count'] : 0;
}

/**
 * This returns the total calls attempted.
 * By default only returns results of calls were made. I.e If the call generated but had issues, it won't be counted as attempt
 * Contains a switch to return the inverse: $only_hard_failures=true only calls that failed due to connection issues
 *
 * @param array $groupids
 * @param DateTime $start_time
 * @param DateTime $end_time
 * @param bool $only_hard_failures Only return results that completely failed due to "CHANUNAVAIL", "DISCONNECTED", "CONGESTION", etc
 * @return bool|int
 * @throws Exception
 */
function api_reports_total_call_attempts(
    array $groupids,
    DateTime $start_time,
    DateTime $end_time,
	$only_hard_failures = false
) {
    $start = $start_time->format('Y-m-d H:i:s');
    $end = $end_time->format('Y-m-d H:i:s');
    $start_timestamp = (new DateTime($start))->getTimestamp();

    $sql = 'SELECT count(DISTINCT c.eventid) as `count` FROM `call_results` c 
    LEFT JOIN call_results c1 ON (c.campaignid=c1.campaignid AND c.targetid=c1.targetid 
    AND c.eventid=c1.eventid AND c1.value IN ("CHANUNAVAIL", "DISCONNECTED", "CONGESTION")) 
    WHERE c.campaignid IN (SELECT k1.id FROM key_store k1 JOIN key_store k2 ON 
	(k1.id=k2.id AND k1.type=k2.type AND k1.type="CAMPAIGNS" AND k2.item="groupowner" AND k1.item="type") 
	JOIN key_store k3 ON (k3.id=k2.id AND k3.type=k2.type AND k3.item="lastsend") 
	WHERE k2.value IN (' . implode(',', array_fill(0, count($groupids), '?')) . ') AND 
	k1.value="phone" AND k3.value>=?) AND c.timestamp BETWEEN ? AND ? ';

    if ($only_hard_failures) {
	    $sql .= 'AND c1.resultid is not null';
    }else{
	    $sql .= 'AND c1.resultid is null';
    }

    $rs = api_db_query_read(
        $sql,
        array_merge($groupids, [$start_timestamp, $start, $end])
    );

    if (!$rs) {
        return false;
    }

    return $rs->RecordCount() ? $rs->FetchRow('count')['count'] : 0;
}

function api_reports_total_sms_delivered_from_campaign(
	array $groupids,
	DateTime $start_time,
	DateTime $end_time
) {
	$start = $start_time->format('Y-m-d H:i:s');
	$end = $end_time->format('Y-m-d H:i:s');
	$start_timestamp = (new DateTime($start))->getTimestamp();

	$sql = 'SELECT count(*) as `count` FROM key_store k1 
	  JOIN key_store k2 ON (k1.id=k2.id AND k1.type=k2.type AND k1.type="CAMPAIGNS" 
	  AND k2.item="groupowner" AND k1.item="type") JOIN key_store k3 ON 
	  (k3.id=k2.id AND k3.type=k2.type AND k3.item="lastsend") 
	  JOIN call_results c on (c.campaignid=k1.id) JOIN sms_status s 
	  ON (s.eventid=c.eventid) WHERE k2.value in (' .
	  implode(',', array_fill(0, count($groupids), '?')) .
	  ') AND k1.value="sms" AND k3.`value`>=? AND c.value="SENT" AND s.status="DELIVERED" AND c.timestamp BETWEEN ? AND ?';

	$rs = api_db_query_read(
		$sql,
		array_merge($groupids, [$start_timestamp, $start, $end])
	);

	if (!$rs) {
		return false;
	}

	return $rs->RecordCount() ? $rs->FetchRow('count')['count'] : 0;
}

function api_reports_total_sms_delivered_from_legacy_api(
	array $users,
	DateTime $start_time,
	DateTime $end_time
) {
	$start = $start_time->format('Y-m-d H:i:s');
	$end = $end_time->format('Y-m-d H:i:s');

	$sql = 'SELECT count(*) AS `count` FROM `sms_api_mapping` sa
	JOIN `sms_status` ss ON (ss.eventid=sa.rid) WHERE 
	ss.status="DELIVERED" AND sa.timestamp BETWEEN ? AND ? AND sa.userid IN (' .
	implode(',', array_fill(0, count($users), '?')) . ')';

	$rs = api_db_query_read(
		$sql,
		array_merge([$start, $end], $users)
	);

	if (!$rs) {
		return false;
	}

	return $rs->RecordCount() ? $rs->FetchRow('count')['count'] : 0;
}

function api_reports_total_sms_delivered_from_rest_api(
	array $users,
	DateTime $start_time,
	DateTime $end_time
) {
	$start = $start_time->format('Y-m-d H:i:s');
	$end = $end_time->format('Y-m-d H:i:s');

	$sql = 'SELECT count(*) AS `count` FROM sms_out s JOIN sms_out_status ss ON (s.id=ss.id)
	WHERE ss.status="delivered" AND s.timestamp BETWEEN ? AND ? AND s.userid IN (' .
	implode(',', array_fill(0, count($users), '?')) . ')';

	$rs = api_db_query_read(
		$sql,
		array_merge([$start, $end], $users)
	);

	if (!$rs) {
		return false;
	}

	return $rs->RecordCount() ? $rs->FetchRow('count')['count'] : 0;
}

function api_reports_get_sms_responses_count(
	$sms_did_name,
	array $responses,
	DateTime $start_time,
	DateTime $end_time
) {
	$start = $start_time->format('Y-m-d H:i:s');
	$end = $end_time->format('Y-m-d H:i:s');

	$did = api_sms_dids_nametoid($sms_did_name);

	if ($did === false) {
		throw new Exception('Invalid sms did name passed');
	}

	$sql = 'SELECT count(*) as `count` FROM sms_received WHERE sms_account=? AND contents IN (' .
		implode(',' , array_fill(0, count($responses), '?'))
		. ') AND timestamp BETWEEN ? AND ?';

	$rs = api_db_query_read($sql, array_merge([$did], $responses, [$start, $end]));

	if (!$rs) {
		return false;
	}

	return $rs->RecordCount() ? $rs->FetchRow('count')['count'] : 0;
}

function api_reports_get_voice_responses_count(
	array $groupids,
	array $actions,
	DateTime $start_time,
	DateTime $end_time
) {
	$start = $start_time->format('Y-m-d H:i:s');
	$end = $end_time->format('Y-m-d H:i:s');
	$start_timestamp = (new DateTime($start))->getTimestamp();

	if (!count($actions)) {
		return false;
	}

	if (count($actions) === 1) {
		$sql = 'SELECT count(distinct r.targetid) as `count` FROM response_data r WHERE 
			r.campaignid IN (SELECT k1.id FROM key_store k1 JOIN key_store k2 ON 
			(k1.id=k2.id AND k1.type=k2.type AND k1.type="CAMPAIGNS" AND k2.item="groupowner" AND k1.item="type") 
			JOIN key_store k3 ON (k3.id=k2.id AND k3.type=k2.type AND k3.item="lastsend") 
			WHERE k2.value IN (' . implode(',', array_fill(0, count($groupids), '?')) . ') AND 
			k1.value="phone" AND k3.value>=?) AND r.action=? AND r.`value` IN (' .
			implode(',', array_fill(0, count($actions[0]['value']), '?'))
			. ') AND r.timestamp BETWEEN ? AND ?';

		$rs = api_db_query_read(
			$sql,
			array_merge($groupids, [$start_timestamp], [$actions[0]['action']], $actions[0]['value'], [$start, $end])
		);
	} else {
		$sql = 'SELECT count(distinct r.targetid) as `count` FROM response_data r';
		$joinSql = ' JOIN response_data %s ON (r.campaignid=%s.campaignid AND r.targetid=%s.targetid AND 
			r.action=? AND r.`value` IN (' .
			implode(',', array_fill(0, count($actions[0]['value']), '?'))
			. ') AND %s.action=? AND %s.value IN (%s))';

		$params = [];
		for ($i = 1; $i < count($actions); $i++) {
			$alias = 'r' . $i;
			$sql .= sprintf(
				$joinSql,
				$alias,
				$alias,
				$alias,
				$alias,
				$alias,
				implode(',', array_fill(0, count($actions[$i]['value']), '?'))
			);

			$params = array_merge(
				[$actions[0]['action']],
				$actions[0]['value'],
				[$actions[$i]['action']],
				$actions[$i]['value']
			);
		}

		$sql .= ' WHERE r.campaignid IN (
			SELECT k1.id FROM key_store k1 JOIN key_store k2 ON 
			(k1.id=k2.id AND k1.type=k2.type AND k1.type="CAMPAIGNS" AND k2.item="groupowner" AND k1.item="type") 
			JOIN key_store k3 ON (k3.id=k2.id AND k3.type=k2.type AND k3.item="lastsend") 
			WHERE k2.value IN (' . implode(',', array_fill(0, count($groupids), '?')) . ') AND 
			k1.value="phone" AND k3.value>=?
		) AND r.timestamp BETWEEN ? AND ? ';

		$params = array_merge($params, $groupids, [$start_timestamp, $start, $end]);

		for ($i = 1; $i < count($actions); $i++) {
			$alias = 'r' . $i;
			$sql .= sprintf(' AND %s.timestamp BETWEEN ? AND ? ', $alias);
			$params = array_merge($params, [$start, $end]);
		}

		$rs = api_db_query_read(
			$sql,
			$params
		);
	}

	if (!$rs) {
		return false;
	}

	return $rs->RecordCount() ? $rs->FetchRow('count')['count'] : 0;
}

function api_reports_rest_api_sms_report(array $users, DateTime $start, DateTime $end, $return_user_name = true, $status_start = null, $status_end = null)
{
	if($status_start == null || $status_end == null) {
		$sql = 'SELECT s.`timestamp` as `timestamp`, ss.`timestamp` as `status_timestamp`, s.`userid` as userid, s.`from` as `from`, 
		s.`destination` as `destination`, s.`message`, IF(ss.`status` is null, "SENT", ss.`status`) as `status`
		FROM `sms_out` s LEFT JOIN `sms_out_status` ss ON (s.`id` = ss.`id`) 
		WHERE s.`timestamp` BETWEEN ? AND ? AND s.`userid` IN (';
		$sql .= implode(',', array_fill(0, count($users), '?')) . ')';

		$rs = api_db_query_read(
			$sql,
			array_merge([$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')], $users)
		);
	} else {
		$sql = 'SELECT s.`timestamp` as `timestamp`, ss.`timestamp` as `status_timestamp`, s.`userid` as userid, s.`from` as `from`, 
    s.`destination` as `destination`, s.`message`, IF(ss.`status` is null, "SENT", ss.`status`) as `status`
		FROM `sms_out` s LEFT JOIN `sms_out_status` ss ON (s.`id` = ss.`id`) 
		WHERE s.`timestamp` BETWEEN ? AND ? AND ss.`timestamp` BETWEEN ? AND ? AND s.`userid` IN (';
		$sql .= implode(',', array_fill(0, count($users), '?')) . ')';

		$rs = api_db_query_read(
			$sql,
			array_merge([$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $status_start->format('Y-m-d H:i:s'), $status_end->format('Y-m-d H:i:s')], $users)
		);
	}    

    if (!$rs) {
        return api_error_raise('Invalid sql generated for rest api sms report.');
    }

    $data = $rs->GetArray();

    if (!$return_user_name) {
        return $data;
    }

    $userdata = [];

    foreach ($users as $id) {
        $userdata[$id] = api_users_setting_getsingle($id, 'username');
    }

    array_walk($data, function(&$value) use ($userdata) {
        $value['username'] = $userdata[$value['userid']];
    });

    return $data;
}

function _api_reports_get_overall_sms_volumes(array &$dids, $rs)
{
	$numeric = 0;
	$alphacode = 0;

	$smsAccounts = [];
	if ($rs->RecordCount() > 0) {
		foreach ($rs->GetArray() as $array) {
			$smsAccounts[] = $array['did'];
		}

		$smsAccountsToSearch = array_diff(array_unique($smsAccounts), array_keys($dids));

		if ($smsAccountsToSearch) {
			$sql = 'SELECT `id`, `value` from key_store WHERE type="SMSDIDS" AND id in (';
			$sql .= implode(',', array_fill(0, count($smsAccountsToSearch), '?'));
			$sql .= ') AND item="name"';

			api_db_ping();
			$rs = api_db_query_read($sql, $smsAccountsToSearch);

			if ($rs && $rs->RecordCount()) {
				foreach ($rs->GetAssoc() as $id => $value) {
					$dids[$id] = $value;
				}
			}
		}

		foreach ($smsAccounts as $did) {
			if (!isset($dids[$did])) {
				print "SMS sent from an unknown did and so is ignored. Did id is " . $did . "\n";
				continue;
			}
			if (is_numeric($dids[$did])) {
				$numeric += 1;
				continue;
			}
			$alphacode += 1;
		}
	}

	return ['alphacode' => $alphacode, 'numeric' => $numeric];
}


function api_reports_overall_actioned_contacts_per_destination(
	array $groupids,
	DateTime $start_time,
	DateTime $end_time,
	$minAttempts,
	array $voice_call_actions
) {

	$users = api_users_list_by_groupowners_last_login($groupids, $start_time);

	$userids = !empty($users) ? array_keys($users) : [];

	$start = $start_time->format('Y-m-d H:i:s');
	$end = $end_time->format('Y-m-d H:i:s');

	$start_timestamp = (new DateTime($start))->getTimestamp();

	// Subquery to extract SMS contacts that belong to a campaign
	$campaign_sms_sql = _api_reports_get_campaign_contact_sql($groupids);

	// Only get api sms's if there is a user id
	if($userids) {
		// Subquery to extract SMS contacts sent through the old api
		$old_api_sql = _api_reports_get_old_api_sms_contact_sql($userids);
		// Subquery to extract SMS's sent through the rest api
		$rest_sql = _api_reports_get_rest_api_sms_contacts_sql($userids);
	}

	// Subquery to extract voice calls
	$voice_calls_sql = _api_reports_get_voice_response_contacts_sql($groupids, $voice_call_actions);

	// Query that selects all contacts made to the given group IDs
	$sql = "SELECT cleaned_destination, sum(attempts) as total_attempts 
		FROM 
		(($campaign_sms_sql)\n" ;

	if(!empty($userids)) { // No users means no api sms's
		$sql .= " UNION ALL ($rest_sql)\n";
		$sql .= " UNION ALL ($old_api_sql)\n";
	}

	$sql .= " UNION ALL ($voice_calls_sql)\n";

	$sql .= ") t
		GROUP BY cleaned_destination
		HAVING sum(attempts) >= ?
		ORDER BY total_attempts DESC
		";

	$action_params = [];
	foreach($voice_call_actions as $action){
		$action_params = array_merge($action_params, [$action['action']], $action['value']);
	}

	if($userids) {
		$params = array_merge(
			$groupids, [$start_timestamp, $start, $end], //campaign sms
			[$start, $end], $userids ? $userids : [], //old api sms
			[$start, $end], $userids ? $userids : [], // rest api sms
			[$start_timestamp], $groupids, $action_params, [$start, $end], //voice calls
			[$minAttempts]
		);
	}else{
		$params = array_merge(
			$groupids, [$start_timestamp, $start, $end], //campaign sms
			[$start_timestamp], $groupids, $action_params, [$start, $end] , //voice calls
			[$minAttempts]
		);
	}

	$rs = api_db_query_read($sql, $params);

	if (!$rs) {
		return false;
	}

	return $rs && $rs->RecordCount() ? $rs->GetRows() : [];
}

/**
 * Retrieves all successful connections made by the given groupids in the given
 * period to a destination that exceed $minAttempts contact attempts
 *
 * E.g if minAttempts = 10 and the groupids contacted a destination 041111111 12 times via voice and sms the
 * result would be
 * [cleaned_destination => 0411111111.
 * total_attempts => 12]
 *
 * @param array $groupids
 * @param DateTime $start_time
 * @param DateTime $end_time
 * @param $minAttempts
 * @return an|array|bool
 * @throws Exception
 */
function api_reports_overall_contacts_per_destination(
	array $groupids,
	DateTime $start_time,
	DateTime $end_time,
	$minAttempts
) {

	$users = api_users_list_by_groupowners_last_login($groupids, $start_time);
	if(!$users){
		return [];
	}
	$userids = array_keys($users);

	$start = $start_time->format('Y-m-d H:i:s');
	$end = $end_time->format('Y-m-d H:i:s');

	$start_timestamp = (new DateTime($start))->getTimestamp();

	// Subquery to extract SMS contacts that belong to a campaign
	$campaign_sms_sql = _api_reports_get_campaign_contact_sql($groupids);
	// Subquery to extract SMS contacts sent through the old api
	$old_api_sql = _api_reports_get_old_api_sms_contact_sql($userids);
	// Subquery to extract SMS's sent through the rest api
	$rest_sql = _api_reports_get_rest_api_sms_contacts_sql($userids);
	// Subquery to extract voice calls
	$voice_calls_sql = _api_reports_get_voice_calls_contacts_sql($groupids);

	// Query that selects all contacts made to the given group IDs
	$sql = "SELECT cleaned_destination, sum(attempts) as total_attempts 
		FROM 
		(
		($campaign_sms_sql) 
		UNION ALL ($rest_sql)
		UNION ALL ($old_api_sql)
		UNION ALL ($voice_calls_sql)
		) t
		GROUP BY cleaned_destination
		HAVING sum(attempts) >= ?
		ORDER BY total_attempts DESC
		";

	//api_db_debug_on();
	$params = array_merge(
		$groupids, [$start_timestamp, $start, $end], //campaign sms
		[$start, $end],$userids, //old api sms
		[$start, $end],$userids, // rest api sms
		$groupids, [$start_timestamp, $start, $end], //voice calls
		[$minAttempts]
	);

	$rs = api_db_query_read($sql, $params);

	if (!$rs) {
		return false;
	}

	return $rs && $rs->RecordCount() ? $rs->GetRows() : [];
}

/**
 * Generates SQL to extract the total contacts the given group ids have made to
 * destinations through campaign SMS's
 *
 * @param $groupids
 * @return string
 */
function _api_reports_get_campaign_contact_sql(array $groupids){

	return 'SELECT
					count(*) as attempts,	
					IF(SUBSTRING(TRIM(LEADING "+" FROM destination), 1, 2) = "61",
					    CONCAT("0", SUBSTRING(TRIM(LEADING "+" FROM destination), 3)),					
					destination) as cleaned_destination,
					"campaign_sms" as source
				FROM
					key_store k1
				JOIN key_store k2 ON
						(k1.id = k2.id
							AND k1.type = k2.type
							AND k1.type = "CAMPAIGNS"
							AND k2.item = "groupowner"
							AND k1.item = "type")
				JOIN key_store k3 ON
						(k3.id = k2.id
							AND k3.type = k2.type
							AND k3.item = "lastsend")
				JOIN call_results c on
						(c.campaignid = k1.id)
				JOIN sms_status s ON
						(s.eventid = c.eventid)
				JOIN targets t ON
						(c.targetid = t.targetid)	
				WHERE
					k2.value in (' .implode(',', array_fill(0, count($groupids), ' ? ')) .')
				AND k1.value = "sms"
				AND k3.`value` >=?
				AND c.value = "SENT"
				AND s.status = "DELIVERED"
				AND c.timestamp BETWEEN ? AND ?				
		GROUP BY destination';
}

/**
 * Generates SQL to extract the total contacts the given group ids have made to
 * destinations through the old SMS API
 *
 * @param $groupids
 * @return string
 */
function _api_reports_get_old_api_sms_contact_sql(array $userids){
	return 'SELECT
					count(*) as attempts,	
					IF(SUBSTRING(TRIM(LEADING \'+\' FROM `to`), 1, 2) = "61",
					replace(TRIM(LEADING \'+\' FROM `to`),
					"61",
					"0"),
					`to`) as cleaned_destination,
					"old_api" as source			
				FROM
					`sms_api_mapping` sa
				JOIN `sms_status` ss ON
						(ss.eventid = sa.rid)
				JOIN sms_sent sms ON (sms.eventid = ss.eventid)
				WHERE
					ss.status = "DELIVERED"
					AND sa.timestamp BETWEEN ? AND ?
					AND sa.userid IN (' .implode(',', array_fill(0, count($userids), ' ? ')) . ')
				GROUP BY cleaned_destination';
}

/**
 *
 * Generates SQL to extract the total contacts the given group ids have made to
 * destinations through the REST SMS API
 *
 * @param $groupids
 * @return string
 */
function _api_reports_get_rest_api_sms_contacts_sql(array $userids){
	return 'SELECT
					count(*) as attempts,	
					IF(SUBSTRING(TRIM(LEADING "+" FROM destination), 1, 2) = "61",
					    CONCAT("0", SUBSTRING(TRIM(LEADING "+" FROM destination), 3)),
					    destination) as cleaned_destination,
					"rest_api" as source
				FROM
					sms_out s
				JOIN sms_out_status ss ON
					(s.id = ss.id)
				WHERE
					ss.status = "delivered"
					AND s.timestamp BETWEEN ? AND ?
					AND s.userid IN (' .implode(',', array_fill(0, count($userids), ' ? ')) . ')					
				GROUP BY cleaned_destination' ;
}

/**
 * Generates SQL to extract the total contacts the given group ids have made to
 * destinations through voice calls
 *
 * @param $groupids
 * @return string
 */
function _api_reports_get_voice_calls_contacts_sql(array $groupids){
	return 'SELECT
				count(DISTINCT c.eventid) as attempts,	
				IF(SUBSTRING(TRIM(LEADING "+" FROM destination), 1, 2) = "61",
				    CONCAT("0", SUBSTRING(TRIM(LEADING "+" FROM destination), 3)),				
				destination) as cleaned_destination,
				"rest_api" as source
			FROM
				`call_results` c
			LEFT JOIN call_results c1 ON
				(c.campaignid = c1.campaignid
					AND c.targetid = c1.targetid
					AND c.eventid = c1.eventid
					AND c1.value IN ("CHANUNAVAIL",
					"DISCONNECTED",
					"CONGESTION"))
			JOIN `targets` t ON (t.targetid = c.targetid)	
			WHERE
				c.campaignid IN (
					SELECT
					k1.id
				FROM
					key_store k1
				JOIN key_store k2 ON
				(k1.id = k2.id
					AND k1.type = k2.type
					AND k1.type = "CAMPAIGNS"
					AND k2.item = "groupowner"
					AND k1.item = "type")
				JOIN key_store k3 ON
				(k3.id = k2.id
					AND k3.type = k2.type
					AND k3.item = "lastsend")
				WHERE
					k2.value IN (' . implode(',', array_fill(0, count($groupids), ' ? ')) . ')
				AND k1.value = "phone"
				AND k3.value >=?)
				AND c.timestamp BETWEEN ? AND ?
				AND c1.resultid is null
			GROUP BY cleaned_destination ';
}

/**
 * @param array $groupids
 * @param array $actions
 * @return string
 */
function _api_reports_get_voice_response_contacts_sql(array $groupids, array $actions) {
	$action_params = [];
	$action_sql = "";
	$action_count = count($actions);
	for ($i = 0; $i < $action_count; $i++) {
		$action_sql .= "(r.action = ? AND r.value IN (" . implode(
				',', array_fill(0, count($actions[$i]['value']), '?')
			) . "))";
		if ($i != $action_count - 1) {
			$action_sql .= " OR ";
		}
		$action_params = array_merge($action_params, [$actions[$i]['action']], $actions[$i]['value']);
	}

	$sql = 'SELECT
			count(distinct eventid) as attempts,	
					IF(SUBSTRING(TRIM(LEADING "+" FROM destination), 1, 2) = "61",
					    CONCAT("0", SUBSTRING(TRIM(LEADING "+" FROM destination), 3)),
					destination) as cleaned_destination,			
			"actioned_voice_call" as source
	FROM
		response_data r
	JOIN targets t ON t.targetid = r.targetid	
	WHERE
		r.campaignid IN (
		SELECT
			k1.id
		FROM
			key_store k1
		JOIN key_store k2 ON
			(k1.id = k2.id
			AND k1.type = k2.type
			AND k1.type = "CAMPAIGNS"
			AND k2.item = "groupowner"
			AND k1.item = "type")
		JOIN key_store k3 ON
			(k3.id = k2.id
			AND k3.type = k2.type
			AND k3.item = "lastsend"
			AND k3.value > ?
			)
		WHERE
			k2.value IN (' . implode(',', array_fill(0, count($groupids), '?')) . ')
			AND k1.value = "phone")
		AND (' . $action_sql . ')
		AND r.timestamp BETWEEN ? AND ?
		GROUP BY destination';
	return $sql;
}

/**
 * @param array    $groupids
 * @param DateTime $start
 * @param DateTime $end
 * @return [
 *      'outbound' => ['billable' => integer, 'raw' => integer],
 *      'callback' => ['billable' => integer, 'raw' => integer]
 * ]
 */
function api_reports_get_total_call_duration(
    array $groupids = [],
    DateTime $start,
    DateTime $end
) {
    $return['outbound'] = api_reports_get_outbound_call_duration($groupids, $start, $end);
    $return['callback'] = api_reports_get_callback_duration($groupids, $start, $end);
    return $return;
}

function api_reports_get_outbound_call_duration(
    array $groupids = [],
    DateTime $start,
    DateTime $end
) {
    $return = [];
    $outboundSql = 'SELECT sum(if((UNIX_TIMESTAMP(c2.timestamp)-UNIX_TIMESTAMP(COALESCE(c3.timestamp, c1.timestamp)))<k6.value, k6.value, (UNIX_TIMESTAMP(c2.timestamp)-UNIX_TIMESTAMP(COALESCE(c3.timestamp, c1.timestamp))))) as billable,
        sum((UNIX_TIMESTAMP(c2.timestamp)-UNIX_TIMESTAMP(COALESCE(c3.timestamp, c1.timestamp)))) as raw
        FROM call_results c1 LEFT JOIN call_results c2 ON (c1.eventid=c2.eventid AND c1.campaignid=c2.campaignid AND c1.targetid=c2.targetid AND c1.value="GENERATED" AND c2.value="HANGUP") 
        LEFT JOIN call_results c3 ON (c1.eventid=c3.eventid AND c1.campaignid=c3.campaignid AND c1.targetid=c3.targetid AND c1.value="GENERATED" AND c3.value="ANSWER") 
        JOIN key_store k5 ON (k5.id=c1.campaignid AND k5.type="CAMPAIGNS" AND k5.item="groupowner") 
        JOIN key_store k6 ON (k6.id=k5.value AND k6.type="GROUPS" AND k6.item="firstinterval") 
        WHERE c1.campaignid IN (SELECT k1.id FROM key_store k1 JOIN key_store k3 ON (k3.id=k1.id AND k3.type=k1.type AND k3.item="lastsend" AND k1.item="type" AND k1.type="CAMPAIGNS") WHERE k1.value="phone" AND k3.value>=?) 
        AND c1.timestamp BETWEEN ? AND ? AND c1.value="GENERATED"';

    $params = [$start->getTimestamp(), $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];

    if ($groupids) {
        $outboundSql .= ' AND k6.id in (' . implode(",",array_fill(0, count($groupids), "?")) . ')';
        $params = array_merge($params, $groupids);
    }

    api_db_switch_connection(null, null, null, DB_MYSQL_READ_HOST_FORCED);
    $rs = api_db_query_read($outboundSql, $params);

    if ($rs && $rs->RecordCount()) {
        $return = $rs->FetchRow();
    }

    api_db_reset_connection();
    return $return;
}

function api_reports_get_callback_duration(
    array $groupids = [],
    DateTime $start,
    DateTime $end
) {
    $return = ['billable' => 0, 'raw' => 0];
    $sql = 'SELECT sum(if(coalesce(r.value,0) < cast(k2.value as unsigned), k2.value, coalesce(r.value,0))) as billable, sum(coalesce(r.value,0)) as raw 
        FROM response_data r JOIN key_store k1 ON (k1.id=r.campaignid AND k1.type="CAMPAIGNS" AND k1.item="groupowner") 
        JOIN key_store k2 ON (k2.id=k1.value AND k2.type="GROUPS" AND k2.item="firstinterval") 
        WHERE r.action="%s" AND r.campaignid IN (SELECT k1.id FROM key_store k1 
        JOIN key_store k3 ON (k3.id=k1.id AND k3.type=k1.type AND k3.item="lastsend" AND k1.item="type" AND k1.type="CAMPAIGNS") 
        WHERE k1.value="phone" AND k3.value>=?) 
        AND r.timestamp BETWEEN ? AND ?';

    $params = [$start->getTimestamp(), $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];

    if ($groupids) {
        $sql .= ' AND k2.id in (' . implode(",",array_fill(0, count($groupids), "?")) . ')';
        $params = array_merge($params, $groupids);
    }

    api_db_switch_connection(null, null, null, DB_MYSQL_READ_HOST_FORCED);

    foreach (['CALLBACK_TRANSDUR', '1_TRANSDUR', '2_TRANSDUR'] as $transdur) {
        $rs = api_db_query_read(sprintf($sql, $transdur), $params);

        if ($rs && $rs->RecordCount()) {
            $row = $rs->FetchRow();
            $return['billable'] += $row['billable'];
            $return['raw'] += $row['raw'];
        }
    }

    api_db_reset_connection();
    return $return;
}
