<?php
/**
 * Keystore Functions
 *
 * @author			nick.adams@reachtel.com.au
 * @copyright		ReachTel (ABN 40 133 677 933)
 * @testCoverage	full
 */

use Services\ActivityLogger;
use Services\Utils\ActivityLoggerActions;

/**
 * Get keystore value
 *
 * @param string  $type
 * @param integer $id
 * @param string  $item
 * @return string|false
 */
function api_keystore_get($type, $id, $item) {

	$sql = "SELECT `value` FROM `key_store` WHERE `type` = ? AND `id` = ? AND `item` = ?";
	$rs = api_db_query_read($sql, array($type, $id, $item));

	if ($rs && ($rs->RecordCount() > 0)) {
		return $rs->Fields("value");
	} else {
		return false;
	}
}

/**
 * Get keystore value by ids
 *
 * @param string $type
 * @param array  $ids
 * @param string $item
 * @return array
 */
function api_keystore_get_multi_byid($type, array $ids, $item) {

	$result = array();

	if (count($ids) == 0) {
		return $result;
	}

	if (empty($item)) {
		return $result;
	}

	$arguments = array($type, $item);

	$sql = "SELECT `id`, `value` FROM `key_store` WHERE `type` = ? AND `item` = ? AND `id` IN ( ";

	foreach ($ids as $id) {
		$sql .= "?,";
		$arguments[] = $id;
	}

	$sql = substr($sql, 0, -1) . ")";

	$rs = api_db_query_read($sql, $arguments);

	if ($rs) {
		$rows = $rs->GetArray();
		foreach ($rows as $row) {
			$result[$row["id"]] = $row["value"];
		}
	}

	return $result;
}

/**
 * Get keystore value by items
 *
 * @param string $type
 * @param string $id
 * @param array  $items
 * @return array
 */
function api_keystore_get_multi_byitem($type, $id, array $items) {

	$result = array();

	if (!is_numeric($id)) {
		return $result;
	}

	if (count($items) == 0) {
		return $result;
	}

	$arguments = array($type, $id);

	$sql = "SELECT `item`, `value` FROM `key_store` WHERE `type` = ? AND `id` = ? AND `item` IN ( ";

	foreach ($items as $item) {
		$sql .= "?,";
		$arguments[] = $item;
	}

	$sql = substr($sql, 0, -1) . ")";

	$rs = api_db_query_read($sql, $arguments);

	if ($rs) {
		$rows = $rs->GetArray();

		foreach ($rows as $row) {
			$result[$row["item"]] = $row["value"];
		}
	}

	return $result;
}

/**
 * Set a keystore value
 *
 * @param string  $type
 * @param integer $id
 * @param string  $item
 * @param mixed   $value
 * @return boolean
 */
function api_keystore_set($type, $id, $item, $value) {

	if (!is_numeric($id)) {
		return false;
	}

	if (empty($item)) {
		return false;
	}

	if (!is_string($type)) {
		return false;
	}

	$sql = "INSERT INTO `key_store` (`type`, `id`, `item`, `value`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = ?";
	$rs = api_db_query_write($sql, array($type, $id, $item, $value, $value));

	if ($rs) {
		ActivityLogger::getInstance()->addLog(
			$type,
			ActivityLoggerActions::ACTION_UPDATE_SETTINGS,
			$item . ' : ' . $value,
			$id,
			null,
			$item
		);

		return true;
	}

	return false;
}

/**
 * Check and set a keystore value
 *
 * @param string  $type
 * @param integer $id
 * @param string  $item
 * @param mixed   $check
 * @param mixed   $value
 * @return boolean
 */
function api_keystore_cas($type, $id, $item, $check, $value) {

	$sql = "UPDATE `key_store` SET `value` = ? WHERE `type` = ? AND `id` = ? AND `item` = ? AND `value` = ?";
	$rs = api_db_query_write($sql, array($value, $type, $id, $item, $check));

	if ($rs && api_db_affectedrows() > 0) {
		ActivityLogger::getInstance()->addLog(
			$type,
			ActivityLoggerActions::ACTION_UPDATE_SETTINGS,
			$item . ' : ' . $value,
			$id,
			null,
			$item
		);

		return true;
	}

	return false;
}

