<?php
/**
 * CheckExistsModuleTestTrait
 * Trait to module test methods like Check Exists
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait to module test methods like Check Exists
 */
trait MethodsCheckExistsModuleTrait
{
	/**
	 * @group api_asset_checkidexists
	 * @group api_audio_checkidexists
	 * @group api_campaigns_checkidexists
	 * @group api_cron_checkidexists
	 * @return void
	 */
	public function test_api_checkidexists() {
		$test_function = strtolower(sprintf('api_%s_checkidexists', self::$type));

		if (function_exists($test_function)) {
			$expected_id = $this->get_expected_next_id(self::$type);
			$expected_name = uniqid($test_function);

			$this->assertFalse($test_function(null));
			$this->assertFalse($test_function(false));
			$this->assertFalse($test_function(''));
			$this->assertFalse($test_function(0));
			$this->assertFalse($test_function($expected_id));

			$this->assertEquals($expected_id, $this->create_new_by_type(self::$type));
			$this->assertTrue($test_function($expected_id));

			// purge
			$this->purge_by_type(self::$type);
		}
	}

	/**
	 * @group api_asset_checknameexists
	 * @group api_audio_checknameexists
	 * @group api_campaigns_checknameexists
	 * @group api_cron_checknameexists
	 * @return void
	 */
	public function test_api_checknameexists() {
		$test_function = strtolower(sprintf('api_%s_checknameexists', self::$type));

		if (function_exists($test_function)) {
			$expected_id = $this->get_expected_next_id(self::$type);

			$this->assertFalse($test_function(null));
			$this->assertFalse($test_function(false));
			$this->assertFalse($test_function(''));
			$this->assertFalse($test_function(0));
			$this->assertFalse($test_function(uniqid($test_function)));

			$this->assertEquals($expected_id, $this->create_new_by_type(self::$type));
			$function_get = strtolower(sprintf('api_%s_setting_getsingle', self::$type));
			$this->assertEquals(
				$expected_id,
				$test_function($function_get($expected_id, 'name'))
			);

			// purge
			$this->purge_by_type(self::$type);
		}
	}
}
