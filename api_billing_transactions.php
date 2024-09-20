<?php

use Services\Utils\Billing\Channels;

/**
 * @param integer  $groupid
 * @param DateTime $start
 * @param DateTime $end
 * @return array
 */
function api_billing_transactions_fetch_products($groupid, DateTime $start, DateTime $end) {
	if ($start > $end) {
		throw new InvalidArgumentException('Start date can not be greater than end date');
	}

	if(!api_groups_checkidexists($groupid)) {
		throw new InvalidArgumentException("Invalid group id specified");
	}

	api_db_switch_connection(null, null, null, DB_MYSQL_READ_HOST_FORCED);
	$adhoc_products = [];

	$invoiceitems = api_groups_setting_getsingle($groupid, "invoiceitems");
	if(!empty($invoiceitems)) {
		$invoiceitems = unserialize($invoiceitems);

		if (is_array($invoiceitems)) {
			foreach ($invoiceitems as $invoiceitem) {
				if (!isset($invoiceitem['chargedate'])) {
					continue;
				}

				$charge_date = (new DateTime())->setTimestamp($invoiceitem['chargedate']);

				if ($charge_date < $start || $charge_date > $end) {
					continue;
				}

				$product_id = $invoiceitem['type'];

				if (!isset($adhoc_products[$product_id]['units'])) {
					$adhoc_products[$product_id]['units'] = 0;
				}
				$adhoc_products[$product_id]['units'] += $invoiceitem['units'];

				if (!isset($adhoc_products[$product_id]['description'])) {
					$adhoc_products[$product_id]['description'] = $invoiceitem['itemname'];
				} else {
					$adhoc_products[$product_id]['description'] .= ',' . $invoiceitem['itemname'];
				}
			}
		}
	}

	// Email api products
    api_db_ping(null, null, null, DB_MYSQL_READ_HOST_FORCED);
    $api_email_products = [];
    $api_email = api_email_smtp_api_sendrate($start, $end, $groupid);
    if ($api_email) {
        $email_products_config = api_billing_get_email_products_config();
        $email_product_id = $email_products_config[0]['product_id'];
        $api_email_products[$email_product_id] = $api_email;
    }

	api_db_ping(null, null, null, DB_MYSQL_READ_HOST_FORCED);
	// SMS api products
	$sms_products = [];
	$api_sms = api_campaigns_apirate($start, $end, $groupid);
	if ($api_sms) {
		$sms_products_config = api_billing_get_sms_products_config();

		$region_diff = array_diff_key($api_sms, $sms_products_config);
		if ($region_diff) {
			api_error_raise(
				'Missing config for billing sms for the following regions:' . implode(',', array_keys($region_diff))
			);

			$api_sms = array_intersect_key($api_sms, $sms_products_config);
		}

		array_walk($api_sms, function($user_data, $key) use (&$sms_products, $sms_products_config) {
			$product_id = $sms_products_config[$key];
			$sms_products[$product_id] = $user_data;
		});
	}

	api_db_ping(null, null, null, DB_MYSQL_READ_HOST_FORCED);
	// Wash api products
	$wash_products = [];
	$api_wash = api_campaigns_washrate($start, $end, $groupid);
	if ($api_wash) {
		$wash_products_config = api_billing_get_wash_products_config();
		foreach ($api_wash as $region_id => $data) {
			foreach ($data as $destination_type_id => $user_data) {
				try {
					$product_id = _api_billing_transactions_get_product_id_from_config(
						$wash_products_config,
						$region_id,
						$destination_type_id
					);
				} catch (Exception $e) {
					api_error_raise('Wash config: '. $e->getMessage());
					continue;
				}

				$wash_products[$product_id] = $user_data;
			}
		}
	}

	api_db_ping(null, null, null, DB_MYSQL_READ_HOST_FORCED);
	// Products for campaign items
	$campaign_items = api_campaigns_rate($start, $end, $groupid);
	$campaign_products = [];

	$campaign_products_hydrator = function (array $campaign_products_array, $campaign_id, $product_id, $unit, $name) {
		if (!isset($campaign_products_array[$campaign_id]['name'])) {
			$campaign_products_array[$campaign_id]['name'] = $name;
		}

		if (!isset($campaign_products_array[$campaign_id]['products'][$product_id])) {
			$campaign_products_array[$campaign_id]['products'][$product_id] = 0;
		}

		$campaign_products_array[$campaign_id]['products'][$product_id] += $unit;
		return $campaign_products_array;
	};

	foreach ($campaign_items as $campaign_id => $data) {
		$billinginfo = $data['billinginfo'];
		$name = $data['name'];

		switch ($data['type']) {
			case CAMPAIGN_TYPE_VOICE:
				foreach ($billinginfo as $infos) {
					if (!$infos) {
						continue;
					}
					foreach ($infos as $info) {
						if (!$info) {
							continue;
						}

						foreach ($info['units'] as $interval => $unit) {
							if (!isset($phone_products_config)) {
								$phone_products_config = api_billing_get_phone_products_config();
							}

							try {
								$product_id = _api_billing_transactions_get_product_id_from_config(
									$phone_products_config,
									$info['region_id'],
									$info['destination_type_id'],
									$interval
								);
							} catch (Exception $e) {
								api_error_raise('Phone Config:' . $e->getMessage());
								continue;
							}

							$campaign_products = $campaign_products_hydrator(
								$campaign_products,
								$campaign_id,
								$product_id,
								$unit,
								$name
							);
						}
					}
				}
				break;

			case CAMPAIGN_TYPE_SMS:
				foreach ($billinginfo as $info) {
					if (!$info) {
						continue;
					}

					if (!isset($sms_products_config)) {
						$sms_products_config = api_billing_get_sms_products_config();
					}

					if (!isset($sms_products_config[$info['region_id']])) {
						api_error_raise(
							'Missing config for billing sms for the following region:' . $info['region_id']
						);

						continue;
					}

					$product_id = $sms_products_config[$info['region_id']];
					$campaign_products = $campaign_products_hydrator(
						$campaign_products,
						$campaign_id,
						$product_id,
						$info['units'],
						$name
					);
				}
				break;

			case CAMPAIGN_TYPE_EMAIL:
				foreach ($billinginfo as $info) {
					if (!$info) {
						continue;
					}

					if (!isset($email_products_config)) {
						$email_products_config = api_billing_get_email_products_config();
					}

					$product_id = $email_products_config[0]['product_id'];

					$campaign_products = $campaign_products_hydrator(
						$campaign_products,
						$campaign_id,
						$product_id,
						$info['units'],
						$name
					);
				}
				break;

			case CAMPAIGN_TYPE_WASH:
				foreach ($billinginfo as $info) {
					if (!$info) {
						continue;
					}

					if (!isset($wash_products_config)) {
						$wash_products_config = api_billing_get_wash_products_config();
					}

					try {
						$product_id = _api_billing_transactions_get_product_id_from_config(
							$wash_products_config,
							$info['region_id'],
							$info['destination_type_id']
						);
					} catch (Exception $e) {
						api_error_raise('Wash Config: '. $e->getMessage());
						continue;
					}

					$campaign_products = $campaign_products_hydrator(
						$campaign_products,
						$campaign_id,
						$product_id,
						$info['units'],
						$name
					);
				}
				break;
		}
	}

	api_db_reset_connection();

	return [
		'adhoc_products' => $adhoc_products,
        'api_email_products' => $api_email_products,
		'api_wash_products' => $wash_products,
		'api_sms_products' => $sms_products,
		'campaign_products' => $campaign_products
	];
}

