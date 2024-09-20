<?php
/**
 * MethodsSettingsUnitTrait
 * Trait to unit test methods like Settings
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\helpers;

use Exception;

/**
 * Trait to unit test settings methods
 */
trait MethodsSettingsUnitTrait
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_setting_set_data() {
		return [
			[false, false],
			[true, true],
		];
	}

	/**
	 * @group api_asset_setting_set
	 * @group api_audio_setting_set
	 * @group api_campaigns_setting_set
	 * @group api_cron_setting_set
	 * @dataProvider api_setting_set_data
	 * @param boolean $expected_value
	 * @param boolean $keystore_set
	 * @return void
	 */
	public function test_api_setting_set($expected_value, $keystore_set) {
		$test_function = strtolower(sprintf('api_%s_setting_set', self::TYPE));

		if (function_exists($test_function)) {
			$this->mock_function_value('api_keystore_set', $keystore_set);
			$this->assertSameEquals($expected_value, $test_function(1, 'setting', 'value'));
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_setting_delete_single_data() {
		return [
			[false, false],
			[true, true]
		];
	}

	/**
	 * @group api_asset_setting_delete_single
	 * @group api_audio_setting_delete_single
	 * @group api_campaigns_setting_delete_single
	 * @group api_cron_setting_delete_single
	 * @dataProvider api_setting_delete_single_data
	 * @param boolean $expected_value
	 * @param boolean $keystore_delete
	 * @return void
	 */
	public function test_api_setting_delete_single($expected_value, $keystore_delete) {
		$test_function = strtolower(sprintf('api_%s_setting_delete_single', self::TYPE));

		if (function_exists($test_function)) {
			$this->mock_function_value('api_keystore_delete', $keystore_delete);
			$this->assertSameEquals($expected_value, $test_function(1, 'setting'));
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_setting_delete_getsingle() {
		return [
			[false, false],
			[true, true],
		];
	}

	/**
	 * @group api_asset_setting_getsingle
	 * @group api_audio_setting_getsingle
	 * @group api_campaigns_setting_getsingle
	 * @group api_cron_setting_getsingle
	 * @dataProvider api_setting_delete_getsingle
	 * @param boolean $expected_value
	 * @param boolean $keystore_get
	 * @return void
	 */
	public function test_api_setting_getsingle($expected_value, $keystore_get) {
		$test_function = strtolower(sprintf('api_%s_setting_getsingle', self::TYPE));

		if (function_exists($test_function)) {
			$this->mock_function_value('api_keystore_get', $keystore_get);
			$this->assertSameEquals($expected_value, $test_function(1, 'setting'));
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_setting_boolean_data() {
		return [
			['cas', [1, 'item', 'old value', 'new value']],
			['get_multi_byitem', [1, ['item1', 'item2']]],
			['get_multi_byid', [[1, 2], 'item']],
			['getall', [1], 'api_keystore_getnamespace'],
			['increment', [1, 'setting']]
		];
	}

	/**
	 * @group api_asset_setting_getall
	 * @group api_campaigns_setting_cas
	 * @group api_campaigns_setting_get_multi_byitem
	 * @group api_campaigns_setting_get_multi_byid
	 * @group api_campaigns_setting_getall
	 * @group api_campaigns_setting_increment
	 * @group api_cron_setting_get_multi_byitem
	 * @group api_cron_setting_getall
	 * @group api_cron_setting_increment
	 * @dataProvider api_setting_boolean_data
	 * @param string $function_suffix
	 * @param array  $params
	 * @param mixed  $keystore_function
	 * @return void
	 * @throws Exception If keystore function does not exists.
	 */
	public function test_api_setting_keystore_calls($function_suffix, array $params = [], $keystore_function = null) {
		$test_function = strtolower(sprintf('api_%s_setting_%s', self::TYPE, $function_suffix));

		// For group code coverage, we check we are running the right function group
		$group = self::get_phpunit_option('group');

		if (function_exists($test_function)
			&& (!$group || ($group && $group === $test_function))) {
			$keystore_function = $keystore_function ? : "api_keystore_{$function_suffix}";

			if (!function_exists($keystore_function)) {
				throw new Exception("keystore function {$keystore_function}() does not exists."); // @codeCoverageIgnore
			} // @codeCoverageIgnore

			foreach ([false, true] as $boolean_value) {
				$this->remove_mocked_functions($keystore_function);
				$this->mock_function_value($keystore_function, $boolean_value);
				$this->assertSameEquals(
					$boolean_value,
					call_user_func_array($test_function, $params)
				);
			}
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_setting_delete_all() {
		return [
			[false, false],
			[true, true],
		];
	}

	/**
	 * @group api_cron_setting_delete_all
	 * @dataProvider api_setting_delete_all
	 * @param boolean $expected_value
	 * @param boolean $keystore_purge
	 * @return void
	 */
	public function test_api_setting_delete_all($expected_value, $keystore_purge) {
		$test_function = strtolower(sprintf('api_%s_setting_delete_all', self::TYPE));

		if (function_exists($test_function)) {
			$this->mock_function_value('api_keystore_purge', $keystore_purge);
			$this->assertSameEquals($expected_value, $test_function(1));
		}
	}
}
