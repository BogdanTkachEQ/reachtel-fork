<?php
/**
 * Billing Functions
 *
 * @author			kevin.ohayon@reachtel.com.au
 * @copyright		ReachTel (ABN 40 133 677 933)
 * @testCoverage	full
 */

define('API_BILLING_PRODUCTS_HYDRATOR_STATUS', 'status');
define('API_BILLING_PRODUCTS_HYDRATOR_BILLING_TYPE', 'billing_type_id');
define('API_BILLING_PRODUCTS_HYDRATOR_BILLING_TYPE_NAME', 'billing_type_name');

define('API_BILLING_TRANSACTION_PROCESSED_YES', 'y');
define('API_BILLING_TRANSACTION_PROCESSED_NO', 'n');
define('BILLING_TYPE_ADHOC_ID', 2);

/**
 * List all products
 *
 * @param string $hydratorKey
 *
 * @return array|false
 */
function api_billing_products_listall($hydratorKey = null) {
	$sql = "SELECT p.*, pc.name AS category_name FROM `billing_products` p
			INNER JOIN `billing_types` pc ON (p.`billing_type_id` = pc.`id`)
			ORDER BY p.`id` ASC";
	$rs = api_db_query_read($sql);
	$products = $rs ? $rs->GetAssoc() : false;

	if ($hydratorKey && $products) {
		switch ($hydratorKey) {
			case API_BILLING_PRODUCTS_HYDRATOR_STATUS:
				$grouped = [0 => [], 1 => []];
				break;
			case API_BILLING_PRODUCTS_HYDRATOR_BILLING_TYPE:
				$grouped = [];
				$billingTypes = api_billing_products_billingtypes_listall();
				if ($billingTypes) {
					$grouped = array_map(
						function() {
							return [];
						},
						$billingTypes
					);
				}
				break;
			default:
				return api_error_raise(
					"Sorry, '{$hydratorKey}' is not a valid product hydrator"
				);
				break;
		}

		foreach ($products as $id => $product) {
			$grouped[$product[$hydratorKey]][$id] = $product;
		}

		return $grouped;
	}

	return $products;
}

/**
 * Add a product
 *
 * @param string  $name
 * @param integer $billingType
 * @param string  $code
 *
 * @return boolean
 */
function api_billing_products_add($name, $billingType, $code = null) {
	if (!is_scalar($name) || !preg_match('/^[\w\s\-]+$/', $name)) {
		return api_error_raise("Sorry, that is not a valid product name");
	}

	$billingType = (int) $billingType;
	$billingTypes = api_billing_products_billingtypes_listall(API_BILLING_PRODUCTS_HYDRATOR_BILLING_TYPE_NAME);
	if (!$billingType || !isset($billingTypes[$billingType])) {
		return api_error_raise("Sorry, that is not a valid product billing type");
	}

	if ($code && !preg_match('/^RT[0-9]{2}$/', $code)) {
		return api_error_raise("Sorry, that is not a valid product code");
	}

	if (!$code) {
		// NOTE: DB auto-increment has a step > 1
		$sql = "SELECT COUNT(*) AS count FROM `billing_products`;";
		$rs = api_db_query_read($sql);
		$code = sprintf('RT%02d', $rs->Fields('count') + 1);
	}

	$sql = "INSERT INTO `billing_products` (`name`, `billing_type_id`, `code`) VALUES (?, ?, ?);";
	$id = false;
	if (api_db_query_write($sql, [$name, $billingType, $code])) {
		$id = api_db_lastid();
	}

	if ($id) {
		return $id;
	}

	return api_error_raise("Sorry, this product could not be created: " . api_db_last_error_write(true));
}

/**
 * Get a product by id
 *
 * @param integer $id
 *
 * @return array|false
 */
function api_billing_products_getbyid($id) {
	if (!is_scalar($id)) {
		return api_error_raise("Sorry, that is not a valid product id");
	}

	$sql = "SELECT * FROM `billing_products` WHERE `id` = ?;";
	$rs = api_db_query_read($sql, [$id]);

	return $rs && $rs->RecordCount() ? $rs->GetArray()[0] : false;
}

/**
 * Set the status of a product
 *
 * @param integer $id
 * @param boolean $status
 *
 * @return boolean
 */
