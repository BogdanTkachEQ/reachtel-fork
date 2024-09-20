<?php
/**
 * InvoiceModuleHelper
 * Helper to create invoices
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait Helper for invoices
 * TODO: Move the functions to it's own helper rather than keeping it under billing module
 */
trait BillingModuleHelper
{
	use SmsSupplierModuleHelper;
	use UserModuleHelper;

	private $tables = ['sms_api_mapping', 'sms_out', 'wash_out'];

	private $billing_types = [
		'sms' => ['smsaumobile', 'smsgbmobile', 'smsnzmobile', 'smsothermobile', 'smsphmobile', 'smssgmobile'],
		'wash' => ['washaufixedline', 'washaumobile', 'washgbfixedline', 'washgbmobile', 'washnzfixedline', 'washnzmobile', 'washother', 'washsgfixedline', 'washsgmobile']
	];

	/**
	 * @param integer $nb_records
	 * @param integer $user_id
	 * @param string  $timestamp
	 * @return array
	 */
	protected function create_new_sms_api_mapping($nb_records = 1, $user_id = null, $timestamp = null) {
		$records = [];
		$event_id = api_misc_uniqueid();

		for ($i = 1; $i <= (int) $nb_records; $i++) {
			$user_id = $user_id ? : $this->get_default_admin_id();
			$billing_type = $this->billing_types['sms'][rand(0, count($this->billing_types['sms']) - 1)];
			$uid = rand(1, 99);
			$message_units = rand(1, 3);
			$_timestamp = date('Y-m-d H:i:s', strtotime($timestamp ? : "+{$i} seconds"));

			$sql = "INSERT INTO `sms_api_mapping` (`userid`, `billingtype`, `rid`, `uid`, `messageunits`, `timestamp`) VALUES (?, ?, ?, ?, ?, ?)";
			api_db_query_write($sql, array($user_id, $billing_type, $event_id, $uid, $message_units, $_timestamp));

			$records[] = [
				'event_id' => $event_id,
				'user_id' => $user_id,
				'billing_type' => $billing_type,
				'uid' => $uid,
				'message_units' => $message_units
			];
		}

		return $records;
	}

	/**
	 * @param integer $nb_records
	 * @param integer $user_id
	 * @param string  $timestamp
	 * @return array
	 */
	protected function create_new_sms_out($nb_records = 1, $user_id = null, $timestamp = null) {
		$records = [];

		for ($i = 1; $i <= (int) $nb_records; $i++) {
			$user_id = $user_id ? : $this->get_default_admin_id();
			$_timestamp = date('Y-m-d H:i:s', strtotime($timestamp ? : "+{$i} seconds"));
			$billing_type = $this->billing_types['sms'][rand(0, count($this->billing_types['sms']) - 1)];
			$supplier = rand(1, 127);
			$sms_suppliers = api_sms_supplier_listall();
			if (count($sms_suppliers) > 3) {
				$supplier_id = array_rand($sms_suppliers);
			} else {
				$supplier_id = $this->create_new_smssupplier();
			}
			$message_units = rand(0, 2);
			$message = "Message {$i} " . str_repeat(' content', rand(100, 100));
			$destination = '04' . rand(pow(10, 7), pow(10, 8) - 1);

			$sql = "INSERT INTO `sms_out` (`userid`, `timestamp`, `billingtype`, `supplier`, `supplierid`, `from`, `destination`, `message`)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
			$record = api_db_query_write($sql, array($user_id, $_timestamp, $billing_type, $supplier, $supplier_id, '0411000123', $destination, $message));

			$records[] = [
				'id' => api_db_lastid(),
				'user_id' => $user_id,
				'billing_type' => $billing_type,
				'message_units' => strlen($message) <= 160 ? 1 : ceil(strlen($message) / 153)
			];
		}

		return $records;
	}

	/**
	 * @param integer $sms_out_id
	 * @param string  $timestamp
	 * @return array
	 */
	protected function create_new_sms_out_status($sms_out_id = null, $timestamp = null) {
		$sql = "INSERT INTO `sms_out_staus` (`id`, `timestamp`, `status`) VALUES (?, ?, ?)";
		return api_db_query_write($sql, array($sms_out_id, $timestamp, 'delivered'));
	}

	/**
	 * @param integer $nb_records
	 * @param integer $user_id
	 * @param string  $timestamp
	 * @return array
	 */
	protected function create_new_wash_out($nb_records = 1, $user_id = null, $timestamp = null) {
		$records = [];

		for ($i = 1; $i <= (int) $nb_records; $i++) {
			$user_id = $user_id ? : $this->get_default_admin_id();
			$_timestamp = date('Y-m-d H:i:s', strtotime($timestamp ? : "+{$i} seconds"));
			$destination = '04' . rand(pow(10, 7), pow(10, 8) - 1);
			$billing_type = $this->billing_types['wash'][rand(0, count($this->billing_types['wash']) - 1)];
			$status = rand(0, 1) ? 'CONNECTED' : 'DISCONNECTED';
			$carrier_code = rand(10000, 99999);

			$sql = "INSERT INTO `wash_out` (`userid`, `timestamp`, `destination`, `billingtype`, `status`, `reason`, `returncarrier`, `carriercode`, `errors`)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$record = api_db_query_write($sql, array($user_id, $_timestamp, $destination, $billing_type, $status, 'PING_CONNECTED', 1, $carrier_code, 0));

			$records[] = [
				'id' => api_db_lastid(),
				'user_id' => $user_id,
				'billing_type' => $billing_type,
				'status' => $status
			];
		}

		return $records;
	}

	/**
	 * @return void
	 */
	protected function purge_sms_wash_records() {
		foreach ($this->tables as $table) {
			if ($this->get_table_number_rows($table) > 0) {
				api_db_query_write("TRUNCATE `$table`;");
				$this->assertSameEquals(0, $this->get_table_number_rows($table), "Truncate table '$table' failed");
			}
		}
	}
}
