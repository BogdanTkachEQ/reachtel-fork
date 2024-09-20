<?php
/**
 * MethodsTagsUnitTrait
 * Trait to unit test methods like Tags
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\helpers;

/**
 * Trait to unit test tags methods
 */
trait MethodsTagsUnitTrait
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_tags_get_data() {
		return [
			// Failures does not exists
			[false, 99],
			// Success
			['tag_value', 1],
		];
	}

	/**
	 * @group api_audio_tags_get
	 * @group api_campaigns_tags_get
	 * @group api_cron_tags_get
	 * @dataProvider api_tags_get_data
	 * @param mixed $expected_value
	 * @param mixed $type_id
	 * @return void
	 */
	public function test_api_tags_get($expected_value, $type_id) {
		$test_function = strtolower(sprintf('api_%s_tags_get', self::TYPE));

		if (function_exists($test_function)) {
			$this->mock_common_functions();
			$this->assertSameEquals($expected_value, $test_function($type_id));
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_tags_set_data() {
		return [
			// Failures does not exists
			[false, 99],
			// Success
			[true, 1],
		];
	}

	/**
	 * @group api_audio_tags_set
	 * @group api_campaigns_tags_set
	 * @group api_cron_tags_set
	 * @dataProvider api_tags_set_data
	 * @param mixed $expected_value
	 * @param mixed $type_id
	 * @return void
	 */
	public function test_api_tags_set($expected_value, $type_id) {
		$test_function = strtolower(sprintf('api_%s_tags_set', self::TYPE));

		if (function_exists($test_function)) {
			$this->mock_common_functions();
			$this->assertSameEquals($expected_value, $test_function($type_id, ['tag' => 'tag_value']));
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_tags_delete_data() {
		return [
			// Failures does not exists
			[false, 99],
			// Success
			[true, 1],
		];
	}

	/**
	 * @group api_audio_tags_delete
	 * @group api_campaigns_tags_delete
	 * @group api_cron_tags_delete
	 * @dataProvider api_tags_delete_data
	 * @param mixed $expected_value
	 * @param mixed $type_id
	 * @return void
	 */
	public function test_api_tags_delete($expected_value, $type_id) {
		$test_function = strtolower(sprintf('api_%s_tags_delete', self::TYPE));

		if (function_exists($test_function)) {
			$this->mock_common_functions();
			$this->assertSameEquals($expected_value, $test_function($type_id, ['tag' => 'tag_value']));
		}
	}

	/**
	 * @return void
	 */
	private function mock_common_functions() {
		$this->mock_function_value('api_tags_get', 'tag_value');
		$this->mock_function_value('api_tags_set', true);
		$this->mock_function_value('api_tags_delete', true);
		$check_id_exists_function = strtolower(sprintf('api_%s_checkidexists', self::TYPE));
		$this->mock_function_param_value(
			$check_id_exists_function,
			[
				['params' => 99, 'return' => false],
			],
			true
		);
	}
}
