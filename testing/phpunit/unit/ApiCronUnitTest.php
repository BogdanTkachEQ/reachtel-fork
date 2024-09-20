<?php
/**
 * ApiCronTest
 * Unit test for api_cron.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

use testing\unit\helpers\MethodsCheckExistsUnitTrait;
use testing\unit\helpers\MethodsSettingsUnitTrait;
use testing\unit\helpers\MethodsTagsUnitTrait;

/**
 * Api Cron Unit Test class
 */
class ApiCronUnitTest extends AbstractPhpunitUnitTest
{
	use MethodsCheckExistsUnitTrait;
	use MethodsSettingsUnitTrait;
	use MethodsTagsUnitTrait;

	/**
	 * Type value
	 */
	const TYPE = 'CRON';

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_cron_add_data() {
		return [
			// Failures cron name
			[false, null],
			[false, false],
			[false, ''],
			[false, '123'], // min 4 chars
			[false, str_repeat('a', 51)], // max 50 chars

			// Failures cron with that name already exists
			[false, 'cron_exists_already'],

			// Success
			[1, 'new_cron'],
		];
	}

	/**
	 * @group api_cron_add
	 * @dataProvider api_cron_add_data
	 * @param mixed $expected_value
	 * @param mixed $name
	 * @return void
	 */
	public function test_api_cron_add($expected_value, $name) {
		$this->mock_function_param_value(
			'api_cron_checknameexists',
			[
				['params' => 'cron_exists_already', 'return' => true]
			],
			false
		);
		$this->mock_function_value('api_keystore_increment', 1);
		$this->mock_function_value('api_cron_setting_set', null);

		$this->assertSameEquals($expected_value, api_cron_add($name));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_cron_delete_data() {
		return [
			// Failures does not id exists
			[false, 1],

			// Success
			[true, 2],
		];
	}

	/**
	 * @group api_cron_delete
	 * @dataProvider api_cron_delete_data
	 * @param mixed $expected_value
	 * @param mixed $id
	 * @return void
	 */
	public function test_api_cron_delete($expected_value, $id) {
		$this->mock_function_param_value(
			'api_cron_checkidexists',
			[
				['params' => 1, 'return' => false]
			],
			true
		);
		$this->mock_function_value('api_keystore_purge', null);

		$this->assertSameEquals($expected_value, api_cron_delete($id));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_cron_listall_data() {
		$crons = [
			1 => ['status' => 'ACTIVE', 'name' => '#1_active'],
			2 => ['status' => 'DISABLED', 'name' => '#2_disabled'],
			3 => ['status' => 'ACTIVE', 'name' => '#3_active'],
		];

		return [
			// Success no crons
			[[], false, false, false],
			[[], false, false, null],
			[[], false, false, []],

			// Success crons
			[
				$crons,
				false,
				false,
				$crons
			],
			[
				[
					1 => '#1_active',
					2 => '#2_disabled',
					3 => '#3_active',
				],
				true,
				false,
				$crons
			],
			[
				[
					1 => ['status' => 'ACTIVE', 'name' => '#1_active'],
					3 => ['status' => 'ACTIVE', 'name' => '#3_active'],
				],
				false,
				true,
				$crons
			],
			[
				[
					1 => '#1_active',
					3 => '#3_active',
				],
				true,
				true,
				$crons
			],
		];
	}

	/**
	 * @group api_cron_listall
	 * @dataProvider api_cron_listall_data
	 * @param mixed   $expected_value
	 * @param boolean $short
	 * @param boolean $activeonly
	 * @param mixed   $crons
	 * @return void
	 */
	public function test_api_cron_listall($expected_value, $short = false, $activeonly = false, $crons = false) {
		$this->mock_function_value('api_keystore_getentirenamespace', $crons);

		$this->assertSameEquals($expected_value, api_cron_listall($short, $activeonly));
	}

	/**
	 * @group api_cron_run
	 * @return void
	 */
	public function test_api_cron_run() {
		$this->mock_function_value(
			'api_cron_listall',
			[
				1 => 'cron_#1',
				2 => 'cron_not_due_#2',
				3 => 'cron_#3'
			]
		);
		$this->mock_function_param_value(
			'api_cron_isdue',
			[
				['params' => 2, 'return' => false]
			],
			true
		);
		$this->mock_function_value('api_misc_audit', null);
		$this->mock_function_value('api_queue_add', null);

		$this->assertSameEquals(null, api_cron_run());
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_cron_isdue_data() {
		// match 30 May 2017 at 21:41
		$cron_match = $this->get_cron(41/*=min*/, 21/*=hour*/, 30/*=dayofmonths*/, 5/*=month*/, 2/*=dayofweeks*/);

		return [
			// Failure cron id does not exists
			[false, 1],

			// Failure cron not active
			[false, 2],
			[false, 3],
			[false, 4],

			// Failure cron structure
			[false, 5],
			[false, 6],
			[false, 7],
			[false, 8],

			// Failure timezone
			[false, 9, '2017-05-30 21:41:00', $cron_match],

			// Success no crons
			[false, 10],

			// Success cron but minute does not match
			[false, 10, '2017-05-30 21:41:00 Australia/Brisbane', ['minute' => [42]]],
			// Success cron but hour does not match
			[false, 10, '2017-05-30 21:41:00 Australia/Brisbane', ['hour' => [22]]],
			// Success cron but dayofmonth does not match
			[false, 10, '2017-05-30 21:41:00 Australia/Brisbane', ['dayofmonth' => [31]]],
			// Success cron but dayofmonth does not match
			[false, 10, '2017-05-30 21:41:00 Australia/Brisbane', ['month' => [6]]],
			// Success cron but dayofmonth does not match
			[false, 10, '2017-05-30 21:41:00 Australia/Brisbane', ['dayofweek' => [3]]],
			// Success cron but does not match
			[
				false,
				10,
			 '2017-05-30 21:41:00 Europe/Paris',
				$cron_match
			],

			// Success cron match for each seconds
			[true, 10, '2017-05-30 21:41:00 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:01 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:02 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:03 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:04 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:05 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:06 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:07 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:08 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:09 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:10 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:11 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:12 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:13 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:14 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:15 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:16 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:17 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:18 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:19 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:20 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:21 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:22 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:23 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:24 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:25 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:26 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:27 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:28 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:29 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:30 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:31 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:32 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:33 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:34 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:35 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:36 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:37 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:38 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:39 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:40 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:41 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:42 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:43 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:44 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:45 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:46 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:47 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:48 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:49 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:50 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:51 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:52 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:53 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:54 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:55 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:56 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:57 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:58 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:59 Australia/Brisbane', $cron_match],
			[true, 10, '2017-05-30 21:41:00 Australia/Brisbane', [[]]], // No tokens defaults to match all
		];
	}

	/**
	 * @group api_cron_isdue
	 * @dataProvider api_cron_isdue_data
	 * @param mixed $expected_value
	 * @param mixed $id
	 * @param mixed $time
	 * @param array $crons
	 * @return void
	 */
	public function test_api_cron_isdue($expected_value, $id, $time = 'now', array $crons = []) {
		$this->mock_function_param_value(
			'api_cron_checkidexists',
			[
				['params' => 1, 'return' => false]
			],
			true
		);

		$this->mock_function_param_value(
			'api_cron_setting_getsingle',
			[
				['params' => [2, 'status'], 'return' => false],
				['params' => [3, 'status'], 'return' => null],
				['params' => [4, 'status'], 'return' => 'DISABLED'],
				['params' => [5, 'status'], 'return' => 'ACTIVE'],
				['params' => [6, 'status'], 'return' => 'ACTIVE'],
				['params' => [7, 'status'], 'return' => 'ACTIVE'],
				['params' => [8, 'status'], 'return' => 'ACTIVE'],
				['params' => [9, 'status'], 'return' => 'ACTIVE'],
				['params' => [9, 'timezone'], 'return' => 'whatever'],
				['params' => [10, 'status'], 'return' => 'ACTIVE'],
				['params' => [10, 'timezone'], 'return' => 'Australia/Brisbane'],
			],
			false
		);

		$this->mock_function_param_value(
			'api_cron_parse',
			[
				['params' => 5, 'return' => false],
				['params' => 6, 'return' => null],
				['params' => 7, 'return' => ''],
				['params' => 8, 'return' => []],
			],
			$crons
		);
		$this->mock_function_value('api_misc_audit', null);

		$this->assertSameEquals($expected_value, api_cron_isdue($id, $time));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_cron_parse_data() {
		return [
			// Failures does not id exists
			[false, 1],

			// Failures not a well formed cron"
			[false, 2],

			// Success but no segments
			[$this->get_cron(null, null, null, null, null), 3],
			[$this->get_cron(null, null, null, null, null), 4],
			[$this->get_cron(null, range(1, 5), null, null, null), 5],
			[$this->get_cron(range(0, 59), range(1, 10), 8, range(1, 12), 6), 6],
		];
	}

	/**
	 * @group api_cron_parse
	 * @dataProvider api_cron_parse_data
	 * @param mixed $expected_value
	 * @param mixed $id
	 * @return void
	 */
	public function test_api_cron_parse($expected_value, $id) {
		$this->mock_function_param_value(
			'api_cron_checkidexists',
			[
				['params' => 1, 'return' => false]
			],
			true
		);
		$this->mock_function_param_value(
			'api_cron_setting_get_multi_byitem',
			[
				['params' => 2, 'return' => ['minute' => 'whatever']],
				['params' => 3, 'return' => []],
				['params' => 4, 'return' => ['minute' => '*']],
				['params' => 5, 'return' => ['hour' => '1,2,3,4,5']],
			],
			[
				'minute' => '0-59',
				'hour' => '1-10',
				'dayofmonth' => 8,
				'month' => '1-12',
				'dayofweek' => 6,
			]
		);

		$this->assertSameEquals($expected_value, api_cron_parse($id));
	}

	/**
	 * @param mixed $minutes
	 * @param mixed $hours
	 * @param mixed $dayofmonths
	 * @param mixed $months
	 * @param mixed $dayofweeks
	 * @return array
	 */
	private function get_cron($minutes, $hours, $dayofmonths, $months, $dayofweeks) {
		return [
			'minute' => (array) $minutes,
			'hour' => (array) $hours,
			'dayofmonth' => (array) $dayofmonths,
			'month' => (array) $months,
			'dayofweek' => (array) $dayofweeks,
		];
	}
}