function api_billing_transactions_add_feed($runid, $groupid, DateTime $start, DateTime $end) {
	$products = api_billing_transactions_fetch_products($groupid, $start, $end);

	$feed = _api_billing_transactions_group_and_create_feed_array(
		$runid,
		[
			$products['api_wash_products'],
			$products['api_sms_products'],
			$products['api_email_products']
		],
		$groupid,
		$start
	);

	foreach ($products['adhoc_products'] as $product_id => $data) {
		$adhoc_feed = _api_billing_transactions_group_and_create_feed_array(
			$runid,
			[[$product_id => $data['units']]],
			$groupid,
			$start,
			$data['description'],
			Channels::WEB_NAME
		);

		$feed = array_merge($feed, $adhoc_feed);
	}

	foreach ($products['campaign_products'] as $campaign_id => $data) {
		$campaign_feed = _api_billing_transactions_group_and_create_feed_array(
			$runid,
			[$data['products']],
			$groupid,
			$start,
			$data['name'],
			Channels::WEB_NAME
		);

		$feed = array_merge($feed, $campaign_feed);
	}

	return api_billing_transactions_insert_feed($feed, new Channels());
}

// Channel object injected to make it testable
function api_billing_transactions_insert_feed(array $feed, Channels $channels) {
	if (!$feed) {
		return true;
	}

	$sql = 'INSERT INTO `billing_transactions` 
		(`billing_product_id`, `billing_channel_id`, `group_id`, `transaction_timestamp`, `quantity`, `subject`, `billing_run_id`, `username`)
		VALUES ';

	$params = [];
	$placeholders = [];
	foreach ($feed as $item) {
		$placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?)';
		$params = array_merge(
			$params,
			[
				$item['product_id'],
				$channels->getChannelIdByName($item['channel_name']),
				$item['group_id'],
				$item['transaction_timestamp'],
				$item['quantity'],
				$item['subject'],
				$item['billing_run_id'],
				$item['username']
			]
		);
	}

	$sql .= implode(',', $placeholders);
	return api_db_query_write($sql, $params);
}

