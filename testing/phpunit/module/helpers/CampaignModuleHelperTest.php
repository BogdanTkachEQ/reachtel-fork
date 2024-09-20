<?php
/**
 * CampaignModuleHelperTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

use Exception;

/**
 * Campaign Module Helper Test
 */
class CampaignModuleHelperTest extends AbstractModuleHelperTest
{
	use CampaignModuleHelper;
	use UserModuleHelper;

	const CAMPAIGN_TYPE_KEY = '__TYPE__';
	const CAMPAIGN_DATA_KEY = '__DATA__';
	const EXPECTED_TYPE = 'CAMPAIGNS';

	private $campaign_type = null;

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function create_new_campaign_data() {
		return array_merge(
			[
				// Failure campaign name too short
				[false, '5'],

				// Failure campaign name too long
				[false, uniqid(' test create new campaign campaign name too long123456789012345')], // str length 76 chars

				// Failure campaign name chars
				[false, '#wrong ch@rs#'],

				// Failure user id
				[false, null, 99999],

				// Failure type
				[false, null, 'wrong_type'],
			],
			// Success by campaign types
			$this->get_data_with_all_types([true, uniqid('test create new ' . self::CAMPAIGN_TYPE_KEY . ' campaign'), self::CAMPAIGN_TYPE_KEY]),
			// Success by campaign types
			$this->get_data_with_all_types([true, null, self::CAMPAIGN_TYPE_KEY]),
			// Success by campaign types and new user
			$this->get_data_with_all_types([true, null, self::CAMPAIGN_TYPE_KEY, true])
		);
	}