function api_billing_products_setstatus($id, $status) {
	if (!api_billing_products_getbyid($id)) {
		return api_error_raise("Sorry, that is not a valid product id");
	}

	$sql = "UPDATE `billing_products` SET `status` = ? WHERE `id` = ?;";
	$rs = api_db_query_write($sql, [($status ? 1 : 0), $id]);

	return (bool) $rs;
}

/**
 * Update a product
 *
 * @param integer $id
 * @param string  $name
 * @param integer $billingType
 *
 * @return boolean
 */
function api_billing_products_update($id, $name, $billingType) {
	if (!api_billing_products_getbyid($id)) {
		return api_error_raise("Sorry, that is not a valid product id");
	}

	if (!is_scalar($name) || !preg_match('/^[\w\s\-]+$/', $name)) {
		return api_error_raise("Sorry, that is not a valid product name");
	}

	$billingType = (int) $billingType;
	$billingTypes = api_billing_products_billingtypes_listall(API_BILLING_PRODUCTS_HYDRATOR_BILLING_TYPE_NAME);
	if (!$billingType || !isset($billingTypes[$billingType])) {
		return api_error_raise("Sorry, that is not a valid product billing type");
	}

	$sql = "UPDATE `billing_products` SET `name` = ?, `billing_type_id` = ? WHERE `id` = ?;";
	$rs = api_db_query_write($sql, [$name, $billingType, $id]);

	return (bool) $rs;
}

/**
 * List all products billing types
 *
 * @param string $hydratorKey
 *
 * @return array|false
 */
function api_billing_products_billingtypes_listall($hydratorKey = null) {
	$sql = "SELECT * FROM `billing_types`;";
	$rs = api_db_query_read($sql);
	$billingTypes = $rs ? $rs->GetAssoc() : false;

	if ($hydratorKey && $billingTypes) {
		switch ($hydratorKey) {
			case API_BILLING_PRODUCTS_HYDRATOR_BILLING_TYPE_NAME:
				$grouped = array_map(
					function($billingType) {
						return $billingType['name'];
					},
					$billingTypes
				);
				break;
			default:
				return api_error_raise(
					"Sorry, '{$hydratorKey}' is not a valid billing type hydrator"
				);
				break;
		}

		return $grouped;
	}

	return $billingTypes;
}

/**
 * @param array $regions
 * @return array
 */
function api_billing_get_sms_products_config(array $regions = []) {
	$sql = 'SELECT `region_id`, `billing_product_id` AS `product_id` FROM `billing_products_config_sms`';

	if ($regions) {
		$sql .= ' WHERE `region_id` IN (' . implode(',', array_fill(0, count($regions), '?')) . ')';
	}

	$rs = api_db_query_read($sql, $regions);

	if (!$rs) {
		return [];
	}

	return $rs->GetAssoc();
}

/**
 * @param array $regions
 * @return array
 */
function api_billing_get_wash_products_config(array $regions = []) {
	$sql = 'SELECT `region_id`, `destination_type_id`, `billing_product_id` AS `product_id` FROM `billing_products_config_wash`';

	if ($regions) {
		$sql .= ' WHERE `region_id` IN (' . implode(',', array_fill(0, count($regions), '?')) . ')';
	}

	$rs = api_db_query_read($sql, $regions);

	if (!$rs) {
		return [];
	}

	return $rs->GetArray();
}

/**
 * @param array $regions
 * @return array
 */
function api_billing_get_phone_products_config(array $regions = []) {
	$sql = '
		SELECT `region_id`, `destination_type_id`, `billing_product_id` AS `product_id`, `interval` 
		FROM `billing_products_config_phone`
	';

	if ($regions) {
		$sql .= ' WHERE region_id IN (' . implode(',', array_fill(0, count($regions), '?')) . ')';
	}

	$rs = api_db_query_read($sql, $regions);

	if (!$rs) {
		return [];
	}

	return $rs->GetArray();
}

/**
 * @return array|null
 */
function api_billing_get_email_products_config() {
	$sql = 'SELECT `billing_product_id` as `product_id` FROM `billing_products_config_email`';

	$rs = api_db_query_read($sql);

	if (!$rs || !$rs->RecordCount()) {
		return null;
	}

	$config = $rs->GetArray();
	return $config;
}
