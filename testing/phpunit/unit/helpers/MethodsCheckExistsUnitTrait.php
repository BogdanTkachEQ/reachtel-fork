<?php
/**
 * MethodsCheckExistsUnitTrait
 * Trait to unit test methods like Check Exists
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\helpers;

/**
 * Trait to unit test methods like Check Exists
 */
trait MethodsCheckExistsUnitTrait
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function test_api_checkidexists_data() {
		return [
			// Failures id
			[false, null],
			[false, false],
			[false, ''],

			// Failures api_keystore_get
			[false, 1, false],

			// Success
			[true],
			[true, '78'],
			[true, '78', null]
		];
	}

	/**
	 * @group api_asset_checkidexists
	 * @group api_audio_checkidexists
	 * @group api_campaigns_checkidexists
	 * @group api_cron_checkidexists
	 * @dataProvider test_api_checkidexists_data
	 * @param boolean $expected_value
	 * @param mixed   $id
	 * @param mixed   $keystore_get
	 * @return void
	 */
	public function test_api_checkidexists($expected_value, $id = 1, $keystore_get = 'value') {
		$test_function = strtolower(sprintf('api_%s_checkidexists', self::TYPE));

		if (function_exists($test_function)) {
			$this->mock_function_value(strtolower(sprintf('api_%s_setting_getsingle', self::TYPE)), $keystore_get);
			$this->assertSameEquals($expected_value, $test_function($id));
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function test_api_checknameexists_data() {
		return [
			[false, false],
			[true, true],
		];
	}

	/**
	 * @group api_asset_checknameexists
	 * @group api_audio_checknameexists
	 * @group api_campaigns_checknameexists
	 * @group api_cron_checknameexists
	 * @dataProvider test_api_checknameexists_data
	 * @param boolean $expected_value
	 * @param boolean $keystore_checkkeyexists
	 * @return void
	 */
	public function test_api_checknameexists($expected_value, $keystore_checkkeyexists) {
		$test_function = strtolower(sprintf('api_%s_checknameexists', self::TYPE));

		if (function_exists($test_function)) {
			$this->mock_function_value('api_keystore_checkkeyexists', $keystore_checkkeyexists);
			$this->assertSameEquals($expected_value, $test_function('name'));
		}
	}

	/**
	 * @group api_asset_setting_getall
	 * @group api_audio_setting_getall
	 * @group api_campaigns_setting_getall
	 * @group api_cron_setting_getall
	 * @return void
	 */
	public function test_api_setting_getall() {
		$test_function = strtolower(sprintf('api_%s_setting_getall', self::TYPE));

		if (function_exists($test_function)) {
			$this->mock_function_value('api_keystore_getnamespace', 'whatever');
			$this->assertSameEquals('whatever', $test_function(1));
		}
	}
}