function api_billing_transactions_has_billing_run_for_the_day(DateTime $date = null)
{
	if (is_null($date)) {
		$date = new DateTime();
	}

	$sql = 'SELECT * FROM `billing_runs` WHERE `billing_period_start` >= ? AND `billing_period_end` <= ? AND `status` = ? limit 1';
	$rs = api_db_query_read(
		$sql,
		[$date->format('Y-m-d 00:00:00'), $date->format('Y-m-d 23:59:59'), BILLING_RUN_COMPLETE]
	);

	return $rs->RecordCount() ? true : false;
}

function api_billing_transactions_create_billing_run(DateTime $start, DateTime $end)
{
	if ($start > $end) {
		throw new Exception('Invalid billing periods passed when creating billing run');
	}

	$sql = 'INSERT INTO `billing_runs` (`status`, `billing_period_start`, `billing_period_end`) VALUES (?, ?, ?)';
	$rs = api_db_query_write(
		$sql,
		[
			BILLING_RUN_IN_PROGRESS,
			$start->format('Y-m-d H:i:s'),
			$end->format('Y-m-d H:i:s')
		]
	);

	if (!$rs) {
		throw new Exception('Billing run could not be created');
	}

	return api_db_lastid();
}

function api_billing_transactions_complete_billing_run($run_id, $errors = 0)
{
	$sql = 'UPDATE `billing_runs` set `status` = ?, `errors` = ? WHERE `id` = ?';
	$rs = api_db_query_write($sql, [BILLING_RUN_COMPLETE, $errors, $run_id]);

	if (!$rs) {
		throw new Exception('Error while setting billing run id ' . $run_id . ' to complete');
	}

	return true;
}

function api_billing_transactions_run_billing(array $groupids, DateTime $start, DateTime $end) {
	$runid = api_billing_transactions_create_billing_run($start, $end);
	$errors = 0;
	foreach ($groupids as $groupid) {
		$return = api_billing_transactions_add_feed($runid, $groupid, $start, $end);

		if (!$return) {
			$errors += 1;
			api_error_raise(
				sprintf("Failed adding transaction feed for group id %d for billing run %d", $groupid, $runid)
			);
		}
	}

	api_billing_transactions_complete_billing_run($runid, $errors);
	return $runid;
}

