<?php
/**
 * ApiGroupsUnitTest
 * Unit test for api_groups.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Api Geo Code Unit Test class
 */
class ApiGroupsUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_groups_add_data() {
		return [
			// Failures name pattern
			[false, 'ab'],
			[false, str_repeat('a', 36)],
			[false, 'Wr*ngCh@rs'],

			// Failures name exists
			[false, 'group name', true],

			// Success
			[123, 'group name'],
			[123, 'group name', false, false, 5],
			[123, 'group name', false, 123],
		];
	}

	/**
	 * @dataProvider api_groups_add_data
	 * @param mixed   $expected_value
	 * @param string  $name
	 * @param boolean $name_exists
	 * @param boolean $session_user_id
	 * @return void
	 */
	public function test_api_groups_add($expected_value, $name, $name_exists = false, $session_user_id = false) {
		if ($session_user_id) {
			$_SESSION['userid'] = $session_user_id;
		}

		$this->mock_function_value('api_groups_checknameexists', $name_exists);
		$this->mock_function_value('api_keystore_increment', 123);
		$this->mock_function_value('api_groups_setting_set', null);
		$this->mock_function_value('api_users_setting_set', null);
		$this->mock_function_value('api_users_setting_getsingle', '');

		$this->mock_function_param_value(
			'api_users_checkidexists',
			[
				['params' => [123], 'return' => true],
			],
			false
		);

		$this->assertSameEquals($expected_value, api_groups_add($name));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_groups_checkidexists_data() {
		return [
			// Failures audioid
			[false, null],
			[false, false],
			[false, ''],

			// Failures api_keystore_get
			[false, 1, false],

			// Success
			[true],
			[true, '78'],
			[true, '78', null],
		];
	}

	/**
	 * @dataProvider api_groups_checkidexists_data
	 * @param boolean $expected_value
	 * @param integer $group_id
	 * @param string  $api_keystore_get
	 * @return void
	 */
	public function test_api_groups_checkidexists($expected_value, $group_id = 1, $api_keystore_get = 'value') {
		$this->mock_function_value('api_keystore_get', $api_keystore_get);

		$this->assertSameEquals($expected_value, api_groups_checkidexists($group_id));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_groups_delete_data() {
		return [
			// Failures group does not exists
			[false, 1, false],

			// Failures key exists
			[false, 1, true, true],

			// Failures group id = 2
			[false, 2],

			// Success
			[true, 5]
		];
	}

	/**
	 * @dataProvider api_groups_delete_data
	 * @param boolean $expected_value
	 * @param integer $group_id
	 * @param boolean $group_exists
	 * @param boolean $key_exists
	 * @return void
	 */
	public function test_api_groups_delete($expected_value, $group_id = 1, $group_exists = true, $key_exists = false) {
		$this->mock_function_value('api_groups_checkidexists', $group_exists);
		$this->mock_function_value('api_keystore_checkkeyexists', $key_exists);
		$this->mock_function_value('api_keystore_purge', null);

		$this->assertSameEquals($expected_value, api_groups_delete($group_id));
	}

	/**
	 * @return array
	 */
	public function api_groups_get_all_dids_data_provider() {
		return [
			'When invalid type is not passed' => [12, 'invalid', null, false],
			'When sms did type is passed' => [12, 'SMSDIDS', 'SMSDIDS', [['id' => 34, 'name' => 'didname', 'use' => 'test']]],
			'When voice did type is passed' => [12, 'DIDS', 'DIDS', [['id' => 34, 'name' => 'didname', 'use' => 'test']]]
		];
	}

	/**
	 * @dataProvider api_groups_get_all_dids_data_provider
	 * @param integer $groupid
	 * @param string  $didtype
	 * @param string  $expectedDidtype
	 * @param mixed   $expected
	 * @return void
	 */
	public function test_api_groups_get_all_dids($groupid, $didtype, $expectedDidtype, $expected = null) {
		$this->remove_mocked_functions('api_db_query_read');

		if ($expected !== false) {
			$adoRecords = $this->mock_ado_records($expected);

			$sql = "SELECT k3.`id` AS id, k3.`value` AS `name`, k4.`value` AS `use` FROM" .
				" `key_store` k1 JOIN `key_store` k2 ON (k1.`type`=? AND k1.`item`=? AND k1.`value` = k2.`id`" .
				" AND k2.`type`=? AND k2.`item`=?) JOIN `key_store` k3 ON (k3.`type`=? AND k3.`id`=k1.`id` AND k3.`item`=?)" .
				" LEFT JOIN `key_store` k4 ON (k4.`type`=? AND k4.`id`=k1.`id` AND k4.`item`=?) WHERE k2.`id`=?";

			$parameters = [
				$expectedDidtype,
				'groupowner',
				'GROUPS',
				'name',
				$expectedDidtype,
				'name',
				$expectedDidtype,
				'use',
				$groupid
			];

			$this->mock_function_param_value(
				'api_db_query_read',
				[
					['params' => [$sql, $parameters], 'return' => $adoRecords]
				],
				[]
			);
		}

		$return = api_groups_get_all_dids($groupid, $didtype);

		$this->assertSameEquals($expected, $return);
	}
}
