<?php
/**
 * MethodsTagsModuleTrait
 * Trait to module test methods like Tags
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait to module test methods like Tags
 */
trait MethodsTagsModuleTrait
{
	/**
	 * @group api_audio_tags_get
	 * @group api_campaigns_tags_get
	 * @group api_cron_tags_get
	 * @return void
	 */
	public function test_api_tags_get() {
		$test_function = strtolower(sprintf('api_%s_tags_get', self::$type));

		if (function_exists($test_function)) {
			$expected_id = $this->get_expected_next_id(self::$type);

			$tags = $test_function($expected_id);
			$this->assertFalse($tags);

			// Create a new type item in db
			$item_id = $this->create_new_by_type(self::$type);
			$tags = $test_function($item_id);
			$this->assertInternalType('array', $tags);
			$this->assertEmpty($tags);

			$function = strtolower(sprintf('api_%s_tags_set', self::$type));
			$function($item_id, ['tag1' => 'val1', 'tag2' => 'val2']);

			$tags = $test_function($item_id);
			$this->assertInternalType('array', $tags);
			$this->assertCount(2, $tags);
			$this->assertArrayHasKey('tag1', $tags);
			$this->assertArrayHasKey('tag2', $tags);
			$this->assertSameEquals('val1', $tags['tag1']);
			$this->assertSameEquals('val2', $tags['tag2']);

			$tags = $test_function($item_id, ['tag2']);
			$this->assertInternalType('array', $tags);
			$this->assertCount(1, $tags);
			$this->assertArrayNotHasKey('tag1', $tags);
			$this->assertArrayHasKey('tag2', $tags);
			$this->assertSameEquals('val2', $tags['tag2']);

			$this->assertSameEquals('val1', $test_function($item_id, 'tag1')); // 2nd param as string
			$this->assertFalse($test_function($item_id, 'whatever'));

			// test specific values
			foreach ([true, false, null, '', 1, 1.1, [1, 2]] as $value) {
				$function($item_id, ['specific_value' => $value]);
				$this->assertSameEquals($value, $test_function($item_id, 'specific_value'));
			}

			// clean up delete created file
			$delete_function = strtolower(sprintf('api_%s_delete', self::$type));
			if (function_exists($test_function)) {
				$this->assertTrue($delete_function($item_id));
			}
		}
	}

	/**
	 * @group api_audio_tags_set
	 * @group api_campaigns_tags_set
	 * @group api_cron_tags_set
	 * @return void
	 */
	public function test_api_tags_set() {
		$test_function = strtolower(sprintf('api_%s_tags_set', self::$type));

		if (function_exists($test_function)) {
			$expected_id = $this->get_expected_next_id(self::$type);
			$array_value = ['tag1' => 'val1', 'tag2' => 'val2'];

			$this->assertFalse($test_function($expected_id, $array_value));

			// Create a new type item in db
			$item_id = $this->create_new_by_type(self::$type);
			$this->assertTrue($test_function($item_id, $array_value));

			$function = strtolower(sprintf('api_%s_tags_get', self::$type));
			$this->assertSameEquals($array_value, $function($item_id));

			// set already existing tags
			$this->assertTrue($test_function($item_id, ['tag1' => 'new_val1']));
			$this->assertSameEquals(['tag1' => 'new_val1', 'tag2' => 'val2'], $function($item_id));

			$function_get = sprintf('api_%s_tags_get', self::$type);
			foreach ([null, false, ''] as $value) { // Empty tags
				$this->assertTrue($test_function($item_id, ['empty_tag' => $value]));

				$tags = $function_get($item_id, null);
				$this->assertArrayHasKey('empty_tag', $tags);
				$this->assertTrue($value === $tags['empty_tag']);

				$tags = $function_get($item_id, ['empty_tag']);
				$this->assertArrayHasKey('empty_tag', $tags);
				$this->assertTrue($value === $tags['empty_tag']);

				$this->assertTrue($value === $function_get($item_id, 'empty_tag'));
			}

			// clean up delete created file
			$delete_function = strtolower(sprintf('api_%s_delete', self::$type));
			if (function_exists($test_function)) {
				$this->assertTrue($delete_function($item_id));
			}
		}
	}

	/**
	 * @group api_audio_tags_delete
	 * @group api_campaigns_tags_delete
	 * @group api_cron_tags_delete
	 * @return void
	 */
	public function test_api_tags_delete() {
		$test_function = strtolower(sprintf('api_%s_tags_delete', self::$type));

		if (function_exists($test_function)) {
			$expected_id = $this->get_expected_next_id(self::$type);

			$array_value = ['tag1' => 'val1', 'tag2' => 'val2'];

			$this->assertFalse($test_function($expected_id));
			$this->assertFalse($test_function($expected_id, $array_value));

			// Create a new type item in db
			$item_id = $this->create_new_by_type(self::$type);

			$function_set = strtolower(sprintf('api_%s_tags_set', self::$type));
			$this->assertTrue($function_set($item_id, $array_value));
			$function_get = strtolower(sprintf('api_%s_tags_get', self::$type));

			$this->assertTrue($test_function($item_id)); // no tags set, no change but return true
			$tags = $function_get($item_id, array_keys($array_value));
			$this->assertInternalType('array', $tags);
			$this->assertCount(2, $tags);
			$this->assertArrayHasKey('tag1', $tags);
			$this->assertArrayHasKey('tag2', $tags);
			$this->assertSameEquals($array_value, $tags);

			$this->assertTrue($test_function($item_id, ['tag2'])); // Delete tag2
			$tags = $function_get($item_id, array_keys($array_value));
			$this->assertInternalType('array', $tags);
			$this->assertCount(1, $tags);
			$this->assertArrayHasKey('tag1', $tags);
			$this->assertArrayNotHasKey('tag2', $tags);
			$this->assertSameEquals(['tag1' => 'val1'], $tags);

			$this->assertTrue($test_function($item_id, ['tag1'])); // Delete tag1
			$tags = $function_get($item_id, array_keys($array_value));
			$this->assertInternalType('array', $tags);
			$this->assertEmpty($tags);

			foreach ([null, false, ''] as $value) {  // Empty tags
				$function_set = sprintf('api_%s_tags_set', self::$type);
				$this->assertTrue($function_set($item_id, ['tags' => $value]));
				$this->assertTrue($test_function($item_id, ['tags']));
			}

			// clean up delete created file
			$delete_function = strtolower(sprintf('api_%s_delete', self::$type));
			if (function_exists($test_function)) {
				$this->assertTrue($delete_function($item_id));
			}
		}
	}
}
