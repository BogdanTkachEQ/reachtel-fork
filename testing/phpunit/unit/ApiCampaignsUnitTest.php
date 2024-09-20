<?php
/**
 * ApiCampaignsTest
 * Unit test for api_campaigns.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

use testing\helpers\MethodParametersHelper;
use testing\unit\helpers\MethodsCheckExistsUnitTrait;
use testing\unit\helpers\MethodsSettingsUnitTrait;
use testing\unit\helpers\MethodsTagsUnitTrait;

/**
 * Api Campaigns Unit Test class
 */
class ApiCampaignsUnitTest extends AbstractPhpunitUnitTest
{
	use MethodParametersHelper;
	use MethodsCheckExistsUnitTrait;
	use MethodsSettingsUnitTrait;
	use MethodsTagsUnitTrait;

	/**
	 * Type value
	 */
	const TYPE = 'CAMPAIGNS';

	/**
	 * Campaign types
	 */
	private $types = ['phone', 'sms', 'email', 'wash'];

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_campaigns_add_data() {
		return array_merge(
			// Failures campaignname does not match
			array_map(function($type) { return [false, 'abcd', $type]; }, $this->types),
			array_map(function($type) { return [false, str_repeat('a', 76), $type]; }, $this->types), // length > 76
			array_map(function($type) { return [false, '{[invalid chars)}', $type]; }, $this->types),
			// Failures campaign name exists
			array_map(function($type) { return [false, 'campaign-exists', $type]; }, $this->types),
			// Failures duplicate campaign does not exists
			array_map(function($type) { return [false, 'campaign-duplicate-fail', $type, 10]; }, $this->types),
			// Failures type
			[[false, uniqid('campaign-type-fail'), null]],
			[[false, uniqid('campaign-type-fail'), 'whatever']],
			// Failures user does not exists
			array_map(function($type) { return [false, 'campaign-duplicate-fail', $type, null, 99]; }, $this->types),
			// Success
			array_map(function($type) { return [1, str_repeat('Y', 60), $type]; }, $this->types),
			array_map(function($type) { return [1, 'campaign-duplicate', $type, 3]; }, $this->types)
		);
	}

