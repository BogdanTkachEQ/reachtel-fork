<?php
/**
 * ApiRestrictionsUnitTest
 * Unit test for api_restrictions.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Api Restrictions Unit Test class
 */
class ApiRestrictionsUnitTest extends AbstractPhpunitUnitTest
{
	const DEFAULT_UNIQUE_ID = 'UNIQ';

	/**
	 * {@inheritDoc}
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		/* mock global functions used for this test */
		$this->mock_function_value('api_misc_uniqueid', self::DEFAULT_UNIQUE_ID);
		$this->mock_function_value('api_campaigns_setting_set', false);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_restrictions_time_remove_data() {
		return [
			// Failures campaignid
			[false, null],
			[false, false],
			[false, ''],
			// Failures periodid
			[false, 1, null],
			[false, null],
			[false, false],
			[false, ''],

			// Success
			[true, 1, 1]
		];
	}

	/**
	 * @dataProvider api_restrictions_time_remove_data
	 * @group api_restrictions_time_recurring_remove
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @param mixed $periodid
	 * @return void
	 */
	public function test_api_restrictions_time_recurring_remove($expected_value, $campaignid = 1, $periodid = 1) {
		$this->mock_function_value(
			'api_restrictions_time_structure',
			['recurring' => [$periodid => 'ArrayOfTime'], 'specific' => []]
		);

		$this->assertSameEquals($expected_value, api_restrictions_time_recurring_remove($campaignid, $periodid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_restrictions_time_listsingle_data() {
		return [
			// Failures campaignid
			[false, null],
			[false, false],
			[false, ''],
			// Failures periodid
			[false, 1, null],
			[false, 1, false],
			[false, 1, ''],

			// Success
			['ArrayOfTime1', 1, 1],
			['ArrayOfTime2', 1, 2]
		];
	}

	/**
	 * @dataProvider api_restrictions_time_listsingle_data
	 * @group api_restrictions_time_recurring_listsingle
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @param mixed $periodid
	 * @return void
	 */
	public function test_api_restrictions_time_recurring_listsingle($expected_value, $campaignid = 1, $periodid = 1) {
		$this->mock_function_value(
			'api_restrictions_time_structure',
			['recurring' => [1 => 'ArrayOfTime1', 2 => 'ArrayOfTime2'], 'specific' => []]
		);

		$this->assertSameEquals($expected_value, api_restrictions_time_recurring_listsingle($campaignid, $periodid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_restrictions_time_recurring_listall_data() {
		// test time is set to Tuesday 17 May 2016 at 12:00:00 (1463450400)
		$time = 1463450400;

		return [
			// Failures campaignid
			[false, [], $time, null],
			[false, [], $time, false],
			[false, [], $time, ''],

			// Success
			[[],[]],
			[
				[
					['starttime' => '09:00:00', 'endtime' => '10:00:00', 'daysofweek' => 31, 'status' => 1], // No days set + 12PM not included => future
					['starttime' => '11:00:00', 'endtime' => '12:30:00', 'daysofweek' => 31, 'status' => 0], // No days set + 12PM included => current
					['starttime' => '09:00:00', 'endtime' => '10:00:00', 'daysofweek' => 2, 'status' => 1], // Tuesday + 12PM not included => future
					['starttime' => '11:00:00', 'endtime' => '12:30:00', 'daysofweek' => 2, 'status' => 0], // Tuesday + 12PM included => current
					['starttime' => '09:00:00', 'endtime' => '10:00:00', 'daysofweek' => 16, 'status' => 1], // Friday + 12PM not included => future
					['starttime' => '11:00:00', 'endtime' => '12:30:00', 'daysofweek' => 16, 'status' => 1], // Friday + 12PM included => future
					['starttime' => '12:00:00', 'endtime' => '12:02:00', 'daysofweek' => 127, 'status' => 0], // All days + 12PM included => current
					['starttime' => '11:00:00', 'endtime' => '12:00:00', 'daysofweek' => 127, 'status' => 0], // All days + 12PM included => current
				],
				[
					['starttime' => '09:00:00', 'endtime' => '10:00:00'],
					['starttime' => '11:00:00', 'endtime' => '12:30:00'],
					['starttime' => '09:00:00', 'endtime' => '10:00:00', 'daysofweek' => 2],
					['starttime' => '11:00:00', 'endtime' => '12:30:00', 'daysofweek' => 2],
					['starttime' => '09:00:00', 'endtime' => '10:00:00', 'daysofweek' => 16],
					['starttime' => '11:00:00', 'endtime' => '12:30:00', 'daysofweek' => 16],
					['starttime' => '12:00:00', 'endtime' => '12:02:00', 'daysofweek' => 127],
					['starttime' => '11:00:00', 'endtime' => '12:00:00', 'daysofweek' => 127],
				],
				$time
			]
		];
	}

	/**
	 * @dataProvider api_restrictions_time_recurring_listall_data
	 * @group api_restrictions_time_recurring_listall
	 * @param mixed   $expected_value
	 * @param array   $recurring
	 * @param integer $time
	 * @param mixed   $campaignid
	 * @return void
	 */
	public function test_api_restrictions_time_recurring_listall($expected_value, array $recurring = [], $time = null, $campaignid = 1) {
		$this->mock_function_value(
			'api_restrictions_time_structure',
			['recurring' => $recurring, 'specific' => []]
		);
		$this->mock_function_value('api_campaigns_gettimezone', new \DateTimeZone('Australia/Brisbane'));

		if ($time) {
			$this->mock_function_value('time', $time);
		}

		// set date_create_from_format returns date on Tuesday 17 May 2016 at any time
		$this->mock_function_value('date_create_from_format', "return DateTime::createFromFormat('Y-m-d ' . func_get_arg(0), '2016-05-17 ' . func_get_arg(1), func_get_arg(2));", true);

		$this->assertSameEquals($expected_value, api_restrictions_time_recurring_listall($campaignid));

		$this->remove_mocked_functions();
	}

	/**
	 * @dataProvider api_restrictions_time_remove_data
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @param mixed $periodid
	 * @return void
	 */
	public function test_api_restrictions_time_specific_remove($expected_value, $campaignid = 1, $periodid = 1) {
		$this->mock_function_value(
			'api_restrictions_time_structure',
			['recurring' => [], 'specific' => [$periodid => 'ArrayOfTime']]
		);

		$this->assertSameEquals($expected_value, api_restrictions_time_specific_remove($campaignid, $periodid));
	}

	/**
	 * @dataProvider api_restrictions_time_listsingle_data
	 * @group api_restrictions_time_specific_listsingle
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @param mixed $periodid
	 * @return void
	 */
	public function test_api_restrictions_time_specific_listsingle($expected_value, $campaignid = 1, $periodid = 1) {
		$this->mock_function_value(
			'api_restrictions_time_structure',
			['recurring' => [], 'specific' => [1 => 'ArrayOfTime1', 2 => 'ArrayOfTime2']]
		);

		$this->assertSameEquals($expected_value, api_restrictions_time_specific_listsingle($campaignid, $periodid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_restrictions_time_specific_listall_data() {
		// test time is set to Tuesday 17 May 2016 at 12:00:00 in DEFAULT_TIMEZONE (1463450400)
		$time = strtotime('17-05-2016 12:00:00 Australia/Brisbane');

		return [
			// Failures campaignid
			[false, [], $time, null],
			[false, [], $time, false],
			[false, [], $time, ''],

			// Success
			[[],[]],
			[
				[
					['starttime' => strtotime('15-05-2016 10:00:00 Australia/Brisbane'), 'endtime' => strtotime('16-05-2016 20:00:00 Australia/Brisbane'), 'status' => -1],
					['starttime' => strtotime('16-05-2016 11:00:00 Australia/Brisbane'), 'endtime' => strtotime('17-05-2016 21:00:00 Australia/Brisbane'), 'status' => 0],
					['starttime' => strtotime('17-05-2016 13:00:00 Australia/Brisbane'), 'endtime' => strtotime('18-05-2016 22:00:00 Australia/Brisbane'), 'status' => 1],
					['starttime' => strtotime('17-05-2016 12:00:00 Australia/Brisbane'), 'endtime' => strtotime('19-05-2016 23:00:00 Australia/Brisbane'), 'status' => 0],
					['starttime' => strtotime('16-05-2016 13:00:00 Australia/Brisbane'), 'endtime' => strtotime('17-05-2016 12:00:00 Australia/Brisbane'), 'status' => 0],
				],
				[
					['starttime' => strtotime('15-05-2016 10:00:00 Australia/Brisbane'), 'endtime' => strtotime('16-05-2016 20:00:00 Australia/Brisbane')],
					['starttime' => strtotime('16-05-2016 11:00:00 Australia/Brisbane'), 'endtime' => strtotime('17-05-2016 21:00:00 Australia/Brisbane')],
					['starttime' => strtotime('17-05-2016 13:00:00 Australia/Brisbane'), 'endtime' => strtotime('18-05-2016 22:00:00 Australia/Brisbane')],
					['starttime' => strtotime('17-05-2016 12:00:00 Australia/Brisbane'), 'endtime' => strtotime('19-05-2016 23:00:00 Australia/Brisbane')],
					['starttime' => strtotime('16-05-2016 13:00:00 Australia/Brisbane'), 'endtime' => strtotime('17-05-2016 12:00:00 Australia/Brisbane')],
				],
				$time
			]
		];
	}

	/**
	 * @dataProvider api_restrictions_time_specific_listall_data
	 * @group api_restrictions_time_specific_listall
	 * @param mixed   $expected_value
	 * @param array   $specific
	 * @param integer $time
	 * @param mixed   $campaignid
	 * @return void
	 */
	public function test_api_restrictions_time_specific_listall($expected_value, array $specific = [], $time = null, $campaignid = 1) {
		if ($time) {
			$this->mock_function_value('time', $time);
		}

		$this->mock_function_value(
			'api_restrictions_time_structure',
			['recurring' => [], 'specific' => $specific]
		);

		$this->assertSameEquals($expected_value, api_restrictions_time_specific_listall($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_restrictions_time_check_data() {
		$now = 1458090000; // Wed, 16 Mar 2016, 11:00:00 in DEFAULT_TIMEZONE
		$minus_1day = strtotime('-1 day', $now);
		$minus_2days = strtotime('-2 day', $now);
		$plus_1day = strtotime('+1 day', $now);
		$plus_2days = strtotime('+2 day', $now);
		$hour_now = '11:00:00';
		$minus_1hour = '10:00:00';
		$minus_2hours = '09:00:00';
		$plus_1hour = '12:00:00';
		$plus_2hours = '13:00:00';

		return [
			// Failures campaignid
			[false, null],
			[false, false],
			[false, ''],

			// Failures timezone
			[false, 1, [], [], $now, 'whatever/whatever'],

			// Failures specific times
			[false, 1, [['starttime' => $plus_1day, 'endtime' => $plus_2days]]],
			[false, 2, [['starttime' => $minus_2days, 'endtime' => $minus_1day]]],
			[false, 3, [['starttime' => $plus_2days, 'endtime' => $minus_2days]]],
			[false, 4, [['starttime' => $plus_2days, 'endtime' => $now]]],

			// Failures recurring times
			[false, 1, [], [['starttime' => $plus_1hour, 'endtime' => $plus_2hours]]],
			[false, 2, [], [['starttime' => $minus_2hours, 'endtime' => $minus_1hour]]],
			[false, 3, [], [['starttime' => $plus_2hours, 'endtime' => $minus_1hour]]],
			[false, 4, [], [['starttime' => $minus_1hour, 'endtime' => $plus_1hour]], strtotime('+3 days', $now)], // weekend day

			// Failures recurring times with Brisbane timezone
			[false, 1, [], [['starttime' => $plus_1hour, 'endtime' => $plus_2hours]], $now, 'Australia/Brisbane'],
			[false, 2, [], [['starttime' => $minus_2hours, 'endtime' => $minus_1hour]], $now, 'Australia/Brisbane'],
			[false, 3, [], [['starttime' => $plus_2hours, 'endtime' => $minus_1hour]], $now, 'Australia/Brisbane'],
			[false, 4, [], [['starttime' => $minus_1hour, 'endtime' => $plus_1hour]], strtotime('+3 days', $now), 'Australia/Brisbane'], // weekend day

			// Success edges
			[true, 5, [], [['starttime' => $minus_1hour, 'endtime' => $hour_now]]],
			[true, 5, [], [['starttime' => $minus_1hour, 'endtime' => $hour_now]], $now, 'Australia/Brisbane'],
			[true, 5, [], [['starttime' => $minus_1hour, 'endtime' => $hour_now]], strtotime('last day of +2 months 2 days', $now), 'Australia/Sydney'],

			// Success & Failures recurring times with Sydney timezone with DST
			[true, 1, [], [['starttime' => $plus_1hour, 'endtime' => $plus_2hours]], $now, 'Australia/Sydney'],
			[false, 2, [], [['starttime' => $minus_2hours, 'endtime' => $minus_1hour]], $now, 'Australia/Sydney'],
			[false, 3, [], [['starttime' => $plus_2hours, 'endtime' => $minus_1hour]], $now, 'Australia/Sydney'],
			[false, 4, [], [['starttime' => $minus_1hour, 'endtime' => $plus_1hour]], strtotime('+3 days', $now), 'Australia/Sydney'], // weekend day
			[false, 5, [], [['starttime' => $minus_1hour, 'endtime' => $hour_now]], $now, 'Australia/Sydney'],

			// Success & Failures recurring times with Sydney timezone without DST
			[false, 1, [], [['starttime' => $plus_1hour, 'endtime' => $plus_2hours]], strtotime('last day of +2 months 2 days', $now), 'Australia/Sydney'],
			[false, 2, [], [['starttime' => $minus_2hours, 'endtime' => $minus_1hour]], strtotime('last day of +2 months 2 days', $now), 'Australia/Sydney'],
			[false, 3, [], [['starttime' => $plus_2hours, 'endtime' => $minus_1hour]], strtotime('last day of +2 months 2 days', $now), 'Australia/Sydney'],
			[false, 4, [], [['starttime' => $minus_1hour, 'endtime' => $plus_1hour]], strtotime('+2 months 5 days', $now), 'Australia/Sydney'], // weekend day

			// Success & Failures recurring times with Adelaide timezone with DST
			[true, 1, [], [['starttime' => $minus_2hours, 'endtime' => $plus_2hours]], $now, 'Australia/Adelaide'],
			[false, 1, [], [['starttime' => $plus_1hour, 'endtime' => $plus_2hours]], $now, 'Australia/Adelaide'],
			[false, 2, [], [['starttime' => $minus_2hours, 'endtime' => $minus_1hour]], $now, 'Australia/Adelaide'],
			[false, 3, [], [['starttime' => $plus_2hours, 'endtime' => $minus_1hour]], $now, 'Australia/Adelaide'],
			[false, 4, [], [['starttime' => $minus_1hour, 'endtime' => $plus_1hour]], strtotime('+3 days', $now), 'Australia/Adelaide'], // weekend day
			[false, 5, [], [['starttime' => $minus_1hour, 'endtime' => $hour_now]], $now, 'Australia/Adelaide'],

			// Success & Failures recurring times with Adelaide timezone without DST
			[true, 1, [], [['starttime' => $minus_2hours, 'endtime' => $plus_2hours]], strtotime('last day of +2 months 2 days', $now), 'Australia/Adelaide'],
			[false, 1, [], [['starttime' => $plus_1hour, 'endtime' => $plus_2hours]], strtotime('last day of +2 months 2 days', $now), 'Australia/Adelaide'],
			[false, 2, [], [['starttime' => $minus_2hours, 'endtime' => $minus_1hour]], strtotime('last day of +2 months 2 days', $now), 'Australia/Adelaide'],
			[false, 3, [], [['starttime' => $plus_2hours, 'endtime' => $minus_1hour]], strtotime('last day of +2 months 2 days', $now), 'Australia/Adelaide'],
			[false, 4, [], [['starttime' => $minus_1hour, 'endtime' => $plus_1hour]], strtotime('+2 months 5 days', $now), 'Australia/Adelaide'], // weekend day
			[true, 5, [], [['starttime' => $minus_1hour, 'endtime' => $hour_now]], strtotime('last day of +2 months 2 days', $now), 'Australia/Adelaide'],

			// Failures recurring times with Perth timezone
			[true, 1, [], [['starttime' => $minus_2hours, 'endtime' => $plus_2hours]], $now, 'Australia/Perth'],
			[false, 1, [], [['starttime' => $plus_1hour, 'endtime' => $plus_2hours]], $now, 'Australia/Perth'],
			[true, 2, [], [['starttime' => $minus_2hours, 'endtime' => $minus_1hour]], $now, 'Australia/Perth'],
			[false, 3, [], [['starttime' => $plus_2hours, 'endtime' => $minus_1hour]], $now, 'Australia/Perth'],
			[false, 4, [], [['starttime' => $minus_1hour, 'endtime' => $plus_1hour]], strtotime('+3 days', $now), 'Australia/Perth'], // weekend day
			[false, 5, [], [['starttime' => $minus_1hour, 'endtime' => $hour_now]], $now, 'Australia/Perth'],

			// Success & Failures with weekend recurring periods
			[false, 1, [], [['starttime' => $plus_1hour, 'endtime' => $plus_2hours, 'daysofweek' => 96]], $now, 'Australia/Brisbane'],
			[false, 2, [], [['starttime' => $minus_2hours, 'endtime' => $minus_1hour, 'daysofweek' => 96]], $now, 'Australia/Brisbane'],
			[false, 3, [], [['starttime' => $plus_2hours, 'endtime' => $minus_1hour, 'daysofweek' => 96]], $now, 'Australia/Brisbane'],
			[false, 4, [], [['starttime' => $minus_1hour, 'endtime' => $hour_now, 'daysofweek' => 96]], $now, 'Australia/Brisbane'],

			[true, 5, [], [['starttime' => $minus_1hour, 'endtime' => $plus_1hour, 'daysofweek' => 96]], strtotime('+3 days', $now), 'Australia/Brisbane'], // weekend day
			[true, 6, [], [['starttime' => $minus_1hour, 'endtime' => $plus_1hour, 'daysofweek' => 32]], strtotime('+3 days', $now), 'Australia/Brisbane'], // Saturday and Monday

			// Success specific
			[true, 1, [
				['starttime' => $minus_2days, 'endtime' => $minus_1day], // inactive
				['starttime' => $minus_1day, 'endtime' => $plus_1day], // active
			]],
			[true, 1, [['starttime' => $now, 'endtime' => $plus_1day]]],

			// Success recurring
			[true, 1, [], [
				['starttime' => $minus_2hours, 'endtime' => $minus_1hour], // inactive
				['starttime' => $minus_1hour, 'endtime' => $plus_1hour], // active
			]],
			[true, 1, [], [
				['starttime' => $minus_2hours, 'endtime' => $plus_1hour], // active
			]], // week day
			[true, 1, [], [['starttime' => $hour_now, 'endtime' => $plus_2hours]]],
		];
	}

	/**
	 * @dataProvider api_restrictions_time_check_data
	 * @group api_restrictions_time_check
	 * @param boolean $expected_value
	 * @param mixed   $campaignid
	 * @param array   $specific
	 * @param array   $recurring
	 * @param integer $unix_timestamp
	 * @param mixed   $timezone
	 * @return void
	 */
	public function test_api_restrictions_time_check($expected_value, $campaignid, array $specific = [], array $recurring = [], $unix_timestamp = 1458090000, $timezone = null) {

		$this->mock_function_value('time', $unix_timestamp);

		$this->mock_function_value(
			'api_restrictions_time_structure',
			['recurring' => $recurring, 'specific' => $specific]
		);

		$this->mock_function_value(
			'api_campaigns_setting_getsingle',
			$timezone
		);

		$timezone = $timezone ? : 'Australia/Brisbane'; // DEFAULT_TIMEZONE
		$timezone = ", (new DateTimeZone('{$timezone}'))";
		$YYYYMMDD = date('Y-m-d', $unix_timestamp);
		$this->mock_function_param_value(
			'date_create_from_format',
			[
				['params' => [1 => '09:00:00'], 'return' => "new \DateTime('{$YYYYMMDD} 09:00:00'{$timezone})", 'raw' => true],
				['params' => [1 => '10:00:00'], 'return' => "new \DateTime('{$YYYYMMDD} 10:00:00'{$timezone})", 'raw' => true],
				['params' => [1 => '11:00:00'], 'return' => "new \DateTime('{$YYYYMMDD} 11:00:00'{$timezone})", 'raw' => true],
				['params' => [1 => '12:00:00'], 'return' => "new \DateTime('{$YYYYMMDD} 12:00:00'{$timezone})", 'raw' => true],
				['params' => [1 => '13:00:00'], 'return' => "new \DateTime('{$YYYYMMDD} 13:00:00'{$timezone})", 'raw' => true]
			],
			false
		);

		$this->assertSameEquals($expected_value, api_restrictions_time_check($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_restrictions_time_structure_data() {
		return [
			[['recurring' => [], 'specific' => []]],
			[[
				'recurring' => [50 => ['starttime' => '10:00', 'endtime' => '12:00']],
				'specific' => [45 => ['starttime' => 1440035000, 'endtime' => 1440040000]]
			], 'a:2:{s:9:"recurring";a:1:{i:50;a:2:{s:9:"starttime";s:5:"10:00";s:7:"endtime";s:5:"12:00";}}s:8:"specific";a:1:{i:45;a:2:{s:9:"starttime";i:1440035000;s:7:"endtime";i:1440040000;}}}'],
		];
	}

	/**
	 * @dataProvider api_restrictions_time_structure_data
	 * @group api_restrictions_time_structure
	 * @param array  $expected_value
	 * @param string $timing
	 * @return void
	 */
	public function test_api_restrictions_time_structure(array $expected_value, $timing = false) {
		$this->mock_function_value('api_campaigns_setting_getsingle', $timing);

		api_restrictions_time_structure(1);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_restrictions_channels_campaign_data() {
		return [
			// Failures campaignid
			[false, [], null],
			[false, [], false],
			[false, [], ''],

			// Failures maxchannels
			[false, [], 1, null],
			[false, [], 1, false],
			[false, [], 1, ''],

			// Failures channelMap
			[false, [], 1, 2],
			[false, ['campaigns' => [9 => 1]], 1, 2],
			[false, ['campaigns' => [1 => 1]], 1, 2],

			// Success
			[true, ['campaigns' => [1 => 2]], 1, 2],
			[true, ['campaigns' => [1 => 3]], 1, 2],
		];
	}

	/**
	 * @dataProvider api_restrictions_channels_campaign_data
	 * @group api_restrictions_channels_campaign
	 * @param boolean $expected_value
	 * @param array   $channelMap
	 * @param mixed   $campaignid
	 * @param mixed   $maxchannels
	 * @return void
	 */
	public function test_api_restrictions_channels_campaign($expected_value, array $channelMap = [], $campaignid = null, $maxchannels = null) {
		$this->assertSameEquals($expected_value, api_restrictions_channels_campaign($channelMap, $campaignid, $maxchannels));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_restrictions_caps_provider_data() {
		return [
			// Failures providerid
			[false, null, null],
			[false, null, false],
			[false, null, ''],

			// Failures provider
			[false, []],
			[false, ['callspersecond' => null]],
			[false, ['callspersecond' => false]],
			[false, ['callspersecond' => '']],

			// Success caps not applied
			[false, ['callspersecond' => 2]],
			[false, ['callspersecond' => 2, 'lastcall' => 1440400000.0000]],

			// Success caps applied
			[true, ['callspersecond' => 2, 'lastcall' => 1440400000.1000]],
		];
	}

	/**
	 * @dataProvider api_restrictions_caps_provider_data
	 * @group api_restrictions_caps_provider
	 * @param boolean $expected_value
	 * @param mixed   $default_provider
	 * @param mixed   $providerid
	 * @param mixed   $provider
	 * @return void
	 */
	public function test_api_restrictions_caps_provider($expected_value, $default_provider, $providerid = 1, $provider = false) {
		$this->mock_function_value('api_voice_supplier_setting_get_multi_byitem', $default_provider);

		$this->mock_function_value('microtime', 1440400000.5000);

		$this->assertSameEquals($expected_value, api_restrictions_caps_provider($providerid, $provider));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_restrictions_caps_sms_provider_data() {
		return [
			// Failures providerid
			[false, null, null],
			[false, null, false],
			[false, null, ''],

			// Failures provider
			[false, []],
			[false, ['smspersecond' => null]],
			[false, ['smspersecond' => false]],
			[false, ['smspersecond' => '']],

			// Success caps sms not applied
			[false, ['smspersecond' => 50]],
			[false, ['smspersecond' => 4, 'lastsms' => 1440450000.7499]],

			// Success caps sms applied
			[true, ['smspersecond' => 4, 'lastsms' => 1440450000.7501]],
		];
	}

	/**
	 * @dataProvider api_restrictions_caps_sms_provider_data
	 * @group api_restrictions_caps_sms_provider
	 * @param boolean $expected_value
	 * @param mixed   $default_provider
	 * @param mixed   $providerid
	 * @param mixed   $provider
	 * @return void
	 */
	public function test_api_restrictions_caps_sms_provider($expected_value, $default_provider, $providerid = 1, $provider = false) {
		$this->mock_function_value('api_sms_supplier_setting_get_multi_byitem', $default_provider);

		$this->mock_function_value('microtime', 1440450001.0000);

		$this->assertSameEquals($expected_value, api_restrictions_caps_sms_provider($providerid, $provider));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_restrictions_caps_hlr_provider_data() {
		return [
			// Failures providerid
			[false, null, null],
			[false, null, false],
			[false, null, ''],

			// Failures provider
			[false, []],
			[false, ['hlrpersecond' => null]],
			[false, ['hlrpersecond' => false]],
			[false, ['hlrpersecond' => '']],

			// Success caps hlr not applied
			[false, ['hlrpersecond' => 9]],
			[false, ['hlrpersecond' => 5, 'lasthlr' => 1440458911.7999]],

			// Success caps hlr applied
			[true, ['hlrpersecond' => 5, 'lasthlr' => 1440458911.8001]],
		];
	}

	/**
	 * @dataProvider api_restrictions_caps_hlr_provider_data
	 * @group api_restrictions_caps_hlr_provider
	 * @param boolean $expected_value
	 * @param mixed   $default_provider
	 * @param mixed   $providerid
	 * @param mixed   $provider
	 * @return void
	 */
	public function test_api_restrictions_caps_hlr_provider($expected_value, $default_provider, $providerid = 1, $provider = false) {
		$this->mock_function_value('api_hlr_supplier_setting_get_multi_byitem', $default_provider);

		$this->mock_function_value('microtime', 1440458912.0000);

		$this->assertSameEquals($expected_value, api_restrictions_caps_hlr_provider($providerid, $provider));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_restrictions_channels_provider_sentsms_data() {
		return [
			[false, null],
			[false, false],
			[false, ''],

			// Success
			[true, 78],
		];
	}

	/**
	 * @dataProvider api_restrictions_channels_provider_sentsms_data
	 * @group api_restrictions_channels_provider_sentsms
	 * @param boolean $expected_value
	 * @param mixed   $campaignid
	 * @return void
	 */
	public function test_api_restrictions_channels_provider_sentsms($expected_value, $campaignid) {
		$this->mock_function_value('api_campaigns_setting_getsingle', null);

		$this->assertSameEquals($expected_value, api_restrictions_channels_provider_sentsms($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_restrictions_channels_provider_sentemail_data() {
		return [
			// Failures campaignid
			[false, null],
			[false, false],
			[false, ''],

			// Success
			[true, 78],
		];
	}

	/**
	 * @dataProvider api_restrictions_channels_provider_sentemail_data
	 * @group api_restrictions_channels_provider_sentsms
	 * @param boolean $expected_value
	 * @param mixed   $campaignid
	 * @return void
	 */
	public function test_api_restrictions_channels_provider_sentemail($expected_value, $campaignid) {
		$this->mock_function_value('api_campaigns_setting_getsingle', null);

		$this->assertSameEquals($expected_value, api_restrictions_channels_provider_sentemail($campaignid));
	}
}
