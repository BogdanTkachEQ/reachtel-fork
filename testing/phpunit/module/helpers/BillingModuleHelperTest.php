<?php
/**
 * InvoiceModuleHelperTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Billing Module Helper Test
 */
class BillingModuleHelperTest extends AbstractModuleHelperTest
{
	const EXPECTED_TYPE = false;

	use BillingModuleHelper;

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function create_new_shared_data() {
		return [
			[0],
			[rand(1, 20)],
			[rand(1, 20), 99],
			[rand(1, 20), null, 'last day of 1 month ago'],
			[rand(1, 20), 4, 'last day of 2 months ago'],
		];
	}

	/**
	 * @group create_new_sms_api_mapping
	 * @dataProvider create_new_shared_data
	 * @param integer $nb_records
	 * @param integer $user_id
	 * @param integer $timestamp
	 * @return void
	 */
	public function test_create_new_sms_api_mapping($nb_records, $user_id = null, $timestamp = null) {
		$table = 'sms_api_mapping';
		$initial_nb_results = $this->get_table_number_rows($table);

		$results = $this->create_new_sms_api_mapping($nb_records, $user_id, $timestamp);
		$this->assertInternalType('array', $results);
		$this->assertSameEquals(
			$nb_records,
			$this->get_table_number_rows($table) - $initial_nb_results
		);

		if ($nb_records > 0) {
			foreach ($results as $result) {
				$this->assertArrayHasKey('event_id', $result);
				$this->assertArrayHasKey('user_id', $result);
				$this->assertArrayHasKey('billing_type', $result);
				$this->assertArrayHasKey('uid', $result);
				$this->assertArrayHasKey('message_units', $result);

				$event_id = isset($event_id) ? $event_id : $result['event_id'];
				$this->assertSameEquals($event_id, $result['event_id']);
				$this->assertSameEquals($user_id ? : $this->get_default_admin_id(), $result['user_id']);
				$this->assertTrue(in_array($result['billing_type'], $this->billing_types['sms']));
				$this->assertTrue(in_array($result['message_units'], [1, 2, 3]));
			}
		} else {
			$this->assertEmpty($results);
		}
	}

	/**
	 * @group create_new_sms_out
	 * @dataProvider create_new_shared_data
	 * @param integer $nb_records
	 * @param integer $user_id
	 * @param integer $timestamp
	 * @return void
	 */
	public function test_create_new_sms_out($nb_records, $user_id = null, $timestamp = null) {
		$table = 'sms_out';
		$initial_nb_results = $this->get_table_number_rows($table);

		$results = $this->create_new_sms_out($nb_records, $user_id, $timestamp);
		$this->assertInternalType('array', $results);
		$this->assertSameEquals(
			$nb_records,
			$this->get_table_number_rows($table) - $initial_nb_results
		);

		if ($nb_records > 0) {
			foreach ($results as $result) {
				$this->assertArrayHasKey('id', $result);
				$this->assertArrayHasKey('user_id', $result);
				$this->assertArrayHasKey('billing_type', $result);

				$this->assertSameEquals($user_id ? : $this->get_default_admin_id(), $result['user_id']);
				$this->assertTrue(in_array($result['billing_type'], $this->billing_types['sms']));
			}
		} else {
			$this->assertEmpty($results);
		}
	}

	/**
	 * @group create_new_wash_out
	 * @dataProvider create_new_shared_data
	 * @param integer $nb_records
	 * @param integer $user_id
	 * @param integer $timestamp
	 * @return void
	 */
	public function test_create_new_wash_out($nb_records, $user_id = null, $timestamp = null) {
		$table = 'wash_out';
		$initial_nb_results = $this->get_table_number_rows($table);

		$results = $this->create_new_wash_out($nb_records, $user_id, $timestamp);
		$this->assertInternalType('array', $results);
		$this->assertSameEquals(
			$nb_records,
			$this->get_table_number_rows($table) - $initial_nb_results
		);

		if ($nb_records > 0) {
			foreach ($results as $result) {
				$this->assertArrayHasKey('id', $result);
				$this->assertArrayHasKey('user_id', $result);
				$this->assertArrayHasKey('billing_type', $result);
				$this->assertArrayHasKey('status', $result);

				$this->assertSameEquals($user_id ? : $this->get_default_admin_id(), $result['user_id']);
				$this->assertTrue(in_array($result['billing_type'], $this->billing_types['wash']));
				$this->assertTrue(in_array($result['status'], ['CONNECTED', 'DISCONNECTED']));
			}
		} else {
			$this->assertEmpty($results);
		}
	}

	/**
	 * @group purge_sms_wash_records
	 * @return void
	 */
	public function test_purge_sms_wash_records() {
		$this->test_create_new_sms_api_mapping(rand(10, 100));
		$this->test_create_new_sms_out(rand(10, 100));
		$this->test_create_new_wash_out(rand(10, 100));

		$this->purge_sms_wash_records();

		foreach ($this->tables as $table) {
			$this->assertSameEquals(0, $this->get_table_number_rows($table), "Failed asserting table '$table' is empty");
		}
	}
}
