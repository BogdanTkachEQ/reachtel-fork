<?php
/**
 * ApiSmsUnitTest
 * Unit test for api_sms.php
 *
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Class ApiSmsUnitTest
 */
class ApiSmsUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	public function api_sms_fetch_providers_data_provider() {
		return [
			'when account id is not set' => [
				[
					'type' => 'aumobile',
					'destination' => '0412345678'
				],
				null,
				null,
				['destination' => ['type' => 'aumobile', 'destination' => '0412345678']],
				['aumobile']
			],
			'when traffic on shore setting is "Required"' => [
				[
					'type' => 'aumobile',
					'destination' => '0412345678'
				],
				123,
				SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_REQUIRED,
				['destination' => ['type' => 'aumobile', 'destination' => '0412345678']],
				['aumobile', SMS_SUPPLIER_CAPABILITY_TRAFFIC_ON_SHORE]
			],
			'when traffic on shore setting is "Preferred"' => [
				[
					'type' => 'aumobile',
					'destination' => '0412345678'
				],
				123,
				SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_PREFERRED,
				[
					'destination' => [
						'type' => 'aumobile',
						'destination' => '0412345678'
					],
					'sort_by_capabilities' => [SMS_SUPPLIER_CAPABILITY_TRAFFIC_ON_SHORE]
				],
				['aumobile']
			],
			'when traffic on shore setting is "Not required"' => [
				[
					'type' => 'aumobile',
					'destination' => '0412345678'
				],
				123,
				SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_NOT_REQUIRED,
				[
					'destination' => [
						'type' => 'aumobile',
						'destination' => '0412345678'
					]
				],
				['aumobile']
			],
		];
	}

	/**
	 * @group api_sms_fetch_providers
	 * @param array   $formated_destination_details
	 * @param integer $accountid
	 * @param string  $traffic_onshore_setting
	 * @param array   $expected_options
	 * @param array   $expected_capabilities
	 * @dataProvider api_sms_fetch_providers_data_provider
	 * @return void
	 */
	public function test_api_sms_fetch_providers(
		array $formated_destination_details,
		$accountid,
		$traffic_onshore_setting,
		array $expected_options,
		array $expected_capabilities
	) {
		if ($accountid) {
			$this->mock_function_param_value(
				'api_sms_dids_setting_getsingle',
				[
					['params' => [$accountid, SMS_DID_SETTING_USE_ON_SHORE_PROVIDER], 'return' => $traffic_onshore_setting]
				],
				false
			);
		}

		$expected_providers = [1, 2, 3];

		$this->mock_function_param_value(
			'api_sms_supplier_select',
			[
				['params' => [$expected_capabilities, $expected_options], 'return' => $expected_providers]
			],
			[6]
		);

		$this->mock_function_value('usleep', true);

		$this->assertSameEquals($expected_providers, api_sms_fetch_providers($formated_destination_details, $accountid));
	}

	/**
	 * @group api_sms_get_on_shore_only_providers_options
	 * @return void
	 */
	public function test_api_sms_get_on_shore_only_providers_options() {
		$this->assertSameEquals(
			[
				SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_NOT_REQUIRED,
				SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_REQUIRED,
				SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_PREFERRED
			],
			api_sms_get_on_shore_only_providers_options()
		);
	}

	/**
	 * @return array
	 */
	public function api_sms_dids_setting_getall_data_provider() {
		return [
			'without return defaults' => [
				[
					'setting1' => 123,
					'setting2' => 345
				],
				false,
				[
					'setting1' => 123,
					'setting2' => 345
				],
				[]
			],
			'with return defaults' => [
				[
					'setting1' => 123,
					'setting2' => 345
				],
				true,
				[
					'setting1' => 123,
					'setting2' => 345,
					'setting3' => 'xyz',
					'setting4' => 'asd'
				],
				[
					'setting3' => 'xyz',
					'setting4' => 'asd'
				]
			]
		];
	}

	/**
	 * @group api_sms_dids_setting_getall
	 * @param array   $settings
	 * @param boolean $return_defaults
	 * @param array   $expected_settings
	 * @param array   $default_settings
	 * @dataProvider api_sms_dids_setting_getall_data_provider
	 * @return void
	 */
	public function test_api_sms_dids_setting_getall(array $settings, $return_defaults, array $expected_settings, array $default_settings) {
		$this->mock_function_value(
			'api_keystore_getnamespace',
			$settings
		);

		if ($return_defaults) {
			$this->mock_function_value(
				'api_sms_settings_defaults',
				$default_settings
			);
		}

		$this->assertSameEquals($expected_settings, api_sms_dids_setting_getall(123, $return_defaults));
	}

	/**
	 * @group api_sms_settings_defaults
	 * @return void
	 */
	public function test_api_sms_settings_defaults() {
		$this->assertSameEquals(
			[
				SMS_DID_SETTING_USE_ON_SHORE_PROVIDER => SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_NOT_REQUIRED
			],
			api_sms_settings_defaults()
		);
	}

	/**
	 * @return array
	 */
	public function api_sms_get_received_sms_history_data_provider() {
		return [
			'No sms dids provided' => [
				[], [], 7, '', [], null, false
			],
			'Destination Invalid' => [
				[123], ['invalid key' => 123], 6, '', [], null, false
			],
			'Without destination' => [
				[123, 345],
				[],
				5,
				'SELECT * FROM `sms_received` WHERE `sms_account` IN (?,?) AND `timestamp` > DATE_SUB(NOW(), INTERVAL ? DAY)',
				[123, 345, 5],
				[
					['sms_account' => 123, 'timestamp' => '2018-10-12 11:05:05', 'from' => '61412345678'],
					['sms_account' => 345, 'timestamp' => '2018-10-05 1:15:08', 'from' => '61442489865']
				],
				[
					['sms_account' => 123, 'timestamp' => '2018-10-12 11:05:05', 'from' => '61412345678'],
					['sms_account' => 345, 'timestamp' => '2018-10-05 1:15:08', 'from' => '61442489865']
				]
			],
			'With valid destination' => [
				[444, 555],
				['fnn' => '61412897678', 'destination' => '0412897678'],
				6,
				'SELECT * FROM `sms_received` WHERE `sms_account` IN (?,?) AND `timestamp` > DATE_SUB(NOW(), INTERVAL ? DAY) AND `from` IN (?,?)',
				[444, 555, 6, '61412897678', '0412897678'],
				[
					['sms_account' => 444, 'timestamp' => '2018-09-12 11:05:05', 'from' => '61412897678'],
					['sms_account' => 555, 'timestamp' => '2018-09-05 1:15:08', 'from' => '0412897678']
				],
				[
					['sms_account' => 444, 'timestamp' => '2018-09-12 11:05:05', 'from' => '61412897678'],
					['sms_account' => 555, 'timestamp' => '2018-09-05 1:15:08', 'from' => '0412897678']
				]
			]
		];
	}

	/**
	 * @group api_sms_get_received_sms_history
	 * @dataProvider api_sms_get_received_sms_history_data_provider
	 * @param array         $didids
	 * @param array         $destination
	 * @param integer       $interval
	 * @param string        $sql
	 * @param array         $parameters
	 * @param array|null    $adoRecords
	 * @param array|boolean $expected
	 * @return void
	 */
	public function test_api_sms_get_received_sms_history(
		array $didids,
		array $destination,
		$interval,
		$sql,
		array $parameters,
		$adoRecords,
		$expected
	) {
		if ($adoRecords !== null) {
			$this->remove_mocked_functions('api_db_query_read');
			$this->mock_function_param_value(
				'api_db_query_read',
				[
					['params' => [$sql, $parameters], 'return' => $this->mock_ado_records($adoRecords)]
				],
				[]
			);
		}

		$return = api_sms_get_received_sms_history($didids, $destination, $interval);

		$this->assertSameEquals($expected, $return);
	}

	/**
	 * @return array
	 */
	public function api_sms_handle_dnc_opt_in_out_data_provider() {
		return [
			'when content is stop' => ['stop', '61412457896', ['phone', '61412457896', 123]],
			'when content is opt out' => ['opt out', '61412457896', ['phone', '61412457896', 123]],
			'when content is optout' => ['optout', '61412457896', ['phone', '61412457896', 123]],
			'when content is do not text' => ['do not text', '61412457896', ['phone', '61412457896', 123]],
			'when content is unsubscribe' => ['unsubscribe', '61412457896', ['phone', '61412457896', 123]],
			'when content is not an opt out content' => ['random content', '61412457896', null],
			'when content is opt out but e164 is null' => ['optout', null, null],
			'when content is opt in' => ['opt in', '61412457896', ['phone', '61412457896', 123], true],
			'when content is optin' => ['optin', '61412457896', ['phone', '61412457896', 123], true],
			'when content is subscribe' => ['subscribe', '61412457896', ['phone', '61412457896', 123], true],
		];
	}

	/**
	 * @dataProvider api_sms_handle_dnc_opt_in_out_data_provider
	 * @param string  $contents
	 * @param string  $e164
	 * @param mixed   $expected_args
	 * @param boolean $optin
	 * @return void
	 */
	public function test_api_sms_handle_dnc_opt_in_out($contents, $e164, $expected_args, $optin = false) {
		$this->listen_mocked_function('api_restrictions_donotcontact_add');
		$this->mock_function_value('api_restrictions_donotcontact_add', true);

		$this->listen_mocked_function('api_restrictions_donotcontact_remove_single');
		$this->mock_function_value('api_restrictions_donotcontact_remove_single', true);

		$this->assertTrue(api_sms_handle_dnc_opt_in_out(123, $contents, $e164));
		$optout_called_params = $this
			->fetchListenedMockFunctionParamValues('api_restrictions_donotcontact_add');

		$optin_called_params = $this
			->fetchListenedMockFunctionParamValues('api_restrictions_donotcontact_remove_single');

		if ($optin) {
			$this->assertSameEquals($optin_called_params[0]['args'], $expected_args);
			$this->assertEmpty($optout_called_params[0]['args']);
		} else {
			$this->assertSameEquals($optout_called_params[0]['args'], $expected_args);
			$this->assertEmpty($optin_called_params[0]['args']);
		}

		$this->remove_mocked_functions('api_restrictions_donotcontact_add');
		$this->remove_mocked_functions('api_restrictions_donotcontact_remove_single');
	}
}
