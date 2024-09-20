<?php
/**
 * ApiAudioModuleTest
 * Module test for api_audio.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use testing\module\helpers\UserModuleHelper;

/**
 * Api Restrictions Module Test
 */
class ApiRestrictionsModuleTest extends AbstractPhpunitModuleTest
{
	use UserModuleHelper;

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_restrictions_time_recurring_add_data() {
		return [
			// Invalid parameters
			'Invalid start time' => [false, 'Sorry, that is not a valid start time', 1, 'invalid'],
			'Invalid end time' => [false, 'Sorry, that is not a valid end time', 2, '09:00', '98:78'],
			'end > start' => [false, 'Sorry, the end time must be after the start time', 3, '12:00', '09:00'],
			'end == start' => [false, 'Sorry, the end time must be after the start time', 4, '12:34', '12:34'],
			'invalid campaign id (empty)' => [false, 'Sorry, that is not a valid campaign', ''],
			'invalid campaign id (null)' => [false, 'Sorry, that is not a valid campaign', null],
			'invalid campaign id (string)' => [false, 'Sorry, that is not a valid campaign', 'invalid campaign id'],
			'invalid existing period id' => [false, 'Sorry, that is not a valid existing period', 5, '01:00', '03:00', 'invalid'],
			'invalid days of week (0)' => [false, 'Sorry, the recurring time period days of week are invalid', 6, '02:00', '03:00', null, 0],
			'invalid days of week (> 127)' => [false, 'Sorry, the recurring time period days of week are invalid', 7, '10:00', '12:00', null, 128],
			'campaign does not exists' => [false, 'Campaign id does not exist', 9999 * 9999],
			// recurring timings
			'Campaign exempt any recurring timings' => [
				true,
				false,
				['classification' => 'exempt'],
				'01:00',
				'23:00',
			],
			'ACMA phone campaign telemarketing wrong timings' => [
				false,
				'Specific & recurring time periods for AU telemarketing campaigns must match the following rules',
				['classification' => 'telemarketing'],
				'01:00',
				'23:00',
			],
			'ACMA phone campaign research wrong timings' => [
				false,
				'Specific & recurring time periods for AU research campaigns must match the following rules',
				['classification' => 'research'],
				'01:00',
				'23:00',
			],
		];
	}

