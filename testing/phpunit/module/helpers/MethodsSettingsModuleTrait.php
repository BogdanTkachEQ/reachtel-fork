<?php
/**
 * SettingsModuleTestTrait
 * Trait to module test methods like Settings
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait to module test methods like Settings
 */
trait MethodsSettingsModuleTrait
{
	/**
	 * @group api_asset_setting_set
	 * @group api_audio_setting_set
	 * @group api_campaigns_setting_set
	 * @group api_cron_setting_set
	 * @return void
	 */
	public function test_api_setting_set() {
		$test_function = strtolower(sprintf('api_%s_setting_set', self::$type));

		if (function_exists($test_function)) {
			$expected_id = $this->get_expected_next_id(self::$type) - 1;
			$setting_id = uniqid();
			$setting_value = uniqid($test_function);

			$this->assertTrue($test_function($expected_id, $setting_id, $setting_value));

			// Assert value has been set
			$function = strtolower(sprintf('api_%s_setting_getsingle', self::$type));
			$this->assertEquals($setting_value, $function($expected_id, $setting_id));

			// delete created value
			$this->assertTrue(api_keystore_delete(self::$type, $expected_id, $setting_id));
		}
	}

	/**
	 * @group api_asset_setting_getsingle
	 * @group api_audio_setting_getsingle
	 * @group api_campaigns_setting_getsingle
	 * @group api_cron_setting_getsingle
	 * @return void
	 */
	public function test_api_setting_getsingle() {
		$test_function = strtolower(sprintf('api_%s_setting_getsingle', self::$type));

		if (function_exists($test_function)) {
			$expected_id = $this->get_expected_next_id(self::$type) - 1;
			$setting_id = uniqid();
			$setting_value = uniqid($test_function);

			$this->assertFalse($test_function($expected_id, $setting_id));
			$function = strtolower(sprintf('api_%s_setting_set', self::$type));
			$function($expected_id, $setting_id, $setting_value);
			$this->assertEquals($setting_value, $test_function($expected_id, $setting_id));

			// delete created value
			$this->assertTrue(api_keystore_delete(self::$type, $expected_id, $setting_id));
		}
	}

