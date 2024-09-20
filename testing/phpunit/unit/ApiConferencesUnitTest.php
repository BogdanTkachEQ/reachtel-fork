<?php
/**
 * ApiConferencesTest
 * Unit test for api_campaigns.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Api Conferences Unit Test class
 */
class ApiConferencesUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_conferences_add_data() {
		return [
			// Failures no userid
			[false, []],
			[false, ['whatever' => 'whatever']],

			// Failures no servers to create a conference on
			[false, ['userid' => 1], []],

			// Failures serverpreference failure
			[false, ['userid' => 2], []],

			// Failures expiry failure
			[false, ['userid' => 3, 'expiry' => 'whatever']],

			// Failures accesscodelength failure
			[false, ['userid' => 4, 'accesscodelength' => 'whatever']],
			[false, ['userid' => 5, 'accesscodelength' => -5]],
			[false, ['userid' => 7, 'accesscodelength' => 2]],
			[false, ['userid' => 8, 'accesscodelength' => 11]],

			// Failures empty server id
			[false, ['userid' => 9], [null]],

			// Success
			[6, ['userid' => 9]],
			[6, ['userid' => 10, 'serverpreference' => 1]],
			[3, ['userid' => 11, 'accesscodelength' => 3]],
			[10, ['userid' => 12, 'accesscodelength' => 10]],
		];
	}

	/**
	 * @group api_conferences_add
	 * @dataProvider api_conferences_add_data
	 * @param mixed $expected_accesscode_length
	 * @param array $options
	 * @param array $servers
	 * @return void
	 */
	public function test_api_conferences_add($expected_accesscode_length, array $options = ['userid' => 1], array $servers = [1 => 'server1', 2 => 'server2', 3 => 'server3', 4 => 'server4']) {
		$this->mock_function_value(
			'api_voice_servers_listall_active',
			$servers
		);
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value('api_db_query_read', $this->mock_ado_records([]));
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_value('api_db_query_write', true);
		$this->mock_function_value('api_db_lastid', 99);

		$conference = api_conferences_add($options);
		if ($expected_accesscode_length !== false) {
			$this->assertInternalType('array', $conference);
			$this->assertArrayHasKey('conferenceid', $conference);
			$this->assertArrayHasKey('accesscode', $conference);
			$this->assertSameEquals(99, $conference['conferenceid']);
			$this->assertSameEquals($expected_accesscode_length, strlen($conference['accesscode']));
		} else {
			$this->assertFalse($conference);
		}
	}
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_conferences_exists_data() {
		return [
			// Failures conference id
			[false, null],
			[false, false],
			[false, ''],

			// Failures conference options error
			[false, 1],
			[false, 2, ['whatever' => 'whatever']],
			[false, 3, ['awaitinghost' => true]],
			[false, 4, ['accesscode' => 123456]],
			[false, 5, ['userid' => 99]],

			// Success conference does not exists
			[false, 6, ['connectedonly' => true], false],
			[false, 7, ['connectedonly' => true], []],
			[false, 8, ['awaitinghost' => true, 'accesscode' => 123456], false],
			[false, 9, ['awaitinghost' => true, 'accesscode' => 123456], []],
			[false, 10, ['awaitinghost' => true, 'accesscode' => 123456, 'userid' => 99], false],
			[false, 11, ['awaitinghost' => true, 'accesscode' => 123456, 'userid' => 99], []],

			// Success conference exists
			[true, 12, ['connectedonly' => true]],
			[true, 13, ['awaitinghost' => true, 'accesscode' => 123456]],
			[true, 14, ['awaitinghost' => true, 'accesscode' => 123456, 'userid' => 99]],
		];
	}

	/**
	 * @group api_conferences_exists
	 * @dataProvider api_conferences_exists_data
	 * @param mixed $expected_value
	 * @param mixed $conferenceid
	 * @param array $options
	 * @param mixed $ado_records
	 * @return void
	 */
	public function test_api_conferences_exists($expected_value, $conferenceid, array $options = [], $ado_records = [1]) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			(is_array($ado_records) ? $this->mock_ado_records($ado_records) : $ado_records)
		);

		$this->assertSameEquals($expected_value, api_conferences_exists($conferenceid, $options));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_conferences_get_data() {
		return [
			// Failures conference id
			[false, null],
			[false, false],
			[false, ''],

			// Failures query
			[false, 1],

			// Success query
			[
				['conference'],
				2,
				[['conference']]
			],
		];
	}

	/**
	 * @group api_conferences_get
	 * @dataProvider api_conferences_get_data
	 * @param mixed $expected_value
	 * @param mixed $conferenceid
	 * @param mixed $ado_records
	 * @return void
	 */
	public function test_api_conferences_get($expected_value, $conferenceid, $ado_records = false) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			(is_array($ado_records) ? $this->mock_ado_records($ado_records) : $ado_records)
		);

		$this->assertSameEquals($expected_value, api_conferences_get($conferenceid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_conferences_participants_get_data() {
		return [
			// Failures conference id
			[false, null],
			[false, false],
			[false, ''],

			// Failures query
			[[], 1],

			// Success query
			[
				[
					99 => [
						'timestamp' => 99,
						'status' => 99,
						'channel' => 99,
						'callerid' => 99
					],
					1 => [
						'timestamp' => 1,
						'status' => 1,
						'channel' => 1,
						'callerid' => 1
					]
				],
				2,
				['connectedonly' => true, 'participantid' => 45],
				[
					[
						'participantid' => 99,
						'timestamp' => 99,
						'status' => 99,
						'channel' => 99,
						'callerid' => 99
					],
					[
						'participantid' => 1,
						'timestamp' => 1,
						'status' => 1,
						'channel' => 1,
						'callerid' => 1
					]
				]
			],
		];
	}

	/**
	 * @group api_conferences_participants_get
	 * @dataProvider api_conferences_participants_get_data
	 * @param mixed $expected_value
	 * @param mixed $conferenceid
	 * @param array $options
	 * @param mixed $ado_records
	 * @return void
	 */
	public function test_api_conferences_participants_get($expected_value, $conferenceid, array $options = [], $ado_records = false) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			(is_array($ado_records) ? $this->mock_ado_records($ado_records) : $ado_records)
		);

		$this->assertSameEquals($expected_value, api_conferences_participants_get($conferenceid, $options));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_conferences_participants_kick_data() {
		return [
			// Failures conference
			[false, null],
			[false, false],
			[false, ''],
			[false, []],
			[false, 4],

			// Failures no participants
			[false, 3],
			[false, 3, 3],
			[false, 3, [3]],

			// Failures participants not CONNECTED
			[false, 2],

			// Failures api_queue_add didnt work
			[false, 1, [], false],

			// Success
			[true, 1],
		];
	}

	/**
	 * @group api_conferences_participants_kick
	 * @dataProvider api_conferences_participants_kick_data
	 * @param mixed   $expected_value
	 * @param mixed   $conferenceid
	 * @param mixed   $participants
	 * @param boolean $queue
	 * @return void
	 */
	public function test_api_conferences_participants_kick($expected_value, $conferenceid, $participants = [], $queue = true) {
		$this->mock_function_param_value(
			'api_conferences_get',
			[
				['params' => 1, 'return' => ['id' => 1, 'serverid' => 1]],
				['params' => 2, 'return' => ['id' => 2, 'serverid' => 2]],
				['params' => 3, 'return' => ['id' => 3, 'serverid' => 3]],
			],
			false
		);

		$this->mock_function_param_value(
			'api_conferences_participants_get',
			[
				['params' => 1, 'return' => [10 => ['status' => 'CONNECTED', 'channel' => 1]]],
				['params' => 2, 'return' => [20 => ['status' => 'DISCONNECTED', 'channel' => 1]]],
				['params' => 3, 'return' => []],
			],
			[]
		);

		$this->mock_function_value('api_queue_add', $queue);

		$this->assertSameEquals($expected_value, api_conferences_participants_kick($conferenceid, $participants));
	}
}