/**
 * Delete a keystore value
 *
 * @param string  $type
 * @param integer $id
 * @param string  $item
 * @return boolean
 */
function api_keystore_delete($type, $id, $item) {

	if (!is_numeric($id)) {
		return false;
	}

	if (empty($item)) {
		return false;
	}

	$sql = "DELETE FROM `key_store` WHERE `type` = ? AND `id` = ? AND `item` = ?";
	$rs = api_db_query_write($sql, array($type, $id, $item));

	if ($rs && api_db_affectedrows() > 0) {
		ActivityLogger::getInstance()->addLog(
			$type,
			ActivityLoggerActions::ACTION_REMOVE_SETTINGS,
			$item,
			$id,
			null,
			$item
		);
		return true;
	}

	return false;
}

/**
 * Increment a keystore value
 *
 * @param string  $type
 * @param integer $id
 * @param string  $item
 * @return integer|false
 */
function api_keystore_increment($type, $id, $item) {

	if (!is_numeric($id)) {
		return false;
	}

	// Wrapping this in a transaction to enforce locking on the key_store and prevent a race condition
	// between updating the row in mysql and php calling api_db_lastid - a period of time long enough
	// in which another process can update the record
	if (api_db_starttrans() === false) {
		api_error_raise("KEYSTORE ID GENERATOR: Could not start a transaction");
		return false;
	}

	$sql = "UPDATE `key_store` SET `value` = LAST_INSERT_ID(`value` + 1) WHERE `type` = ? AND `id` = ? AND `item` = ?";
	$rs = api_db_query_write($sql, array($type, $id, $item));

	if ($rs && api_db_affectedrows()) {
		$last_id = api_db_lastid();
		if (api_db_endtrans() === false) {
			return api_error_raise("KEYSTORE ID GENERATOR: Could not commit the incremented id '{$last_id}' to the keystore");
		}

		return $last_id;
	} else {
		api_db_failtrans();
		api_db_endtrans();
		api_error_raise("KEYSTORE ID GENERATOR: Could not update the keystore id");
		return false;
	}
}

/**
 * Purge keystore values
 *
 * @param string  $type
 * @param integer $id
 * @return boolean
 */
function api_keystore_purge($type, $id) {

	if (!is_numeric($id)) {
		return false;
	}

	$items = api_keystore_getnamespace($type, $id);

	if ($items) {
		foreach ($items as $key => $value) {
			api_keystore_delete($type, $id, $key);
		}
	}

	return true;
}

/**
 * Get namespaces keystore values by ids
 *
 * @param string $type
 * @param array  $ids
 * @param array  $filters
 * @return array|false
 */
function api_keystore_getnamespaces_byids($type, array $ids, array $filters = []) {

	if (!is_string($type)) {
		return false;
	}

	$namespaces = [];

	// get all campaign settings by ids
	$sql = sprintf(
		'SELECT * FROM `key_store` WHERE `type` = ? AND `id` IN (%s);',
		implode(',', array_fill(0, count($ids), '?'))
	);
	$rs = api_db_query_read($sql, array_merge([$type], $ids));

	if ($rs && ($rs->RecordCount() > 0)) {
		foreach ($rs->GetArray() as $record) {
			$namespaces[$record['id']][$record['item']] = $record['value'];
		}

		// apply optional filters
		foreach ($filters as $item => $value) {
			foreach ($namespaces as $id => $namespace) {
				if (!array_key_exists($item, $namespace) || $namespace[$item] !== $value) {
					unset($namespaces[$id]);
				}
			}
		}
	}

	return $namespaces;
}

/**
 * Get namespace keystore values
 *
 * @param string  $type
 * @param integer $id
 * @return array|false
 */