	/**
	 * @group api_campaigns_add
	 * @dataProvider api_campaigns_add_data
	 * @param mixed   $expected_value
	 * @param mixed   $name
	 * @param mixed   $type
	 * @param mixed   $duplicate
	 * @param integer $owner_session
	 * @return void
	 */
	public function test_api_campaigns_add($expected_value, $name, $type = 'phone', $duplicate = null, $owner_session = null) {
		if (isset($_SESSION['userid'])) {
			unset($_SESSION['userid']);
		}
		if ($owner_session) {
			$_SESSION['userid'] = $owner_session;
		}

		$this->mock_function_param_value(
			'api_campaigns_checkidexists',
			[
				['params' => 10, 'return' => false]
			],
			true
		);
		$this->mock_function_param_value(
			'api_campaigns_checknameexists',
			[
				['params' => 'campaign-exists', 'return' => true]
			],
			false
		);
		$this->mock_function_param_value(
			'api_users_checkidexists',
			[
				['params' => 99, 'return' => false]
			],
			true
		);

		$this->mock_function_value('api_keystore_increment', 1);
		$this->mock_function_value('api_campaigns_setting_set', null);
		$this->mock_function_value('api_users_setting_getsingle', null);
		$this->mock_function_value('api_campaigns_setting_getall', ['name' => 'diplicate-name', 'wrong-key' => 'whatever']);
		$this->mock_function_value('api_campaigns_getclassification', 'exempt');

		$this->assertSameEquals($expected_value, api_campaigns_add($name, $type, $duplicate));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_campaigns_delete_data() {
		return [
			// Failures campaign does not exists
			[false, 99],

			// Success
			[true, 1],
		];
	}

	/**
	 * @group api_campaigns_delete
	 * @dataProvider api_campaigns_delete_data
	 * @param mixed $expected_value
	 * @param mixed $campaign_id
	 * @return void
	 */
	public function test_api_campaigns_delete($expected_value, $campaign_id) {
		$this->mock_function_param_value(
			'api_campaigns_checkidexists',
			[
				['params' => 99, 'return' => false]
			],
			true
		);
		$this->mock_function_value('api_data_responses_delete', null);
		$this->mock_function_value('api_data_callresult_delete_all', null);
		$this->mock_function_value('api_targets_delete_all', null);
		$this->mock_function_value('api_data_merge_delete_all', null);
		$this->mock_function_value('api_keystore_purge', null);

		$this->assertSameEquals($expected_value, api_campaigns_delete($campaign_id));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_campaigns_rename_data() {
		return [
			// Failures not idexists
			[false, 1, 'campaign', false],

			// Failures name does not match
			[false, 1, 'abcd'],
			[false, 1, str_repeat('a', 61)], // length > 60
			[false, 1, '{[invalid chars)}'],

			// Failures name already exists
			[false, 1, 'campaign', true, true],

			// Success
			[true, 1, 'campaign'],
			[true, 1, str_repeat('Y', 60)], // length = 60
		];
	}

	/**
	 * @group api_campaigns_rename
	 * @dataProvider api_campaigns_rename_data
	 * @param mixed $expected_value
	 * @param mixed $campaign_id
	 * @param mixed $name
	 * @param mixed $id_exists
	 * @param mixed $name_exists
	 * @return void
	 */
	public function test_api_campaigns_rename($expected_value, $campaign_id, $name = 'campaign', $id_exists = true, $name_exists = false) {
		$this->mock_function_value('api_campaigns_setting_set', true);
		$this->mock_function_value('api_campaigns_checkidexists', $id_exists);
		$this->mock_function_value('api_campaigns_checknameexists', $name_exists);
		$this->assertSameEquals($expected_value, api_campaigns_rename($campaign_id, $name));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_campaigns_checkorcreate_data() {
		return [
			// Failures name
			[false, null],
			[false, false],
			[false, ''],

			// Failures duplicatecampaignid
			[false, 'campaign', null],
			[false, 'campaign', false],
			[false, 'campaign', ''],

			// Success
			[1, 'campaign', 2],
			[4, 'campaign', 2, null]
		];
	}

	/**
	 * @group api_campaigns_checkorcreate
	 * @dataProvider api_campaigns_checkorcreate_data
	 * @param mixed $expected_value
	 * @param mixed $name
	 * @param mixed $duplicatecampaignid
	 * @param mixed $checknameexists
	 * @param mixed $campaigns_add
	 * @return void
	 */
	public function test_api_campaigns_checkorcreate($expected_value, $name, $duplicatecampaignid = 2, $checknameexists = 1, $campaigns_add = 4) {
		$this->mock_function_value('api_campaigns_checknameexists', $checknameexists);
		$this->mock_function_value('api_campaigns_add', $campaigns_add);
		$this->assertSameEquals($expected_value, api_campaigns_checkorcreate($name, $duplicatecampaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_campaigns_nametoid_data() {
		return [
			// Failures checknameexists
			[false, 'campaign', null],
			[false, 'campaign', false],
			[false, 'campaign', ''],

			[1, 'campaign'],
		];
	}

	/**
	 * @group api_campaigns_nametoid
	 * @dataProvider api_campaigns_nametoid_data
	 * @param mixed $expected_value
	 * @param mixed $name
	 * @param mixed $checknameexists
	 * @return void
	 */
	public function test_api_campaigns_nametoid($expected_value, $name, $checknameexists = 1) {
		$this->mock_function_value('api_campaigns_checknameexists', $checknameexists);
		$this->assertSameEquals($expected_value, api_campaigns_nametoid($name));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_campaigns_namesuggester_data() {
		return [
			// Failures empty name
			[false, false],
			[false, null],
			[false, ''],

			// Success dummy name
			['dummy-name-2', 'dummy-name'],

			// Success matches "IgniteTravel-SMS-DocRemind-13February14-1"
			[ // today date & year 2 digits
				'Customer-Today-' . date('jFy') . '-9',
				'Customer-Today-' . date('jFy') . '-' . rand(1, 5)
			],
			[ // today date & year 4 digits
				'Customer-Today-' . date('jFY') . '-9',
				'Customer-Today-' . date('jFY') . '-' . rand(1, 5)
			],
			[ // not today & year 2 digits
				'Customer-NotToday-' . date('jFy') . '-1',
				'Customer-NotToday-19February15-' . rand(1, 5)
			],
			[ // today date & year 4 digits
				'Customer-NotToday-' . date('jFY') . '-1',
				'Customer-NotToday-19February2015-' . rand(1, 5)
			],

			// Success matches "NCML-13February14-SMS-NCMLSCOM-1"
			[ // today date & year 2 digits
				'CustomerToday-' . date('jFy') . '-Test-7',
				'CustomerToday-' . date('jFy') . '-Test-' . rand(1, 5)
			],
			[ // today date & year 4 digits
				'CustomerToday-' . date('jFY') . '-Test-7',
				'CustomerToday-' . date('jFY') . '-Test-' . rand(1, 5)
			],
			[ // not today & year 2 digits
				'CustomerNotToday-' . date('jFy') . '-Test-1',
				'CustomerNotToday-19February15-Test-' . rand(1, 5)
			],
			[ // today date & year 4 digits
				'CustomerNotToday-' . date('jFY') . '-Test-1',
				'CustomerNotToday-19February2015-Test-' . rand(1, 5)
			],

			// Success matches "NewsLtd-13February14-Franklin"
			[ // today date & year 2 digits
				'CustomerName-' . date('jFy') . '-Today-2',
				'CustomerName-' . date('jFy') . '-Today'
			],
			[ // today date & year 4 digits
				'CustomerName-' . date('jFY') . '-Today-2',
				'CustomerName-' . date('jFY') . '-Today'
			],
			[ // not today & year 2 digits
				'CustomerName-' . date('jFy') . '-NotToday',
				'CustomerName-19February15-NotToday'
			],
			[ // today date & year 4 digits
				'CustomerName-' . date('jFY') . '-NotToday',
				'CustomerName-19February2015-NotToday'
			],

			// Success matches "LoanRanger-Overdue-14Feb14"
			[ // today date & year 2 digits
				'CustomerName-Today-' . date('jFy') . '-2',
				'CustomerName-Today-' . date('jFy'),
			],
			[ // today date & year 4 digits
				'CustomerName-Today-' . date('jFY') . '-2',
				'CustomerName-Today-' . date('jFY'),
			],
			[ // not today & year 2 digits
				'CustomerName-NotToday-' . date('jFy'),
				'CustomerName-NotToday-19February16'
			],
			[ // today date & year 4 digits
				'CustomerName-NotToday-' . date('jFY'),
				'CustomerName-NotToday-19February2016'
			],

			// Success matches "ToyotaFS-Hardship-20160705"
			[ // today date & year 2 digits
				'CustomerName-Today-' . date('Ymd') . '-2',
				'CustomerName-Today-' . date('Ymd'),
			],
			[ // not today & year 2 digits
				'CustomerName-NotToday-' . date('Ymd'),
				'CustomerName-NotToday-20160705'
			],

			// Success matches "ToyotaFS-Hardship-05072016"
			[ // today date & year 2 digits
			'CustomerName-Today-' . date('dmY') . '-2',
			'CustomerName-Today-' . date('dmY'),
			],
			[ // not today & year 2 digits
				'CustomerName-NotToday-' . date('dmY'),
				'CustomerName-NotToday-05072016'
			],

			// Success matches "Some-UnsupportedFormat-WithANumber-OnTheEnd-6"
			[
				'Whatever-3',
				'Whatever-1',
			],
		];
	}

	/**
	 * @group api_campaigns_namesuggester
	 * @dataProvider api_campaigns_namesuggester_data
	 * @param mixed $expected_value
	 * @param mixed $name
	 * @return void
	 */
	public function test_api_campaigns_namesuggester($expected_value, $name) {
		$date = date('jFy');

		$nameexists = [
			['params' => 'dummy-name', 'return' => true],
			['params' => 'CustomerName-' . date('jFy') . '-Today', 'return' => true],
			['params' => 'CustomerName-' . date('jFY') . '-Today', 'return' => true],
			['params' => 'CustomerName-Today-' . date('jFy'), 'return' => true],
			['params' => 'CustomerName-Today-' . date('jFY'), 'return' => true],
			['params' => 'CustomerName-Today-' . date('Ymd'), 'return' => true],
			['params' => 'CustomerName-Today-' . date('dmY'), 'return' => true],
			['params' => 'Whatever-1', 'return' => true],
			['params' => 'Whatever-2', 'return' => true],
		];
		for ($x = 1; $x <= 8; $x++) {
			$nameexists[] = ['params' => 'Customer-Today-' . date('jFy') . "-{$x}", 'return' => true];
			$nameexists[] = ['params' => 'Customer-Today-' . date('jFY') . "-{$x}", 'return' => true];
		}
		for ($x = 1; $x <= 6; $x++) {
			$nameexists[] = ['params' => 'CustomerToday-' . date('jFy') . "-Test-{$x}", 'return' => true];
			$nameexists[] = ['params' => 'CustomerToday-' . date('jFY') . "-Test-{$x}", 'return' => true];
		}

		$this->mock_function_param_value('api_campaigns_checknameexists', $nameexists, false);
		$this->assertSameEquals($expected_value, api_campaigns_namesuggester($name));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_campaigns_list_all_data() {
		$count = rand(1, 100);

		return [
			// Success empty values
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [], 'records' => []]
			),

			// Success option countonly
			self::get_test_data_from_parameters_default_values(
				['expected_value' => $count, 'options' => ['countonly' => true], 'records' => ['count' => $count]]
			),

			// Success option countonly with long
			self::get_test_data_from_parameters_default_values(
				['expected_value' => $count, 'long' => true, 'options' => ['countonly' => true], 'records' => ['count' => $count]]
			),

			// Success option activeonly
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [1, 22], 'options' => ['activeonly' => true]]
			),

			// Success option activeonly with long
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [1 => 'campaign1', 22 => 'campaign22'], 'long' => true, 'options' => ['activeonly' => true]]
			),

			// Success option search
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [1, 22], 'options' => ['search' => '*789']]
			),
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [1, 22], 'options' => ['search' => '789']]
			),

