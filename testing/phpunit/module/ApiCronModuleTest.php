<?php
/**
 * ApiCronModuleTest
 * Module test for api_cron.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use testing\module\helpers\CronModuleHelper;
use testing\module\helpers\MethodsCheckExistsModuleTrait;
use testing\module\helpers\MethodsSettingsModuleTrait;
use testing\module\helpers\MethodsTagsModuleTrait;

/**
 * Api Cron Module Test
 */
class ApiCronModuleTest extends AbstractPhpunitModuleTest
{
	use CronModuleHelper;
	use MethodsCheckExistsModuleTrait;
	use MethodsSettingsModuleTrait;
	use MethodsTagsModuleTrait;

	/**
	 * Type value
	 */
	private static $type;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		self::$type = self::get_cron_type();
	}

	/**
	 * @group api_cron_add
	 * @return void
	 */
	public function test_api_cron_add() {
		// Failures cron name
		$this->assertFalse(api_cron_add(false));
		$this->assertFalse(api_cron_add(null));
		$this->assertFalse(api_cron_add(''));
		$this->assertFalse(api_cron_add('123'));
		$this->assertFalse(api_cron_add(str_repeat('a', 51)));

		// Failures cron exists
		$this->create_new_cron('default_cron');
		$this->assertFalse(api_cron_add('default_cron'));

		// Success
		$expected_next_cron_id = $this->get_expected_next_cron_id();
		$cron_name = uniqid('test_cron');
		$this->assertSameEquals($expected_next_cron_id, api_cron_add($cron_name));
	}

	/**
	 * @group api_cron_delete
	 * @return void
	 */
	public function test_api_cron_delete() {
		// Failures cron name
		$this->assertFalse(api_cron_delete(false));
		$this->assertFalse(api_cron_delete(null));
		$this->assertFalse(api_cron_delete(''));
		$this->assertFalse(api_cron_delete('whatever'));

		// Success
		$cron_id = $this->create_new_cron();
		$this->assertTrue(api_cron_delete($cron_id));
	}

	/**
	 * @group api_cron_listall
	 * @return void
	 */
	public function test_api_cron_listall() {
		// Failures cron name
		$this->purge_all_crons();

		$this->assertEmpty(api_cron_listall());
		$this->assertEmpty(api_cron_listall(true, false));
		$this->assertEmpty(api_cron_listall(false, true));
		$this->assertEmpty(api_cron_listall(true, true));

		$cron1_id = $this->create_new_cron('cron1_listall');
		$cron2_id = $this->create_new_cron('cron2_listall');

		// Success
		$crons = api_cron_listall();
		$this->assertInternalType('array', $crons);
		$this->assertNotEmpty($crons);
		$this->assertArrayHasKey($cron1_id, $crons);
		$this->assertArrayHasKey($cron2_id, $crons);
		$this->assert_cron(
			$crons[$cron1_id],
			[
				'name' => 'cron1_listall',
				'status' => 'DISABLED'
			]
		);
		$this->assert_cron(
			$crons[$cron2_id],
			[
				'name' => 'cron2_listall',
				'status' => 'DISABLED'
			]
		);

		// Success with short
		$crons = api_cron_listall(true);
		$this->assertInternalType('array', $crons);
		$this->assertNotEmpty($crons);
		$this->assertSameEquals(
			$crons,
			[$cron1_id => 'cron1_listall', $cron2_id => 'cron2_listall']
		);

		$active_cron_id = $this->create_new_cron('active_cron_listall', 'ACTIVE');

		// Success active only
		$crons = api_cron_listall(false, true);
		$this->assertInternalType('array', $crons);
		$this->assertNotEmpty($crons);
		$this->assertArrayHasKey($active_cron_id, $crons);
		$this->assertArrayNotHasKey($cron1_id, $crons);
		$this->assertArrayNotHasKey($cron2_id, $crons);
		$this->assert_cron(
			$crons[$active_cron_id],
			[
				'name' => 'active_cron_listall',
				'status' => 'ACTIVE'
			]
		);

		// Success active only with short
		$crons = api_cron_listall(true, true);
		$this->assertInternalType('array', $crons);
		$this->assertNotEmpty($crons);
		$this->assertArrayHasKey($active_cron_id, $crons);
		$this->assertArrayNotHasKey($cron1_id, $crons);
		$this->assertArrayNotHasKey($cron2_id, $crons);
		$this->assertSameEquals(
			$crons,
			[$active_cron_id => 'active_cron_listall']
		);
	}

	/**
	 * @group api_cron_run
	 * @return void
	 */
	public function test_api_cron_run() {
		$this->create_new_cron();
		$this->assertNull(api_cron_run());
	}

	/**
	 * @group api_cron_isdue
	 * @return void
	 */
	public function test_api_cron_isdue_failures() {
		// Failures cron name
		$this->purge_all_crons();

		// Failures wrong id
		$this->assertFalse(api_cron_isdue(null));
		$this->assertFalse(api_cron_isdue(false));
		$this->assertFalse(api_cron_isdue(''));

		$cron_id = $this->create_new_cron();

		// Failures cron not active
		$this->assertFalse(api_cron_isdue($cron_id));

		$this->assertTrue(api_cron_setting_set($cron_id, 'status', 'ACTIVE'));
		$this->assertTrue(api_cron_setting_set($cron_id, 'minute', 'bad_structure'));

		// Failures cron bad structure
		$this->assertFalse(api_cron_isdue($cron_id));

		$this->assertTrue(api_cron_setting_set($cron_id, 'minute', '*'));
		$this->assertTrue(api_cron_setting_set($cron_id, 'timezone', 'whatever'));

		// Failures cron bad timezone
		$this->assertFalse(api_cron_isdue($cron_id));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_cron_isdue_success_data() {
		$datetime = function($time, $format) {
			$date = new \DateTime($time, new \DateTimeZone('Australia/Brisbane'));
			return $date->format($format);
		};

		return [
			// Failure wrong values
			[false, $this->get_cron_settings_now(999, 999, 999, 999, 999)],
			// Failure wrong value minutes
			[false, $this->get_cron_settings_now(999)],
			// Failure wrong value hours
			[false, $this->get_cron_settings_now(null, 999)],
			// Failure wrong value day of month
			[false, $this->get_cron_settings_now(null, null, 999)],
			// Failure wrong value months
			[false, $this->get_cron_settings_now(null, null, null, 999)],
			// Failure wrong value day of weeks
			[false, $this->get_cron_settings_now(null, null, null, null, 999)],

			// Failure minutes does not matches
			[false, ['minute' => $datetime('now + 5 minutes', 'i')]],
			// Failure hours does not matches
			[false, ['hour' => $datetime('now - 4 hours', 'G')]],
			// Failure day of month does not matches
			[false, ['dayofmonth' => $datetime('now + 3 days', 'j')]],
			// Failure month does not matches
			[false, ['month' => $datetime('now - 2 month', 'n')]],
			// Failure day of week does not matches
			[false, ['dayofweek' => $datetime('now + 1 day', 'N')]],

			// Failure timezone
			[false, $this->get_cron_settings_now(4, 3), 'today 13:04:30'],

			// Success every minutes (empty tokens)
			[true],

			// Success today right now
			[true, $this->get_cron_settings_now(2, 1, null, null, null), 'today 01:02:30 Australia/Brisbane'],
			// Success any minutes
			[true, $this->get_cron_settings_now('*')],
			// Success any hours
			[true, $this->get_cron_settings_now(4, '*'), 'today 03:04:30 Australia/Brisbane'],
			// Success any day of month
			[true, $this->get_cron_settings_now(6, 5, '*'), 'today 05:06:30 Australia/Brisbane'],
			// Success any months
			[true, $this->get_cron_settings_now(8, 7, null, '*'), 'today 07:08:30 Australia/Brisbane'],
			// Success any day of weeks
			[true, $this->get_cron_settings_now(10, 9, null, null, '*'), 'today 09:10:30 Australia/Brisbane'],
		];
	}

	/**
	 * @group api_cron_isdue
	 * @dataProvider api_cron_isdue_success_data
	 * @param mixed  $expected_value
	 * @param array  $settings
	 * @param string $time
	 * @return void
	 */
	public function test_api_cron_isdue_success($expected_value, array $settings = [], $time = 'now') {
		$cron_id = $this->create_new_cron();
		$this->assertTrue(api_cron_setting_set($cron_id, 'status', 'ACTIVE'));
		foreach ($settings as $setting_name => $setting_value) {
			$this->assertTrue(api_cron_setting_set($cron_id, $setting_name, $setting_value));
		}

		$this->assertSameEquals($expected_value, api_cron_isdue($cron_id, $time));
	}

	/**
	 * @group api_cron_parse
	 * @return void
	 */
	public function test_api_cron_parse() {
		// Failures wrong id
		$this->assertFalse(api_cron_parse(null));
		$this->assertFalse(api_cron_parse(false));
		$this->assertFalse(api_cron_parse(''));

		$cron_id = $this->create_new_cron();

		// Failures not a well formed cron
		$this->assertTrue(api_cron_setting_set($cron_id, 'minute', 'whatever'));
		$this->assertFalse(api_cron_parse($cron_id));
		// Failures not a well formed list
		$this->assertTrue(api_cron_setting_set($cron_id, 'minute', '0 ,1'));
		$this->assertFalse(api_cron_parse($cron_id));
		// Failures not a well formed range
		$this->assertTrue(api_cron_setting_set($cron_id, 'minute', '1 -15'));
		$this->assertFalse(api_cron_parse($cron_id));
		$this->assertTrue(api_cron_setting_set($cron_id, 'minute', '*'));
		// Success each segments all '*'
		$this->assertSameEquals(
			$this->get_cron_settings_now([], [], [], [], []),
			api_cron_parse($cron_id)
		);

		// Success segments as lists '8,14,18'
		$this->assertTrue(api_cron_setting_set($cron_id, 'minute', '0,1,2,3,4,5,6,7,8,9,10'));
		$this->assertSameEquals(
			$this->get_cron_settings_now(range(0, 10), [], [], [], []),
			api_cron_parse($cron_id)
		);
		$this->assertTrue(api_cron_setting_set($cron_id, 'hour', '5,10,15,20'));
		$this->assertSameEquals(
			$this->get_cron_settings_now(range(0, 10), [5, 10, 15, 20], [], [], []),
			api_cron_parse($cron_id)
		);
		$this->assertTrue(api_cron_setting_set($cron_id, 'dayofmonth', '5,6,7,8,9,10'));
		$this->assertSameEquals(
			$this->get_cron_settings_now(range(0, 10), [5, 10, 15, 20], range(5, 10), [], []),
			api_cron_parse($cron_id)
		);
		$this->assertTrue(api_cron_setting_set($cron_id, 'month', '1,2,3,10,11,12'));
		$this->assertSameEquals(
			$this->get_cron_settings_now(range(0, 10), [5, 10, 15, 20], range(5, 10), [1, 2, 3, 10, 11, 12], []),
			api_cron_parse($cron_id)
		);
		$this->assertTrue(api_cron_setting_set($cron_id, 'dayofweek', '1,7'));
		$this->assertSameEquals(
			$this->get_cron_settings_now(range(0, 10), [5, 10, 15, 20], range(5, 10), [1, 2, 3, 10, 11, 12], [1, 7]),
			api_cron_parse($cron_id)
		);
		$cron_id = $this->create_new_cron();

		// Success segments as ranges "8-12"
		$this->assertTrue(api_cron_setting_set($cron_id, 'minute', '0-10'));
		$this->assertSameEquals(
			$this->get_cron_settings_now(range(0, 10), [], [], [], []),
			api_cron_parse($cron_id)
		);
		$this->assertTrue(api_cron_setting_set($cron_id, 'hour', '5-20'));
		$this->assertSameEquals(
			$this->get_cron_settings_now(range(0, 10), range(5, 20), [], [], []),
			api_cron_parse($cron_id)
		);
		$this->assertTrue(api_cron_setting_set($cron_id, 'dayofmonth', '3-10'));
		$this->assertSameEquals(
			$this->get_cron_settings_now(range(0, 10), range(5, 20), range(3, 10), [], []),
			api_cron_parse($cron_id)
		);
		$this->assertTrue(api_cron_setting_set($cron_id, 'month', '1-12'));
		$this->assertSameEquals(
			$this->get_cron_settings_now(range(0, 10), range(5, 20), range(3, 10), range(1, 12), []),
			api_cron_parse($cron_id)
		);
		$this->assertTrue(api_cron_setting_set($cron_id, 'dayofweek', '1-7'));
		$this->assertSameEquals(
			$this->get_cron_settings_now(range(0, 10), range(5, 20), range(3, 10), range(1, 12), range(1, 7)),
			api_cron_parse($cron_id)
		);

		$this->assertTrue(api_cron_setting_set($cron_id, 'minute', 1));
		$this->assertTrue(api_cron_setting_set($cron_id, 'hour', 2));
		$this->assertTrue(api_cron_setting_set($cron_id, 'dayofmonth', 3));
		$this->assertTrue(api_cron_setting_set($cron_id, 'month', 4));
		$this->assertTrue(api_cron_setting_set($cron_id, 'dayofweek', 5));

		// Success specific minute
		$this->assertSameEquals(
			$this->get_cron_settings_now([1], [2], [3], [4], [5]),
			api_cron_parse($cron_id)
		);
	}

	/**
	 * @param mixed $minutes
	 * @param mixed $hours
	 * @param mixed $dayofmonths
	 * @param mixed $months
	 * @param mixed $dayofweeks
	 * @return array
	 */
	private function get_cron_settings_now($minutes = null, $hours = null, $dayofmonths = null, $months = null, $dayofweeks = null) {
		$date = new \DateTime('now', new \DateTimeZone('Australia/Brisbane'));

		return [
			'minute' => (is_null($minutes) ? $date->format('i') : $minutes),
			'hour' => (is_null($hours) ? $date->format('G') : $hours),
			'dayofmonth' => (is_null($dayofmonths) ? $date->format('j') : $dayofmonths),
			'month' => (is_null($months) ? $date->format('n') : $months),
			'dayofweek' => (is_null($dayofweeks) ? $date->format('N') : $dayofweeks),
		];
	}
}