function api_keystore_getnamespace($type, $id) {

	if (!is_numeric($id)) {
		return false;
	}

	if (!is_string($type)) {
		return false;
	}

	$items = [];

	$sql = "SELECT `item`, `value` FROM `key_store` WHERE `type` = ? AND `id` = ?";
	$rs = api_db_query_read($sql, array($type, $id));

	if ($rs) {
		$result = $rs->GetArray();
		foreach ($result as $i) {
			$items[$i["item"]] = $i["value"];
		}

		return $items;
	}

	return false;
}

/**
 * Get entire namespace keystore values
 *
 * @param string $type
 * @return array|false
 */
function api_keystore_getentirenamespace($type) {

	if (!is_string($type)) {
		return false;
	}

	$ids = [];

	$sql = "SELECT `id`, `item`, `value` FROM `key_store` WHERE `type` = ? AND `id` != ?";
	$rs = api_db_query_read($sql, array($type, 0));

	if ($rs) {
		$result = $rs->GetArray();
		foreach ($result as $i) {
			$ids[$i["id"]][$i["item"]] = $i["value"];
		}

		return $ids;
	}

	return false;
}

/**
 * Get all ids
 *
 * @param string  $type
 * @param string  $item
 * @param boolean $ret
 * @return array
 */
function api_keystore_getids($type, $item = "name", $ret = false) {

	$id = [];

	$add_value_field = $ret ? ', `value`' : '';
	$sql = "SELECT `id`{$add_value_field} FROM `key_store` WHERE `type` = ? AND `item` = ? ORDER BY `id` DESC";
	$rs = api_db_query_read($sql, array($type, $item));

	if ($rs && ($rs->RecordCount() > 0)) {
		if ($ret) {
			return $rs->GetAssoc();
		} else {
			$result = $rs->GetArray();

			foreach ($result as $i) {
				$id[] = $i["id"];
			}

			return $id;
		}
	} else {
		return $id;
	}
}

/**
 * Get all keystore id's for a type with certain value
 *
 * @param string $type
 * @param string $item
 * @param mixed  $value
 * @return array|false
 */
function api_keystore_getidswithvalue($type, $item, $value) {

	if (empty($type)) {
		return [];
	}
	if (!is_string($type)) {
		return [];
	}
	if (empty($item)) {
		return [];
	}

	$sql = "SELECT DISTINCT `id` FROM `key_store` USE INDEX (`type`) WHERE `type` = ? AND `item` = ? AND `value` = ? ORDER BY `id` DESC";
	$rs = api_db_query_read($sql, array($type, (string) $item, (string) $value));

	if ($rs) {
		$result = $rs->GetArray();
	} else {
		return false;
	}

	$id = array();

	if (is_array($result)) {
		foreach ($result as $i) {
			if ($i["id"] != 0) {
				$id[] = (integer)$i["id"];
			}
		}
	}

	return $id;
}

/**
 * Check if a keystore key exists
 *
 * @param string $type
 * @param string $item
 * @param mixed  $value
 * @param array  $options
 * @return mixed
 */
function api_keystore_checkkeyexists($type, $item, $value, array $options = []) {

	if (empty($type)) {
		return [];
	}
	if (!is_string($type)) {
		return [];
	}
	if (empty($item)) {
		return [];
	}

	// Allow case sensitive key searches by setting the option 'casesensitive' to true. This forces MySQL to do a binary comparison.
	$binary = (isset($options['casesensitive']) && $options['casesensitive']) ? "BINARY" : "";

	$sql = "SELECT `id` FROM `key_store` USE INDEX (`type`) WHERE `type` = ? AND `item` = ? AND `value` = {$binary} ?";
	$rs = api_db_query_read($sql, array($type, (string) $item, (string) $value));

	if (!$rs) {
		return false;
	} elseif ($rs->RecordCount() === 0) {
		return false;
	} elseif ($rs->RecordCount() === 1) {
		return $rs->Fields("id");
	} else { // RecordCount > 1

		api_misc_audit("KEYSTORE_ERROR", "Check key exists greater than 1; Type=" . $type . "; Item=" . $item . "; Value=" . $value);

		return $rs->Fields("id");
	}
}