			// Success option regex search
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [1, 22], 'options' => ['regex' => '.+789']]
			),
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [1, 22], 'options' => ['regex' => '7[8]9']]
			),

			// Success option search with long
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [1 => 'campaign1', 22 => 'campaign22'], 'long' => true, 'options' => ['search' => '*789']]
			),

			// Success userid does not exists
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [1, 22], 'userid' => 1]
			),

			// Success is admin
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [1, 22], 'userid' => 1, 'security_groupaccess' => ['isadmin' => false, 'groups' => [1, 2]]]
			),

			// Success is admin with long
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [1 => 'campaign1', 22 => 'campaign22'], 'long' => true, 'userid' => 2, 'security_groupaccess' => ['isadmin' => false, 'groups' => [1, 2]]]
			),

			// Success limit
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [1, 22], 'limit' => 2]
			),

			// Success limit with long
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [1 => 'campaign1', 22 => 'campaign22'], 'long' => true, 'limit' => 2]
			),

			// Success limit and offset option
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [1, 22], 'limit' => 2, 'options' => ['offset' => 1]]
			),

			// Success limit and offset option with long
			self::get_test_data_from_parameters_default_values(
				['expected_value' => [1 => 'campaign1', 22 => 'campaign22'], 'long' => true, 'limit' => 2, 'options' => ['offset' => 1]]
			),
		];
	}

	/**
	 * @group api_campaigns_list_all
	 * @dataProvider api_campaigns_list_all_data
	 * @param mixed   $expected_value
	 * @param mixed   $long
	 * @param boolean $userid
	 * @param boolean $limit
	 * @param array   $options
	 * @param array   $records
	 * @param mixed   $security_groupaccess
	 * @return void
	 */
	public function test_api_campaigns_list_all($expected_value, $long = null, $userid = false, $limit = false, array $options = [], array $records = array(1 => 'campaign1', 22 => 'campaign22'), $security_groupaccess = []) {
		$ado_records = $this->mock_ado_records($records);
		$this->mock_function_param_value(
			'api_users_checkidexists',
			[
				['params' => 2, 'return' => true],
			],
			false
		);
		$this->mock_function_value('api_security_groupaccess', $security_groupaccess);
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value('api_db_query_read', $ado_records);
		$this->assertSameEquals($expected_value, api_campaigns_list_all($long, $userid, $limit, $options));
	}

	/**
	 * @group api_campaigns_list_active
	 * @return void
	 */
	public function test_api_campaigns_list_active() {
		$keystore_function = 'api_keystore_getidswithvalue';
		foreach ([false, true] as $boolean_value) {
			$this->remove_mocked_functions($keystore_function);
			$this->mock_function_value($keystore_function, $boolean_value);
			$this->assertSameEquals(
				$boolean_value,
				api_campaigns_list_active()
			);
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_campaigns_averageduration_data() {
		return [
			// Failures campaign not valid
			[false, false],
			[false, null],
			[false, ''],
			[false, 0],
			[false, 99],

			// Failures empty action
			[false, 1, false],
			[false, 2, null],
			[false, 3, ''],
			[false, 4, 0],

			// Failures empty value
			[false, 5, 'action', false],
			[false, 6, 'action', null],
			[false, 7, 'action', ''],
			[false, 8, 'action', 0],

			// Failures query
			[false, 9, 'action', 'value', false], // Query read failure
			[false, 10, 'action', 'value', []], // No results
			[false, 11, 'action', 'value', ['whatever' => 11]], // No 'average' fields

			// Success
			[false, 12, 'action', 'value', ['average' => null]],
			[13.13, 13, 'action', 'value', ['average' => 13.13]],
			[14.0, 14, 'action', 'value', ['average' => 14]],
		];
	}

	/**
	 * @group api_campaigns_averageduration
	 * @dataProvider api_campaigns_averageduration_data
	 * @param mixed $expected_value
	 * @param mixed $campaign_id
	 * @param mixed $action
	 * @param mixed $value
	 * @param mixed $ado_records
	 * @return void
	 */
	public function test_api_campaigns_averageduration($expected_value, $campaign_id, $action = 'action', $value = 'value', $ado_records = [1, 2, 3]) {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => 99, 'return' => 'sms'],
			],
			'phone'
		);
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			(is_array($ado_records) ? $this->mock_ado_records($ado_records) : $ado_records)
		);

		$this->assertSameEquals($expected_value, api_campaigns_averageduration($campaign_id, $action, $value));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_campaigns_healthcheck_data() {
		return [
			// Failures campaign does not exists
			[false, 99],

			// Success
			[true, 1], // Not active
			[true, 2, 'DISABLED'], // Not active
			[true, 3, 'ACTIVE', '1 min ago'], // Active, heartbeat 1 min ago
			[true, 4, 'ACTIVE', null, '1 min ago'], // Active, no heartbeat and created 1 min ago
			[false, 5, 'ACTIVE', '3 min ago'], // Active, heartbeat 3 min ago
			[false, 6, 'ACTIVE', null, '3 min ago'], // Active, no heartbeat and created 3 min ago
		];
	}

	/**
	 * @group api_campaigns_healthcheck
	 * @dataProvider api_campaigns_healthcheck_data
	 * @param mixed $expected_value
	 * @param mixed $campaign_id
	 * @param mixed $status
	 * @param mixed $heartbeattimestamp
	 * @param mixed $created
	 * @return void
	 */
	public function test_api_campaigns_healthcheck($expected_value, $campaign_id, $status = null, $heartbeattimestamp = 'now', $created = 'now') {
		$this->mock_function_param_value(
			'api_campaigns_checkidexists',
			[
				['params' => 99, 'return' => false]
			],
			true
		);
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [1 => 'status'], 'return' => $status],
				['params' => [1 => 'heartbeattimestamp'], 'return' => strtotime($heartbeattimestamp)],
				['params' => [1 => 'created'], 'return' => strtotime($created)],
			],
			null
		);

		$this->assertSameEquals($expected_value, api_campaigns_healthcheck($campaign_id));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_campaigns_gettimezone_data() {
		return [
			// Failures campaign does not exists
			[false, 99],

			// Success
			[new \DateTimeZone('Australia/Brisbane'), 1],
			[new \DateTimeZone('Australia/Sydney'), 2],

			// Success DEFAULT_TIMEZONE
			[new \DateTimeZone('Australia/Brisbane'), 10],
			[new \DateTimeZone('Australia/Brisbane'), 3],
		];
	}

	/**
	 * @group api_campaigns_gettimezone
	 * @dataProvider api_campaigns_gettimezone_data
	 * @param mixed $expected_value
	 * @param mixed $campaign_id
	 * @return void
	 */
	public function test_api_campaigns_gettimezone($expected_value, $campaign_id) {
		$this->mock_function_value('api_misc_audit', null);
		$this->mock_function_param_value(
			'api_campaigns_checkidexists',
			[
				['params' => 99, 'return' => false]
			],
			true
		);
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [1, 'timezone'], 'return' => 'Australia/Brisbane'],
				['params' => [2, 'timezone'], 'return' => 'Australia/Sydney'],
				['params' => [3, 'timezone'], 'return' => 'whatever'],
			],
			null
		);

		$this->assertEquals($expected_value, api_campaigns_gettimezone($campaign_id));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_campaigns_getclassification_data() {
		return [
			// Failures campaign does not exists
			[false, 99],

			// Success default values
			['telemarketing', 1], // phone
			['telemarketing', 2], // wash
			['exempt', 3], // sms
			['exempt', 4], // email

			// Success defined values
			['value_is_set', 10], // phone
			['value_is_set', 11], // wash
			['value_is_set', 12], // sms
			['value_is_set', 13], // email
		];
	}

	/**
	 * @group api_campaigns_getclassification
	 * @dataProvider api_campaigns_getclassification_data
	 * @param mixed $expected_value
	 * @param mixed $campaign_id
	 * @return void
	 */
	public function test_api_campaigns_getclassification($expected_value, $campaign_id) {
		$this->mock_function_param_value(
			'api_campaigns_checkidexists',
			[
				['params' => 99, 'return' => false]
			],
			true
		);
		$this->mock_function_param_value(
			'api_campaigns_setting_get_multi_byitem',
			[
				['params' => [1], 'return' => ['type' => 'phone']],
				['params' => [2], 'return' => ['type' => 'wash']],
				['params' => [3], 'return' => ['type' => 'sms']],
				['params' => [4], 'return' => ['type' => 'email']],
				['params' => [10], 'return' => ['type' => 'phone', 'classification' => 'value_is_set']],
				['params' => [11], 'return' => ['type' => 'wash', 'classification' => 'value_is_set']],
				['params' => [12], 'return' => ['type' => 'sms', 'classification' => 'value_is_set']],
				['params' => [13], 'return' => ['type' => 'email', 'classification' => 'value_is_set']],
			]
		);

		$this->assertEquals($expected_value, api_campaigns_getclassification($campaign_id));
	}

	/**
	 * @return array
	 */
	public function boost_spooler_permission_data_provider() {
		return [
			'user is not logged in' => [null, null, false],
			'user is logged in and is not technical admin' => [12, false, false],
			'user is logged in and is technical admin' => [12, true, true]
		];
	}

	/**
	 * @group api_campaigns_has_boost_spooler_permission
	 * @dataProvider boost_spooler_permission_data_provider
	 * @param mixed   $userid
	 * @param mixed   $istechadmin
	 * @param boolean $expected
	 * @return void
	 */
	public function test_api_campaigns_has_boost_spooler_permission($userid, $istechadmin, $expected) {
		if (is_null($userid)) {
			unset($_SESSION['userid']);
		} else {
			$_SESSION['userid'] = $userid;
		}

		$this->mock_function_param_value(
			'api_users_is_technical_admin',
			[
				['params' => [$userid], 'return' => $istechadmin]
			],
			null
		);

		$this->assertEquals($expected, api_campaigns_has_boost_spooler_permission());
		unset($_SESSION['userid']);
	}

	/**
	 * @group api_campaigns_update_lastsend
	 * @return void
	 */
	public function testApiCampaignsUpdateLastSendReturnsFalseForNonExistingCampaign() {
		$this->mock_function_value('api_campaigns_checkidexists', false);
		self::assertFalse(api_campaigns_update_lastsend(123));
	}

	/**
	 * @group api_campaigns_update_lastsend
	 * @return void
	 */
	public function testApiCampaignsUpdateLastSend() {
		$campaignId = 123;
		$this->mock_function_value('api_campaigns_checkidexists', true);
		$timestamp = 123445567;
		$this->mock_function_param_value(
			'microtime',
			[
				['params' => true, 'return' => $timestamp],
			],
			false
		);
		$this->mock_function_param_value(
			'api_campaigns_setting_set',
			[
				['params' => [$campaignId, 'lastsend', $timestamp], 'return' => true]
			],
			false
		);

		self::assertTrue(api_campaigns_update_lastsend($campaignId));
	}

	/**
	 * @return void
	 */
	public function testApiCampaignsDisableDownload() {
		$campaignId = 123;
		$this->mock_function_value('api_campaigns_checkidexists', true);

		$this->mock_function_param_value(
			'api_campaigns_setting_set',
			[
				['params' => [$campaignId, 'disabledownload', 1], 'return' => true]
			],
			false
		);

		self::assertTrue(api_campaigns_disable_download($campaignId));
	}

	/**
	 * @return void
	 */
	public function testApiCampaignsIsDownloadDisabledIfAdminUser() {
		$campaignId = 123;
		$this->mock_function_value('api_campaigns_checkidexists', true);
		$userId = 34;

		$this->mock_function_param_value(
			'api_users_is_admin_user',
			[
				['params' => [$userId], 'return' => true]
			],
			false
		);

		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [$campaignId, 'disabledownload'], 'return' => 1]
			],
			0
		);

		$this->assertFalse(api_campaigns_is_download_disabled($campaignId, $userId));
	}

	/**
	 * @return array
	 */
	public function apiCampaignsIsDownloadDisabledDataProvider() {
		return [
			'is disabled' => [1, true],
			'is not disabled' => [0, false]
		];
	}

	/**
	 * @dataProvider apiCampaignsIsDownloadDisabledDataProvider
	 * @param integer $is_disabled
	 * @param boolean $expected
	 * @return void
	 */
	public function testApiCampaignsIsDownloadDisabled($is_disabled, $expected) {
		$campaignId = 123;
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [$campaignId, 'disabledownload'], 'return' => $is_disabled]
			],
			0
		);

		$this->assertSameEquals($expected, api_campaigns_is_download_disabled($campaignId));
	}
}