	/**
	 * @group create_new_campaign
	 * @dataProvider create_new_campaign_data
	 * @param boolean $expected_success
	 * @param mixed   $campaign_name
	 * @param mixed   $type
	 * @param mixed   $user_id
	 * @return void
	 */
	public function test_create_new_campaign($expected_success, $campaign_name = null, $type = null, $user_id = null) {
		// Set campaign_type for get default expected values of campaign type
		$this->campaign_type = $type;

		if ($user_id === true) {
			$user_id = $this->create_new_user();
		}
		$this->do_test_create_new($expected_success, [$campaign_name, $type, $user_id]);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function create_new_campaign_settings_data() {
		return [
			[true, []],
			[true, ['billingmonth' => 'perpetual']],
			[true, ['billingmonth' => date('Y-m'), 'ordered' => 'on']],
			[true, $this->get_default_expected_values(self::get_campaign_type())],
		];
	}

	/**
	 * @group create_new_campaign
	 * @dataProvider create_new_campaign_settings_data
	 * @param boolean $expected_success
	 * @param array   $override_settings
	 * @return void
	 */
	public function test_create_new_campaign_settings($expected_success, array $override_settings) {
		$expected_id = $this->get_expected_next_campaign_id();
		$this->assertSameEquals(
			$expected_id,
			$this->create_new_campaign(uniqid('campaign'), null, null, $override_settings)
		);

		// assert settings
		$settings = api_campaigns_setting_getall($expected_id);
		foreach ($override_settings as $setting_name => $value) {
			$this->assertEquals($value, $settings[$setting_name]);
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function add_campaign_targets_data() {
		return array_merge(
			$this->get_data_with_all_types([self::CAMPAIGN_TYPE_KEY]),
			$this->get_data_with_all_types([self::CAMPAIGN_TYPE_KEY, self::CAMPAIGN_DATA_KEY])
		);
	}

	/**
	 * @group add_campaign_targets
	 * @dataProvider add_campaign_targets_data
	 * @param string $campaign_type
	 * @param mixed  $targets
	 * @return void
	 */
	public function test_add_campaign_targets($campaign_type, $targets = null) {
		$campaign_id = $this->create_new_campaign(uniqid('targets'), $campaign_type);
		$this->assertTrue($this->add_campaign_targets($campaign_id, $targets));
	}

	/**
	 * @group get_campaign_types
	 * @return void
	 */
	public function test_get_campaign_types() {
		$this->assertSameEquals(
			['phone', 'wash', 'sms', 'email'],
			$this->get_campaign_types()
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function add_campaign_call_result_data() {
		return array_merge(
			[
				// Failures wrong value
				[null, 'phone', 'wrong_value', Exception::class, "Yaml config key 'wrong_value' not found"],

				// Failures wrong type - value
				[null, 'wash', 'sent', Exception::class, "Call results key 'sent' is not valid for campign type 'wash'"],
				[null, 'sms', 'answer', Exception::class, "Call results key 'answer' is not valid for campign type 'sms'"],
				[null, 'phone', 'sent', Exception::class, "Call results key 'sent' is not valid for campign type 'phone'"],
				[null, 'email', 'congestion', Exception::class, "Call results key 'congestion' is not valid for campign type 'email'"],
			],
			// Success DNC all types
			$this->get_data_with_all_types([true, self::CAMPAIGN_TYPE_KEY, 'dnc']),
			// Success
			[
				[true, 'email', 'sent'],
				[true, 'phone', 'generated'],
				[true, 'wash', 'generated'],
				[true, 'phone', 'answer'],
				[true, 'wash', 'answer'],
				[true, 'phone', 'no_answer'],
				[true, 'wash', 'no_answer'],
				[true, 'phone', 'cancel'],
				[true, 'wash', 'cancel'],
				[true, 'phone', 'disconnected'],
				[true, 'wash', 'disconnected'],
				[true, 'phone', 'congestion'],
				[true, 'wash', 'congestion'],
				[true, 'phone', 'busy'],
				[true, 'wash', 'busy'],
				[true, 'phone', 'chanunavail'],
				[true, 'wash', 'chanunavail'],
				[true, 'phone', 'hangup'],
				[true, 'wash', 'hangup']
			]
		);
	}

	/**
	 * @group add_campaign_call_result
	 * @dataProvider add_campaign_call_result_data
	 * @param boolean $expected_success
	 * @param string  $campaign_type
	 * @param string  $value
	 * @param mixed   $expected_exception
	 * @param mixed   $expected_message
	 * @return void
	 */
	public function test_add_campaign_call_result($expected_success, $campaign_type, $value, $expected_exception = null, $expected_message = null) {
		$this->setExpectedException($expected_exception, $expected_message);
		$campaign_id = $this->create_new_campaign(uniqid('callresult'), $campaign_type);
		$this->assertTrue($this->add_campaign_targets($campaign_id, $this->get_target_fixtures_by_type($campaign_type)));
		$targets = api_targets_listall($campaign_id);
		$call_results = $this->add_campaign_call_result(date('Y-m'), key($targets), $value);
		$this->assertInternalType('array', $call_results);
		$this->assertNotEmpty($call_results);

		// test with perpetual and month not current
		$times = ['last day of 1 month ago', 'last day of 2 months ago', '1 year ago'];
		$this->assertTrue(api_campaigns_setting_set($campaign_id, 'billingmonth', 'perpetual'));
		foreach ($times as $time) {
			$call_results = $this->add_campaign_call_result(date('Y-m', strtotime($time)), key($targets), $value);
			$this->assertInternalType('array', $call_results);
			$this->assertEmpty($call_results);
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function get_target_fixtures_by_type_data() {
		return $this->get_data_with_all_types([self::CAMPAIGN_TYPE_KEY]);
	}

	/**
	 * @group get_target_fixtures_by_type
	 * @dataProvider get_target_fixtures_by_type_data
	 * @param string $type
	 * @return void
	 */
	public function test_get_target_fixtures_by_type($type) {
		$fixtures = $this->get_target_fixtures_by_type($type);
		$this->assertInternalType('array', $fixtures);
		$this->assertCount(2, $fixtures);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function test_get_call_results_value_map_data() {
		return [
			// Failures
			[null, 'whatever', null, "Yaml config key 'whatever' not found"],
			[null, 'generated', 'email', "Call results key 'generated' is not valid for campign type 'email'"],

			// Success
			[['values' => ['DNC']], 'dnc', null],
			[
				[
					'types' => ['phone', 'wash'],
					'values' => ['GENERATED'],
					'responses_data' => ['wash' => [['status', 'CONNECTED']]],
				],
				'generated',
				'phone'
			],
			[
				[
					'types' => ['phone', 'wash'],
					'values' => ['GENERATED'],
					'responses_data' => ['wash' => [['status', 'CONNECTED']]],
				],
				'generated',
				'wash'
			],
		];
	}

	/**
	 * @group get_call_results_value_map
	 * @dataProvider test_get_call_results_value_map_data
	 * @param mixed  $expected_value
	 * @param string $key
	 * @param mixed  $campaign_type
	 * @param mixed  $expected_exception
	 * @return void
	 */
	public function test_get_call_results_value_map($expected_value, $key, $campaign_type = null, $expected_exception = false) {
		if ($expected_exception) {
			$this->setExpectedException(Exception::class, $expected_exception);
		}
		$this->assertSameEquals(
			$expected_value,
			$this->get_call_results_value_map($key, $campaign_type)
		);
	}

	/**
	 * Overrieds get_default_expected_values() to get the specific campaign types values
	 *
	 * @param string $type
	 * @return mixed
	 */
	protected function get_default_expected_values($type) {
		$default_expected_values = parent::get_default_expected_values($type);
		if ($this->campaign_type) {
			$type = strtolower($this->type_plural($type, false));
			$default_expected_values = array_merge(
				$default_expected_values,
				self::get_config("helpers.{$type}.default_expected_type_values.{$this->campaign_type}")
			);
		}

		return $default_expected_values;
	}

	/**
	 * Get a data values array from defined types
	 *
	 * @codeCoverageIgnore
	 * @param array $data_row
	 * @param mixed $not_types
	 * @return array
	 */
	private function get_data_with_all_types(array $data_row, $not_types = null) {
		$datas = array_map(
			function($type) use ($data_row, $not_types) {
				if ($not_types && in_array($type, (array) $not_types)) {
					return null;
				}

				return array_map(
					function($value) use($type) {
						if (is_string($value)) {
							if ($value == self::CAMPAIGN_DATA_KEY) {
								$value = $this->get_target_fixtures_by_type($type);
							} else {
								$value = str_replace(self::CAMPAIGN_TYPE_KEY, $type, $value);
							}
						}
						return $value;
					},
					$data_row
				);
			},
			$this->get_campaign_types()
		);

		return array_filter($datas);
	}
}