	/**
	 * @dataProvider api_restrictions_time_recurring_add_data
	 * @group api_restrictions_time_recurring_add
	 * @param boolean $expected
	 * @param mixed   $error
	 * @param mixed   $campaignid
	 * @param mixed   $starttime
	 * @param mixed   $endtime
	 * @param mixed   $periodid
	 * @param integer $daysOfWeek
	 * @return void
	 */
	public function test_api_restrictions_time_recurring_add($expected, $error, $campaignid, $starttime = '09:00', $endtime = '15:00', $periodid = null, $daysOfWeek = 31) {

		// if $campaignid is array, then create a campaign with specific settings
		if (is_array($campaignid)) {
			$settings = $campaignid;
			$campaignid = api_campaigns_add(uniqid('recurring'), 'phone');
			foreach ($settings as $name => $value) {
				$this->assertTrue(
					api_campaigns_setting_set($campaignid, $name, $value)
				);
			}
		}

		$id = api_restrictions_time_recurring_add($campaignid, $starttime, $endtime, $periodid, $daysOfWeek);
		if ($expected) {
			$this->assertGreaterThan(0, $id);
		} else {
			$this->assertFalse($id);
		}

		$actualError = api_error_printiferror(['return' => true]);

		if ($error) {
			$this->assertRegExp("/{$error}/", $actualError);
		} else {
			$this->assertFalse($error);
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_restrictions_time_specific_add_data() {
		return [
			// Invalid parameters
			'Invalid times (empty)' => [false, 'Sorry, the end time must be after the start time', 1, '', ''],
			'Invalid times (null)' => [false, 'Sorry, the end time must be after the start time', 2, null, null],
			'Invalid times (0)' => [false, 'Sorry, the end time must be after the start time', 3, 0, 0],
			'Invalid times (string)' => [false, 'Sorry, the end time must be after the start time', 4, 'invalid', 'invalid'],
			'Invalid times (same values)' => [false, 'Sorry, the end time must be after the start time', 4, 10, 10],
			'Invalid campaign id' => [false, 'Sorry, that is not a valid campaign', 'invalid', 1000, 1001],
			'Invalid campaign id' => [false, 'Sorry, that is not a valid existing period', 5, 1000, 1001, 'invalid'],
			// specific timings
			'Campaign exempt any recurring timings' => [
				true,
				false,
				['classification' => 'exempt'],
				strtotime('today 11AM'),
				strtotime('today 2PM'),
			],
			'Campaign exempt recurring timings different date' => [
				false,
				'Start & end dates are not on the same calendar day',
				['classification' => 'exempt'],
				strtotime('2 weeks ago'),
				strtotime('today 2PM'),
			],
			'ACMA Campaign telemarketing recurring timings not same calendar day' => [
				false,
				'Start & end dates are not on the same calendar day',
				['classification' => 'telemarketing'],
				strtotime('2 weeks ago'),
				strtotime('today 2PM'),
			],
			'ACMA Campaign telemarketing wrong time periods' => [
				false,
				'Specific & recurring time periods for AU telemarketing campaigns must match the following rules',
				['classification' => 'telemarketing'],
				strtotime('today 2AM'),
				strtotime('today 2PM'),
			],
			'ACMA Campaign research recurring timings not same calendar day' => [
				false,
				'Start & end dates are not on the same calendar day',
				['classification' => 'research'],
				strtotime('3 weeks ago'),
				strtotime('today 2PM'),
			],
			'ACMA Campaign research wrong time periods' => [
				false,
				'Specific & recurring time periods for AU research campaigns must match the following rules',
				['classification' => 'research'],
				strtotime('today 9AM'),
				strtotime('today 9PM'),
			],
			'ACMA Campaign research boundaries time periods - telemarketing weekdays' => [
				true,
				false,
				['classification' => 'telemarketing'],
				strtotime('29th December 2020 9AM'),
				strtotime('29th December 2020 8PM'),
			],
			'ACMA Campaign research boundaries time periods - telemarketing saturday' => [
				true,
				false,
				['classification' => 'telemarketing'],
				strtotime('18th April 2020 9AM'), //Saturday
				strtotime('18th April 2020 5PM'), //Saturday
			],
			'ACMA Campaign research boundaries time periods - research weekdays' => [
				true,
				false,
				['classification' => 'research'],
				strtotime('last wednesday 9AM'),
				strtotime('last wednesday 8:30PM'),
			],
			'ACMA Campaign research boundaries time periods - research weekends (saturday)' => [
				true,
				false,
				['classification' => 'research'],
				strtotime('18th April 2020 9AM'), //Saturday
				strtotime('18th April 2020 5PM'), //Saturday
			],
			'ACMA Campaign research boundaries time periods - research weekends (sunday)' => [
				true,
				false,
				['classification' => 'research'],
				strtotime('18-01-2020 9AM'),
				strtotime('18-01-2020 5PM'),
			],
		];
	}

	/**
	 * @dataProvider api_restrictions_time_specific_add_data
	 * @group api_restrictions_time_specific_add
	 * @param boolean $expected
	 * @param mixed   $error
	 * @param mixed   $campaignid
	 * @param mixed   $starttime
	 * @param mixed   $endtime
	 * @param mixed   $periodid
	 * @return void
	 */
	public function test_api_restrictions_time_specific_add($expected, $error, $campaignid, $starttime, $endtime, $periodid = null) {
		// if $campaignid is array, then create a campaign with specific settings
		if (is_array($campaignid)) {
			$settings = $campaignid;
			$campaignid = api_campaigns_add(uniqid('recurring'), 'phone');
			foreach ($settings as $name => $value) {
				$this->assertTrue(
					api_campaigns_setting_set($campaignid, $name, $value)
				);
			}
		}

		$id = api_restrictions_time_specific_add($campaignid, $starttime, $endtime, $periodid);
		if ($expected) {
			$this->assertGreaterThan(0, $id);
		} else {
			$this->assertFalse($id);
		}

		$actualError = api_error_printiferror(['return' => true]);

		if ($error) {
			$this->assertRegExp("/{$error}/", $actualError);
		} else {
			$this->assertFalse($error);
		}
	}

	/**
	 * @return void
	 */
	public function test_api_restrictions_donotcontact_lists() {

		$group1 = $this->create_new_group();
		$group2 = $this->create_new_group();
		$group3 = $this->create_new_group();

		$id = api_restrictions_donotcontact_addlist(uniqid(), $group1);
		$id1 = api_restrictions_donotcontact_addlist(uniqid(), $group2);
		$id2 = api_restrictions_donotcontact_addlist(uniqid(), $group3);

		$result1 = api_restrictions_donotcontact_lists();

		$this->assertCount(3, api_restrictions_donotcontact_lists());
		$this->assertCount(1, api_restrictions_donotcontact_lists(false, [$group1]));
		$this->assertCount(2, api_restrictions_donotcontact_lists(false, [$group1, $group3]));
		$this->assertCount(2, api_restrictions_donotcontact_lists(false, [$group2, $group3]));
		$this->assertCount(0, api_restrictions_donotcontact_lists(false, [$group1, 'fail']));
		$this->assertCount(0, api_restrictions_donotcontact_lists(false, [$group1, 'false']));

		api_restrictions_donotcontact_remove_list($id);
		api_restrictions_donotcontact_remove_list($id1);
		api_restrictions_donotcontact_remove_list($id2);
	}

	/**
	 * @return void
	 */
	public function test_api_restrictions_donotcontact_user_campaign_hasaccess() {

		$group1 = $this->create_new_group();
		$group2 = $this->create_new_group();
		$group3 = $this->create_new_group();

		$user1 = $this->create_new_user(null, $group1);
		$user2 = $this->create_new_user(null, $group2);
		$user3 = $this->create_new_user(null, $group3);
		api_users_setting_set($user3, "usergroups", serialize([$group1, $group2]));

		$id = api_restrictions_donotcontact_addlist(uniqid(), $group1);
		$id1 = api_restrictions_donotcontact_addlist(uniqid(), $group2);
		$id2 = api_restrictions_donotcontact_addlist(uniqid(), $group3);

		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [1, "donotcontactdestination"], 'return' => $id],
				['params' => [1, "donotcontact"], 'return' => serialize([$id])],
				['params' => [2, "donotcontactdestination"], 'return' => $id1],
				['params' => [2, "donotcontact"], 'return' => serialize([])],
				['params' => [3, "donotcontactdestination"], 'return' => $id2],
				['params' => [3, "donotcontact"], 'return' => serialize([$id])],

			],
			false
		);

		$this->mock_function_value("api_campaigns_checkidexists", true);

		$this->assertTrue(api_restrictions_donotcontact_user_campaign_hasaccess($user1, 1));
		$this->assertFalse(api_restrictions_donotcontact_user_campaign_hasaccess($user2, 1));
		$this->assertTrue(api_restrictions_donotcontact_user_campaign_hasaccess($user2, 2));
		$this->assertFalse(api_restrictions_donotcontact_user_campaign_hasaccess($user1, 2));
		$this->assertTrue(api_restrictions_donotcontact_user_campaign_hasaccess($user3, 1));
		$this->assertTrue(api_restrictions_donotcontact_user_campaign_hasaccess($user3, 2));
		$this->assertTrue(api_restrictions_donotcontact_user_campaign_hasaccess($user3, 3));

		api_restrictions_donotcontact_remove_list($id);
		api_restrictions_donotcontact_remove_list($id1);
		api_restrictions_donotcontact_remove_list($id2);
	}
}
