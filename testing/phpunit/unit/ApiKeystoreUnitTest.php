<?php
/**
 * ApiKeystoreTest
 * Unit test for api_data.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Api Keystore Unit Test class
 */
class ApiKeystoreUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_keystore_get_data() {
		return [
			// Failure database
			[false, false],

			// Failure no records
			[false, []],

			// Failure no value field
			[null, [1]],

			// Success no value field
			[false, ['value' => false]],
			[true, ['value' => true]],
			['', ['value' => '']],
			['data', ['value' => 'data']],
		];
	}

	/**
	 * @group api_keystore_get
	 * @dataProvider api_keystore_get_data
	 * @param mixed $expected_value
	 * @param mixed $records
	 * @return void
	 */
	public function test_api_keystore_get($expected_value, $records) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			is_array($records) ? $this->mock_ado_records($records) : $records
		);
		$this->assertSameEquals($expected_value, api_keystore_get('type', 1, 'item'));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_keystore_set_data() {
		return [
			// Failure database
			[false, false],

			// Failure invalid id
			[false, true, null],
			[false, true, false],
			[false, true, ''],

			// Failure no item
			[false, true, 1, null],
			[false, true, 2, false],
			[false, true, 3, ''],

			// Failure type
			[false, ['value' => 1], 1, 'type_failed', null],
			[false, ['value' => 1], 2, 'type_failed', false],
			[false, ['value' => 1], 3, 'type_failed', true],
			[false, ['value' => 1], 4, 'type_failed', 0],

			// Success no value field
			[true, ['value' => 2]],
		];
	}

	/**
	 * @group api_keystore_set
	 * @dataProvider api_keystore_set_data
	 * @param mixed $expected_value
	 * @param mixed $rs
	 * @param mixed $id
	 * @param mixed $item
	 * @param mixed $type
	 * @return void
	 */
	public function test_api_keystore_set($expected_value, $rs, $id = 1, $item = 'item', $type = 'type') {
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_value('api_db_query_write', $rs);
		$this->assertSameEquals($expected_value, api_keystore_set($type, $id, $item, 'value'));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_keystore_cas_data() {
		return [
			// Failure database
			[false, false],

			// Failure no affected rows
			[false, true, 0],

			// Success affected rows
			[true, true, 1],
		];
	}

	/**
	 * @group api_keystore_cas
	 * @dataProvider api_keystore_cas_data
	 * @param boolean $expected_value
	 * @param mixed   $rs
	 * @param integer $affected_rows
	 * @return void
	 */
	public function test_api_keystore_cas($expected_value, $rs = true, $affected_rows = 1) {
		$this->mock_function_value('api_db_affectedrows', $affected_rows);
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_value('api_db_query_write', $rs);
		$this->assertSameEquals($expected_value, api_keystore_cas('type', 1, 'item', 'check', 'value'));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_keystore_get_multi_byid_data() {
		return [
			// Failure database
			[[], false],

			// Empty results no ids
			[[], null, 'no ids', []],

			// Empty results no item
			[[], null, 'no item 1', [1], ''],
			[[], null, 'no item 2', [2], null],
			[[], null, 'no item 3', [3], false],

			// Success no records
			[[], []],

			// Success
			[
				[1 => 'val1', 2 => 'val2'],
				[['id' => 1, 'value' => 'val1'], ['id' => 2, 'value' => 'val2']],
			]
		];
	}

	/**
	 * @group api_keystore_get_multi_byid
	 * @dataProvider api_keystore_get_multi_byid_data
	 * @param mixed $expected_value
	 * @param mixed $records
	 * @param mixed $type
	 * @param array $ids
	 * @param mixed $item
	 * @return void
	 */
	public function test_api_keystore_get_multi_byid($expected_value, $records, $type = 'type', array $ids = [1, 2], $item = 'item') {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			is_array($records) ? $this->mock_ado_records($records) : $records
		);
		$this->assertSameEquals($expected_value, api_keystore_get_multi_byid($type, $ids, $item));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_keystore_get_multi_byitem_data() {
		return [
			// Failure database
			[[], false],

			// Empty results invalid id
			[[], null, 'invalid id 1', null],
			[[], null, 'invalid id 2', false],
			[[], null, 'invalid id 3', ''],

			// Empty results no items
			[[], null, 'invalid id 1', 1, []],

			// Success no records
			[[], []],

			// Success
			[
				[1 => 'val1', 2 => 'val2'],
				[['item' => 1, 'value' => 'val1'], ['item' => 2, 'value' => 'val2']],
			]
		];
	}

	/**
	 * @group api_keystore_get_multi_byitem
	 * @dataProvider api_keystore_get_multi_byitem_data
	 * @param mixed $expected_value
	 * @param mixed $records
	 * @param mixed $type
	 * @param mixed $id
	 * @param array $items
	 * @return void
	 */
	public function test_api_keystore_get_multi_byitem($expected_value, $records, $type = 'type', $id = 1, array $items = ['item1', 'item2']) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			is_array($records) ? $this->mock_ado_records($records) : $records
		);
		$this->assertSameEquals($expected_value, api_keystore_get_multi_byitem($type, $id, $items));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_keystore_delete_data() {
		return [
			// Failure invalid id
			[false, true, 1, null],
			[false, true, 1, false],
			[false, true, 1, ''],

			// Failure invalid item
			[false, true, 1, 1, null],
			[false, true, 1, 2, false],
			[false, true, 1, 3, ''],

			// Failure database
			[false, false],
			[false, null],

			// Failure no affected rows
			[false, true, 0],

			// Success no affected rows
			[true],
		];
	}

	/**
	 * @group api_keystore_delete
	 * @dataProvider api_keystore_delete_data
	 * @param mixed   $expected_value
	 * @param mixed   $rs
	 * @param integer $affected_rows
	 * @param mixed   $id
	 * @param mixed   $item
	 * @return void
	 */
	public function test_api_keystore_delete($expected_value, $rs = true, $affected_rows = 1, $id = 1, $item = 'item') {
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_value('api_db_query_write', $rs);
		$this->mock_function_value('api_db_affectedrows', $affected_rows);
		$this->assertSameEquals($expected_value, api_keystore_delete('type', $id, $item));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_keystore_increment_data() {
		return [
			// Failure invalid id
			[false, true, 1, null],
			[false, true, 1, false],
			[false, true, 1, ''],

			// Failure database
			[false, false],
			[false, null],

			// Failure no affected rows
			[false, true, 0],

			// Success no affected rows
			[2],
		];
	}

	/**
	 * @group api_keystore_increment
	 * @dataProvider api_keystore_increment_data
	 * @param mixed   $expected_value
	 * @param mixed   $rs
	 * @param integer $affected_rows
	 * @param mixed   $id
	 * @return void
	 */
	public function test_api_keystore_increment($expected_value, $rs = true, $affected_rows = 1, $id = 1) {
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_value('api_db_query_write', $rs);
		$this->mock_function_value('api_db_affectedrows', $affected_rows);
		$this->mock_function_value('api_db_lastid', 2);
		$this->assertSameEquals($expected_value, api_keystore_increment('type', $id, 'item'));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_keystore_purge_data() {
		return [
			// Failure invalid id
			[false, null],
			[false, false],
			[false, ''],

			// Success
			[true],
		];
	}

	/**
	 * @group api_keystore_purge
	 * @dataProvider api_keystore_purge_data
	 * @param mixed $expected_value
	 * @param mixed $id
	 * @return void
	 */
	public function test_api_keystore_purge($expected_value, $id = 1) {
		$this->mock_function_value('api_keystore_getnamespace', ['key1' => 'value1', 'key2' => 'value2']);
		$this->mock_function_value('api_keystore_delete', null);
		$this->assertSameEquals($expected_value, api_keystore_purge('type', $id, 'item'));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_keystore_getnamespace_data() {
		return [
			// Failure invalid type
			[false, true, null],
			[false, true, false],
			[false, true, true],
			[false, true, 0],

			// Failure invalid id
			[false, true, 'invalid_id1', null],
			[false, true, 'invalid_id2', false],
			[false, true, 'invalid_id3', ''],

			// Failure database
			[false, false],

			// Success
			[[], []],
			[
				[
					'item_1' => 'value_1',
					'item_2' => 'value_2'
				],
				[
					['item' => 'item_1', 'value' => 'value_1'],
					['item' => 'item_2', 'value' => 'value_2']
				]
			],
		];
	}

	/**
	 * @group api_keystore_getnamespace
	 * @dataProvider api_keystore_getnamespace_data
	 * @param mixed $expected_value
	 * @param mixed $records
	 * @param mixed $type
	 * @param mixed $id
	 * @return void
	 */
	public function test_api_keystore_getnamespace($expected_value, $records = true, $type = 'type', $id = 1) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			is_array($records) ? $this->mock_ado_records($records) : $records
		);
		$this->assertSameEquals($expected_value, api_keystore_getnamespace($type, $id));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_keystore_getnamespaces_byids_data() {
		$records = [
			['id' => 4, 'item' => 'name', 'value' => 'four'],
			['id' => 4, 'item' => 'setting1', 'value' => 'four_set_1'],
			['id' => 4, 'item' => 'setting2', 'value' => 'four_set_2'],
			['id' => 5, 'item' => 'name', 'value' => 'five'],
			['id' => 5, 'item' => 'setting1', 'value' => 'five_set_1'],
		];

		return [
			// Failure invalid type
			[false, true, true],
			[false, true, false],
			[false, true, null],
			[false, true, 0],

			// Empty results
			[[], [], 'TYPE'],

			// Some results
			[
				[
					4 => [
						'name' => 'four',
						'setting1' => 'four_set_1',
						'setting2' => 'four_set_2',
					],
					5 => [
						'name' => 'five',
						'setting1' => 'five_set_1',
					],
				],
				$records,
				'TYPE'
			],

			// filter tests
			// no filter match
			[
				[],
				$records,
				'TYPE',
				['whatever' => 'whatever']
			],
			// filter key found and value match
			[
				[
					4 => [
						'name' => 'four',
						'setting1' => 'four_set_1',
						'setting2' => 'four_set_2',
					],
				],
				$records,
				'TYPE',
				['setting2' => 'four_set_2']
			],
			// filter key found and but value not match
			[
				[],
				$records,
				'TYPE',
				['setting2' => 'whatever']
			],
			[
				[
					4 => [
						'name' => 'four',
						'setting1' => 'four_set_1',
						'setting2' => 'four_set_2',
					],
				],
				$records,
				'TYPE',
				['name' => 'four']
			],
		];
	}

	/**
	* @group api_keystore_getnamespaces_byids
	* @dataProvider api_keystore_getnamespaces_byids_data
	* @param mixed  $expected_value
	* @param mixed  $records
	* @param string $type
	* @param array  $filters
	* @return void
	*/
	public function test_api_keystore_getnamespaces_byids($expected_value, $records, $type, array $filters = []) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			is_array($records) ? $this->mock_ado_records($records) : $records
		);
		$this->assertSameEquals($expected_value, api_keystore_getnamespaces_byids($type, [1 , 2], $filters));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_keystore_getentirenamespace_data() {
		return [
			// Failure invalid type
			[false, false, null],
			[false, false, false],
			[false, false, true],
			[false, false, 0],

			// Failure database
			[false, false],

			// Success
			[[], []],
			[
				[
					1 => ['item_1' => 'value_1'],
					5 => ['item_5' => 'value_5']
				],
				[
					['id' => 1, 'item' => 'item_1', 'value' => 'value_1'],
					['id' => 5, 'item' => 'item_5', 'value' => 'value_5'],
				]
			],
		];
	}

	/**
	 * @group api_keystore_getentirenamespace
	 * @dataProvider api_keystore_getentirenamespace_data
	 * @param mixed $expected_value
	 * @param mixed $records
	 * @param mixed $type
	 * @return void
	 */
	public function test_api_keystore_getentirenamespace($expected_value, $records, $type = 'type') {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			is_array($records) ? $this->mock_ado_records($records) : $records
		);
		$this->assertSameEquals($expected_value, api_keystore_getentirenamespace($type));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_keystore_getids_data() {
		return [
			// Failure database
			[[], false],

			// Success
			[[], []],
			[[10, 1, 2], [['id' => 10], ['id' => 1], ['id' => 2]]],

			// Success with ret
			[[], [], true],
			[
				[['id' => 10], ['id' => 1], ['id' => 2]],
				[['id' => 10], ['id' => 1], ['id' => 2]],
				true
			],
		];
	}

	/**
	 * @group api_keystore_getids
	 * @dataProvider api_keystore_getids_data
	 * @param mixed $expected_value
	 * @param mixed $records
	 * @param mixed $ret
	 * @return void
	 */
	public function test_api_keystore_getids($expected_value, $records, $ret = false) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			is_array($records) ? $this->mock_ado_records($records) : $records
		);
		$this->assertSameEquals($expected_value, api_keystore_getids('type', 'name', $ret));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_keystore_getidswithvalue_data() {
		return [
			// Failure invalid type
			[[], true, null],
			[[], true, true],
			[[], true, false],
			[[], true, 0],

			// Failure invalid item
			[[], true, 'type1', null],
			[[], true, 'type2', false],
			[[], true, 'type3', ''],

			// Failure database
			[false, false],
			[false, null],

			// Success
			[[10, 1, 2], [['id' => 10], ['id' => 1], ['id' => 2]]],
		];
	}

	/**
	 * @group api_keystore_getidswithvalue
	 * @dataProvider api_keystore_getidswithvalue_data
	 * @param mixed $expected_value
	 * @param mixed $records
	 * @param mixed $type
	 * @param mixed $item
	 * @return void
	 */
	public function test_api_keystore_getidswithvalue($expected_value, $records, $type = 'type', $item = 'item') {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			is_array($records) ? $this->mock_ado_records($records) : $records
		);
		$this->assertSameEquals($expected_value, api_keystore_getidswithvalue($type, $item, 'value'));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_keystore_checkkeyexists_data() {
		return [
			// Failure invalid item
			[[], false, null],
			[[], false, false],
			[[], false, ''],

			// Failure invalid type
			[[], false, 'invalid type 1', null],
			[[], false, 'invalid type 2', false],
			[[], false, 'invalid type 3', true],
			[[], false, 'invalid type 4', 0],

			// Failure database
			[false, []],
			[false, null],

			// Failure no records
			[false, []],

			// Success one record
			[50, ['id' => 50]],

			// Success many records
			[50, ['id' => 50, 'id2' => 51]],
		];
	}

	/**
	 * @group api_keystore_checkkeyexists
	 * @dataProvider api_keystore_checkkeyexists_data
	 * @param mixed $expected_value
	 * @param mixed $records
	 * @param mixed $item
	 * @param mixed $type
	 * @return void
	 */
	public function test_api_keystore_checkkeyexists($expected_value, $records, $item = 'item', $type = 'type') {
		$this->mock_function_value('api_misc_audit', null);
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			is_array($records) ? $this->mock_ado_records($records) : $records
		);
		$this->assertSameEquals($expected_value, api_keystore_checkkeyexists($type, $item, 'value'));
		// with options
		$this->assertSameEquals($expected_value, api_keystore_checkkeyexists($type, $item, 'value', ['casesensitive' => true]));
	}
}