function api_billing_transactions_selcomm_export(array $run_ids, $filename, $mark_processed = true) {
	if (!defined('SELCOMM_ACCOUNT_CODE_PREFIX')) {
		throw new Exception('Billing transactions export can not be performed as SELCOMM_ACCOUNT_CODE_PREFIX is not configured.');
	}

	if (!$run_ids) {
		return false;
	}

	$filter = '(' . implode(',', array_fill(0, count($run_ids), '?')) . ')';

	$sql = sprintf('SELECT CONCAT("%s", t.group_id) AS `Account_Code`, p.`code` AS `Product_Code`, 
		c.`code` AS `Channel_Code`, DATE_FORMAT(t.`transaction_timestamp`, "%%d/%%m/%%Y") AS `Transaction_Date`, 
		DATE_FORMAT(t.`transaction_timestamp`, "%%H:%%i:%%s") AS `Transaction_Time`, "" AS `Third_Party_Cost`,
		"" AS `Enquiry_Purpose_Code`, "" AS `Matched`, "" AS `Job_Number`, COALESCE(t.`subject`, "") AS `Subject`,
		"" AS `File_Number`, "" AS `User_Id`, COALESCE(t.`username`, "") AS `User_Name`, t.`quantity` AS `Quantity`,
		COALESCE(t.`client_defined1`, "") AS `Client_Defined_Field_1`, "" AS `Client_Defined_Field_2`, "" AS `Search_Criteria`,
		t.`id` AS `Transaction_reference`, "REACHTEL" AS `Source`, "" AS `IA_Portfolio_Name`
		FROM `billing_transactions` t JOIN billing_channels c ON (c.`id`=t.`billing_channel_id`) 
		JOIN `billing_products` p ON (p.`id`=t.`billing_product_id`) WHERE t.billing_run_id IN ' . $filter, SELCOMM_ACCOUNT_CODE_PREFIX);

	$rs = api_db_query_read($sql, $run_ids);
	if (!$rs) {
		throw new Exception('Some thing went wrong with the sql to export transaction data');
	}

	$data = $rs->GetArray();
	$header = $data ? array_keys($data[0]) : [];
	array_unshift($data, $header);

	// Selcomm wants file to not have enclosures
	$csv = api_csv_string($data, '|', chr(0));
	$csv = str_replace(chr(0), '', $csv);

	if (!file_put_contents($filename, $csv)) {
		throw new Exception('Error occurred during export of transaction data to the file path');
	}

	if ($mark_processed) {
		$sql = 'UPDATE `billing_transactions` SET `processed`="y" WHERE `billing_run_id` IN ' . $filter;
		return api_db_query_write($sql, $run_ids);
	}
	return true;
}

function _api_billing_transactions_group_and_create_feed_array(
	$run_id,
	array $products,
	$groupid,
	DateTime $transaction_timestamp,
	$subject = null,
	$channel = Channels::API_NAME
) {
	$feed_array = [];
	foreach ($products as $product) {
		foreach ($product as $id => $units) {
			$feed = [
				'billing_run_id' => $run_id,
				'product_id' => $id,
				'channel_name' => $channel,
				'group_id' => $groupid,
				'transaction_timestamp' => $transaction_timestamp->format('Y-m-d H:i:s'),
				'subject' => $subject
			];

			// This is for api transactions where the transaction is on a username level.
			if (is_array($units)) {
				foreach ($units as $username => $unit) {
					$feed['quantity'] = $unit;
					$feed['username'] = $username;
					$feed_array[] = $feed;
				}
				continue;
			}

			$feed['quantity'] = $units;
			$feed['username'] = null;
			$feed_array[] = $feed;
		}
	}

	return $feed_array;
}

function _api_billing_transactions_get_product_id_from_config(
	array $config,
	$region_id,
	$destination_type_id,
	$interval = null
) {
	foreach ($config as $item) {
		if (
			$item['region_id'] == $region_id &&
			$item['destination_type_id'] == $destination_type_id &&
			(is_null($interval) || $item['interval'] == $interval)
		) {
			return $item['product_id'];
		}
	}

	$message = 'No config found. region_id: ' . $region_id . ', destination_type_id: ' . $destination_type_id;

	if ($interval) {
		$message .= ', interval: ' . $interval;
	}
	throw new Exception($message);
}