	/**
	 * @group api_asset_setting_increment
	 * @group api_campaigns_setting_increment
	 * @group api_cron_setting_increment
	 * @return void
	 */
	public function test_api_setting_increment() {
		$test_function = strtolower(sprintf('api_%s_setting_increment', self::$type));

		if (function_exists($test_function)) {
			$expected_id = $this->get_expected_next_id(self::$type) - 1;
			$setting_id = uniqid();
			$setting_value = rand(0, 100);

			$this->assertFalse($test_function($expected_id, $setting_id));
			$function = strtolower(sprintf('api_%s_setting_set', self::$type));

			$function($expected_id, $setting_id, $setting_value);
			// Loop increment test
			for ($x = 1; $x <= $setting_value + 1; $x++) {
				$this->assertEquals($setting_value + $x, $test_function($expected_id, $setting_id));
			}

			// delete created value
			$this->assertTrue(api_keystore_delete(self::$type, $expected_id, $setting_id));
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
		$test_function = strtolower(sprintf('api_%s_setting_getall', self::$type));

		if (function_exists($test_function)) {
			$expected_id = $this->get_expected_next_id(self::$type);

			$all_settings = $test_function($expected_id);
			$this->assertInternalType('array', $all_settings);
			$this->assertEmpty($all_settings);

			// Create a new type item in db
			$type_id = $this->create_new_by_type(self::$type);

			// create an item
			$all_settings = $test_function($type_id);
			$this->assertInternalType('array', $all_settings);
			$this->assertNotEmpty($all_settings);

			// delete previsouly created item
			$function = strtolower(sprintf('api_%s_delete', self::$type));
			$this->assertTrue($function($type_id));
		}
	}

	/**
	 * @group api_asset_setting_get_multi_byitem
	 * @group api_campaigns_setting_get_multi_byitem
	 * @group api_cron_setting_get_multi_byitem
	 * @return void
	 */
	public function test_api_setting_get_multi_byitem() {
		$test_function = strtolower(sprintf('api_%s_setting_get_multi_byitem', self::$type));

		if (function_exists($test_function)) {
			$expected_id = $this->get_expected_next_id(self::$type);
			$default_expected_type_keys = array_keys($this->get_default_expected_values(self::$type));
			$all_type_keys = array_merge($default_expected_type_keys, ['whatever']);

			$all_settings_by_item = $test_function($expected_id, $all_type_keys);
			$this->assertInternalType('array', $all_settings_by_item);
			$this->assertEmpty($all_settings_by_item);

			// Create a new type item in db
			$type_id = $this->create_new_by_type(self::$type);

			$all_settings_by_item = $test_function($type_id, $all_type_keys);
			$this->assertInternalType('array', $all_settings_by_item);
			$this->assertNotEmpty($all_settings_by_item);
			foreach ($default_expected_type_keys as $type_key) {
				$this->assertArrayHasKey($type_key, $all_settings_by_item);
			}
			$this->assertArrayNotHasKey('whatever', $all_settings_by_item);
		}
	}

	/**
	 * @group api_asset_setting_get_multi_byitem
	 * @group api_campaigns_setting_get_multi_byid
	 * @return void
	 */
	public function test_api_setting_get_multi_byid() {
		$test_function = strtolower(sprintf('api_%s_setting_get_multi_byid', self::$type));

		if (function_exists($test_function)) {
			$expected_id = $this->get_expected_next_id(self::$type);
			$default_expected_type = $this->get_default_expected_values(self::$type);
			$this->assertNotEmpty($default_expected_type);
			$setting_name = key($default_expected_type);
			$expected_setting_value = current($default_expected_type);

			$all_settings_by_id = $test_function([$expected_id], $setting_name);
			$this->assertInternalType('array', $all_settings_by_id);
			$this->assertEmpty($all_settings_by_id);

			$type_id = $this->create_new_by_type(self::$type);

			$all_settings_by_id = $test_function([$type_id, $type_id + 1], $setting_name);
			$this->assertInternalType('array', $all_settings_by_id);
			$this->assertEquals(
				[$type_id => $expected_setting_value],
				$all_settings_by_id,
				self::$type . " setting '$setting_name' failure:"
			);

			$type_id_2 = $this->create_new_by_type(self::$type);

			$all_settings_by_id = $test_function([$type_id_2, $type_id, $type_id + 1], $setting_name);
			$this->assertInternalType('array', $all_settings_by_id);
			$this->assertEquals(
				[$type_id => $expected_setting_value, $type_id_2 => $expected_setting_value],
				$all_settings_by_id,
				"Setting '$setting_name' failure:"
			);
		}
	}

	/**
	 * @group api_asset_setting_delete_single
	 * @group api_audio_setting_delete_single
	 * @group api_campaigns_setting_delete_single
	 * @group api_cron_setting_delete_single
	 * @return void
	 */
	public function test_api_setting_delete_single() {
		$test_function = strtolower(sprintf('api_%s_setting_delete_single', self::$type));

		if (function_exists($test_function)) {
			$expected_id = $this->get_expected_next_id(self::$type);
			$setting_id = uniqid();

			$this->assertFalse($test_function($expected_id, $setting_id));
			$function = strtolower(sprintf('api_%s_setting_set', self::$type));
			$function($expected_id, $setting_id, $test_function);
			$this->assertTrue($test_function($expected_id, $setting_id));
		}
	}

	/**
	 * @group api_cron_setting_delete_all
	 * @return void
	 */
	public function test_api_setting_delete_all() {
		$test_function = strtolower(sprintf('api_%s_setting_delete_all', self::$type));

		if (function_exists($test_function)) {
			$expected_id = $this->get_expected_next_id(self::$type);
			$setting_id = uniqid();

			$function_get_all = strtolower(sprintf('api_%s_setting_getall', self::$type));
			$this->assertEmpty($function_get_all($expected_id));

			$this->assertTrue($test_function($expected_id));
			$function_set = strtolower(sprintf('api_%s_setting_set', self::$type));
			$nb = rand(10, 20);
			for ($i = 1; $i <= $nb; $i++) {
				$function_set($expected_id, "{$setting_id}_{$i}", "{$test_function}_{$i}");
			}

			$this->assertNotEmpty($function_get_all($expected_id));
			$this->assertCount($nb, $function_get_all($expected_id));
			$this->assertTrue($test_function($expected_id));
			$this->assertEmpty($function_get_all($expected_id));
		}
	}

	/**
	 * @group api_campaigns_setting_cas
	 * @return void
	 */
	public function test_api_setting_cas() {
		$test_function = strtolower(sprintf('api_%s_setting_cas', self::$type));

		if (function_exists($test_function)) {
			$expected_id = $this->get_expected_next_id(self::$type) - 1;
			$setting_id = uniqid();
			$setting_old_value = uniqid($test_function);
			$setting_new_value = uniqid($test_function);

			$function_get = strtolower(sprintf('api_%s_setting_getsingle', self::$type));
			$function_set = strtolower(sprintf('api_%s_setting_set', self::$type));
			$function_set($expected_id, $setting_id, $setting_old_value);

			// wrong setting name
			$this->assertFalse($test_function($expected_id, "wrong_setting_name", $setting_old_value, $setting_new_value));
			// check setting has old value
			$this->assertSameEquals($setting_old_value, $function_get($expected_id, $setting_id));

			// wrong setting value
			$this->assertFalse($test_function($expected_id, $setting_id, 'whatever', $setting_new_value));
			// check setting has old value
			$this->assertSameEquals($setting_old_value, $function_get($expected_id, $setting_id));

			// wrong setting name and setting value
			$this->assertFalse($test_function($expected_id, "wrong_setting_name", 'whatever', $setting_new_value));
			// check setting has old value
			$this->assertSameEquals($setting_old_value, $function_get($expected_id, $setting_id));

			// success
			$this->assertTrue($test_function($expected_id, $setting_id, $setting_old_value, $setting_new_value));
			// check setting has old value
			$this->assertSameEquals($setting_new_value, $function_get($expected_id, $setting_id));

			// delete created value
			$this->assertTrue(api_keystore_delete(self::$type, $expected_id, $setting_id));
		}
	}
}
