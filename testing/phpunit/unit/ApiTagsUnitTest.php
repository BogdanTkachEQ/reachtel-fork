<?php
/**
 * ApiTagsTest
 * Unit test for api_tags.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Api Tags Unit Test class
 */
class ApiTagsUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_tags_get_data() {
		$noExisting = [[], []];
		$existing = [['t1' => 'v1', 't2' => 2, 0 => ['Z']], ['t2']];

		return [
			// No existing tags
			[[], $noExisting, null],
			[[], $noExisting, ['non_scalar_test']],
			[false, $noExisting, 'scalar_test'],
			[false, $noExisting, 1],
			[false, $noExisting, 1.1],

			// parameter tag is null
			[$existing[0], $existing, null],

			// tag does not exists in the existing tags
			[false, $existing, false],
			[false, $existing, true],
			[false, $existing, ''],
			[false, $existing, 0.0],
			[false, $existing, 1.65],

			// Array #tags parameter test
			[$existing[0], $existing, ['wrong_tag']],
			[$existing[0], $existing, ['t1']],
			[$existing[0], $existing, ['t2']],

			// String #tags parameter test
			[false, $existing, 'string_not_in_existing_tags'],

			// String #tags parameter test
			['v1', $existing, 't1'],
			[2, $existing, 't2'],
			[['Z'], $existing, 0],
			[['Z'], $existing, '0'],
		];
	}

	/**
	 * @dataProvider api_tags_get_data
	 * @param boolean $expected_value
	 * @param array   $existing
	 * @param mixed   $tags
	 * @return void
	 */
	public function test_api_tags_get($expected_value, array $existing, $tags = null) {
		$this->mock_function_value('api_tags_get_existing_tag_details', $existing);
		$this->mock_function_value('api_tags_decrypt_values', $existing[0]);
		$this->assertSameEquals($expected_value, api_tags_get('WHATEVER', 1, $tags));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_tags_set_data() {
		return [
			// id does not exists
			[false, ['TAG_ID_NOT_EXISTS']],
			// no tags
			['stored_tags', [[], []]],
			// some tags
			['stored_tags', [[], []], ['test' => 1]],
			// new tags
			['stored_tags', [[], []], ['test' => 2]],
			// override tags
			['stored_tags', [['t1' => 'v1'], []], ['t1' => 3]],

			// encryption, wrong tag names
			['stored_tags', [[], []], ['t1' => 4], ['wrong']],
			// encryption, valid tag name
			['stored_tags', [[], []], ['t1' => 5], ['t1']],

			// encryption failed, value is array ( we do not support to encrypt arrays)
			[false, [['t1' => [6]], []], ['t1' => [6]], ['t1']],

			// encryption, existing tags
			['stored_tags', [['t1' => 7], []], ['t1' => 7], ['t1']],
			['stored_tags', [['t1' => 8], ['t1']], ['t1' => 8], ['t1']],
			// tags to be removed from existing encrypt list
			['stored_tags', [['t1' => 9], ['t1']], ['t1' => 9, 't2' => 9], ['t2']],

			// encryption failed
			[false, [[], []], ['t1' => 10], ['t1'], [], false],
		];
	}

	/**
	 * @dataProvider api_tags_set_data
	 * @param mixed   $expected_value
	 * @param array   $existing
	 * @param array   $tags
	 * @param array   $encryptTags
	 * @param boolean $encryptTagsReturn
	 * @return void
	 */
	public function test_api_tags_set($expected_value, array $existing, array $tags = [], array $encryptTags = [], $encryptTagsReturn = true) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			(in_array('TAG_ID_NOT_EXISTS', $existing) ? false : $this->mock_ado_records(['found']))
		);
		$this->mock_function_value('api_tags_get_existing_tag_details', $existing);
		$this->mock_function_value('api_tags_store_tags', 'stored_tags');
		$this->mock_function_value('api_tags_store_encrypt_tags', $encryptTagsReturn);
		$this->assertSameEquals($expected_value, api_tags_set('WHATEVER', 1, $tags, $encryptTags));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_tags_delete_data() {
		return [
			// no existing tags
			[true, [[], []]],

			// wrong tags
			[true, [['t1' => 1], []], ['wrong']],
			// remove t1
			[true, [['t1' => 1, 't2' => 2], []], ['t1']],
			// remove t1 failed
			[false, [['t1' => 3, 't2' => 4], []], ['t1'], false],
			// remove t1 from encrypted tags
			['stored_encrypt_tags', [['t1' => 5, 't2' => 6], ['t1']], ['t1']],
		];
	}

	/**
	 * @dataProvider api_tags_delete_data
	 * @param mixed   $expected_value
	 * @param array   $existing
	 * @param array   $tags
	 * @param boolean $storeTagsReturn
	 * @return void
	 */
	public function test_api_tags_delete($expected_value, array $existing, array $tags = [], $storeTagsReturn = true) {
		$this->mock_function_value('api_tags_get_existing_tag_details', $existing);
		$this->mock_function_value('api_tags_store_tags', $storeTagsReturn);
		$this->mock_function_value('api_tags_store_encrypt_tags', 'stored_encrypt_tags');
		$this->assertSameEquals($expected_value, api_tags_delete('WHATEVER', 1, $tags));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_tags_get_existing_tag_details_data() {
		return [
			[[['t1' => 1, 't2' => 2], ['t1']]],
			[['DECRYPTED', ['t1']], true],
		];
	}

	/**
	 * @dataProvider api_tags_get_existing_tag_details_data
	 * @param mixed   $expected_value
	 * @param boolean $get_decrypted_values
	 * @return void
	 */
	public function test_api_tags_get_existing_tag_details($expected_value, $get_decrypted_values = false) {
		$this->mock_function_value(
			'api_keystore_get_multi_byitem',
			[
				'tags' => serialize(['t1' => 1, 't2' => 2]),
				'encrypt_tags' => serialize(['t1'])
			]
		);
		$this->mock_function_value('api_tags_decrypt_values', 'DECRYPTED');

		$this->assertSameEquals($expected_value, api_tags_get_existing_tag_details('WHATEVER', 1, $get_decrypted_values));
	}

	/**
	 * Test for api_tags_store_tags
	 * @return void
	 */
	public function test_api_tags_store_tags() {
		$this->mock_function_value('api_keystore_set', 'PROXY_METHOD_TEST_ONLY');

		$this->assertSameEquals('PROXY_METHOD_TEST_ONLY', api_tags_store_tags('WHATEVER', 1, []));
	}

	/**
	 * Test for api_tags_store_encrypt_tags
	 * @return void
	 */
	public function test_api_tags_store_encrypt_tags() {
		$this->mock_function_value('api_keystore_set', 'PROXY_METHOD_TEST_ONLY');

		$this->assertSameEquals('PROXY_METHOD_TEST_ONLY', api_tags_store_encrypt_tags('WHATEVER', 1, []));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_tags_decrypt_values_data() {
		return [
			// empty params
			[[]],
			// empty encrypt_tags
			[['t1' => 1], ['t1' => 1]],
			// wrong encrypt_tags
			[['t1' => 2], ['t1' => 2], ['wrong']],
			// wrong encrypt_tags
			[['t1' => 'DECRYPTED_VALUE'], ['t1' => 3], ['t1']],
		];
	}

	/**
	 * @dataProvider api_tags_decrypt_values_data
	 * @param mixed $expected_value
	 * @param array $tags
	 * @param array $encrypt_tags
	 * @return void
	 */
	public function test_api_tags_decrypt_values($expected_value, array $tags = [], array $encrypt_tags = []) {
		$this->mock_function_value('api_misc_decrypt_base64', 'DECRYPTED_VALUE');

		$this->assertSameEquals($expected_value, api_tags_decrypt_values($tags, $encrypt_tags));
	}
}
