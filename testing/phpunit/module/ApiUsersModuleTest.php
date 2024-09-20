<?php
/**
 * ApiUsersModuleTest
 * Module test for api_users.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use Services\User\UserTypeEnum;
use testing\module\helpers\MethodsSettingsModuleTrait;
use testing\module\helpers\MethodsTagsModuleTrait;
use testing\module\helpers\UserModuleHelper;

/**
 * Api Users Module Test
 */
class ApiUsersModuleTest extends AbstractPhpunitModuleTest
{
	use MethodsSettingsModuleTrait;
	use MethodsTagsModuleTrait;
	use UserModuleHelper;

	/**
	 * Type value
	 */
	private static $type;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		self::$type = self::get_user_type();
	}

	/**
	 * @param string $username
	 * @return void
	 */
	public function test_api_users_add($username = null) {
		$expected_id = $this->get_expected_next_user_id();
		$username = $username ? : uniqid('test');

		// Assert new user id
		$this->assertEquals($expected_id, api_users_add($username));
		// Assert next user id
		$this->assertEquals($expected_id + 1, $this->get_expected_next_user_id());
		// Assert new user fields
		$this->assert_user($expected_id, $username);
	}

	/**
	 * @return void
	 */
	public function test_api_users_add_duplicate() {
		// make sure we have a user in db
		$user_id = $this->create_new_user();
		$expected_user_duplcate_id = $user_id + 1;
		$username = uniqid('dup');

		// Assert new user id
		$this->assertEquals($expected_user_duplcate_id, api_users_add($username, $user_id));
		// Assert new user fields
		$this->assert_user($expected_user_duplcate_id, $username);
	}

	/**
	 * @return void
	 */
	public function test_api_users_duplicate_inaccessible_user_should_not_work() {
		$group_id1 = $this->create_new_group();
		$group_id2 = $this->create_new_group();

		$user_id1 = $this->create_new_user(null, $group_id1);
		$user_id2 = $this->create_new_user(null, $group_id2);

		$username = uniqid('test');
		$this->assertFalse(api_users_checknameexists($username));
		$this->assertFalse(api_users_add($username, $user_id1, $user_id2));
		$this->assertFalse(api_users_checknameexists($username));
	}

	/**
	 * @return void
	 */
	public function test_api_users_duplicate_accessible_user() {
		$group_id1 = $this->create_new_group();

		$user_id1 = $this->create_new_user(null, $group_id1);
		$user_id2 = $this->create_new_user(null, $group_id1);

		$username = uniqid('test');
		$this->assertFalse(api_users_checknameexists($username));
		$expected_id = $this->get_expected_next_user_id();
		$this->assertSameEquals($expected_id, api_users_add($username, $user_id1, $user_id2));
		$this->assertNotFalse(api_users_checknameexists($username));
	}

	/**
	 * @return void
	 */
	public function test_api_users_admin_can_duplicate_any_users() {
		$admin_user = $this->get_default_admin_id();
		$group_id = $this->create_new_group();
		$user_id = $this->create_new_user(null, $group_id);

		$username = uniqid('test');
		$this->assertFalse(api_users_checknameexists($username));
		$expected_id = $this->get_expected_next_user_id();
		$this->assertSameEquals($expected_id, api_users_add($username, $user_id, $admin_user));
		$this->assertNotFalse(api_users_checknameexists($username));
	}

	/**
	 * @return void
	 */
	public function test_api_users_add_should_copy_groupowner() {
		$group_id = $this->create_new_group();
		$user_id = $this->create_new_user(null, $group_id);
		$username = uniqid('test');
		$this->assertFalse(api_users_checknameexists($username));
		$expected_id = $this->get_expected_next_user_id();
		$this->assertSameEquals($expected_id, api_users_add($username, null, $user_id));
		$this->assertNotFalse(api_users_checknameexists($username));
		$this->assertSameEquals($group_id, (int)api_users_setting_getsingle($expected_id, 'groupowner'));
	}

	/**
	 * @return void
	 */
	public function test_api_users_add_should_not_copy_groupowner_when_created_by_admin() {
		$admin_user = $this->get_default_admin_id();
		$username = uniqid('test');
		$this->assertFalse(api_users_checknameexists($username));
		$expected_id = $this->get_expected_next_user_id();
		$this->assertSameEquals($expected_id, api_users_add($username, null, $admin_user));
		$this->assertNotFalse(api_users_checknameexists($username));
		$this->assertFalse(api_users_setting_getsingle($expected_id, 'groupowner'));
	}

	/**
	 * @return void
	 */
	public function test_api_users_checknameexists() {
		$username = uniqid('test');

		$this->assertFalse(api_users_checknameexists($username));
		// make sure we have a user in db
		$this->create_new_user($username);
		$this->assertInternalType('string', api_users_checknameexists($username));
	}

	/**
	 * @return void
	 */
	public function test_api_users_checkidexists() {
		$expected_id = $this->get_expected_next_user_id();

		$this->assertFalse(api_users_checkidexists($expected_id));
		// Create a new user in db
		$user_id = $this->create_new_user();
		$this->assertEquals($expected_id, $user_id);
		$this->assertTrue(api_users_checkidexists($user_id));
	}

	/**
	 * @return void
	 */
	public function test_api_users_nametoid() {
		$username = uniqid('test');

		$this->assertFalse(api_users_nametoid($username));
		// Create a new user in db
		$user_id = $this->create_new_user($username);
		$this->assertEquals($user_id, api_users_nametoid($username));
	}

	/**
	 * @return void
	 */
	public function test_api_users_idtoname() {
		$name = uniqid((new \DateTime())->format('dmyhis'));
		$id = $this->create_new_user($name);
		$this->assertSameEquals($name, api_users_idtoname($id));
	}

	/**
	 * @return void
	 */
	public function test_api_users_gettimezone() {
		$expected_id = $this->get_expected_next_user_id();

		$this->assertSameEquals('Australia/Brisbane', api_users_gettimezone($expected_id));
		// Create a new user in db
		$user_id = $this->create_new_user();
		$this->assertEquals($expected_id, $user_id);
		$this->assertSameEquals('Australia/Brisbane', api_users_gettimezone($user_id));

		// Assert changing timezone to Sydney
		$this->assertTrue(api_keystore_set(self::get_user_type(), $user_id, 'timezone', 'Australia/Sydney'));
		$this->assertSameEquals('Australia/Sydney', api_users_gettimezone($user_id));
	}

	/**
	 * @return void
	 */
	public function test_api_users_getusertype() {
		$expected_id = $this->get_expected_next_user_id();

		$user_id = $this->create_new_user(null, null, UserTypeEnum::ADMIN());
		$this->assertEquals($expected_id, $user_id);
		$this->assertEquals(UserTypeEnum::ADMIN(), api_users_getusertype($user_id));

		// Assert changing timezone to Sydney

		$this->assertTrue(api_keystore_set(self::get_user_type(), $user_id, 'usertype', UserTypeEnum::CLIENT()->getValue()));
		$this->assertSameEquals(UserTypeEnum::CLIENT(), api_users_getusertype($user_id));

		api_keystore_delete(self::get_user_type(), $user_id, 'usertype');
		$this->assertSameEquals(null, api_users_getusertype($user_id));

		$user_id = $this->create_new_user(null, null);
		$this->assertSameEquals(UserTypeEnum::getDefault(), api_users_getusertype($user_id));
	}

	/**
	 * @return void
	 */
	public function test_api_users_findkey() {
		$username = uniqid('test');

		$this->assertFalse(api_users_findkey('username', $username));

		// Create a new user in db
		$this->create_new_user($username);
		$this->assertInternalType('string', api_users_findkey('username', $username));
	}

	/**
	 * @return void
	 */
	public function test_api_users_delete() {
		$expected_id = $this->get_expected_next_user_id();

		$this->assertFalse(api_users_delete($expected_id));

		// Create a new user in db
		$user_id = $this->create_new_user();
		$this->assertEquals($expected_id, $user_id);

		$this->assertTrue(api_users_delete($user_id));
		$user_key_stores = $this->get_table_rows(
			'key_store',
			['type' => self::get_user_type(), 'id' => $user_id]
		);
		$this->assertEmpty($user_key_stores, "Failed asserting that user id#$user_id is deleted.");
	}

	/**
	 * @return void
	 */
	public function test_api_users_delete_byuser() {
		$username = uniqid('test');

		$this->assertFalse(api_users_delete_byuser($username));

		// Create a new user in db
		$user_id = $this->create_new_user($username);

		$this->assertTrue(api_users_delete_byuser($username));
		$user_key_stores = $this->get_table_rows(
			'key_store',
			['type' => self::get_user_type(), 'id' => $user_id]
		);
		$this->assertEmpty($user_key_stores, "Failed asserting that user with username '$username' is deleted.");
	}

	/**
	 * @return void
	 */
	public function test_api_users_listall() {
		// Create a new user in db
		$this->create_new_user();
		$users = api_users_listall();
		$this->assertInternalType('array', $users);

		// Assert each user is valid
		foreach ($users as $user_id => $user) {
			$this->assertTrue(api_users_checkidexists($user_id));
		}
	}

	/**
	 * @return void
	 */
	public function test_api_users_listall_short() {
		// Create a new user in db
		$this->create_new_user();
		$options = array("short" => true);
		$users = api_users_listall($options);
		$this->assertInternalType('array', $users);

		// Assert each user as default keys
		foreach ($users as $user_id) {
			$this->assertInternalType('integer', $user_id);
			$this->assertTrue(api_users_checkidexists($user_id));
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_users_ratelimit_check_data() {
		return [
			[
				false,
				'test1',
				[
					'test1.ratelimits' => false,
					'test1.limit' => '60',
					'apirequest.get.limit' => '60'
				],
			],
			[
				false,
				'test2',
				[
					'test2.ratelimits' => false,
					'test2.limit' => '60',
					'apirequest.get.limit' => '60'
				],
				[
					'test2.lastrequest' => function() { return strtotime("-1 minutes"); },
					'test2.throttleperiod' => 12
				]
			],
			[
				true,
				'test3',
				[
					'test3.ratelimits' => '11',
					'test3.limit' => '60',
					'apirequest.get.limit' => '60'
				],
				[
					'test3.lastrequest' => function() { return microtime(true); },
					'test3.throttleperiod' => 12,
					'test3.ratelimits' => 10
				]
			],
			[
				true,
				'test4',
				[
					'test4.ratelimits' => '1',
					'test4.limit' => '60',
					'apirequest.get.limit' => '60'
				],
				[
					'test4.lastrequest' => function() { return microtime(true); },
					'test4.throttleperiod' => 12
				]
			],
			[
				false,
				'test5',
				[
					'test5.ratelimits' => false,
					'test5.limit' => '300',
					'apirequest.get.limit' => null
				],
				[
					'apirequest.get.limit' => null // will use API_RATE_LIMIT_PERMIN = 300
				]
			],
			[
				false,
				'test6',
				[
					'test6.ratelimits' => false,
					'test6.limit' => '30',
					'apirequest.get.limit' => '60'
				],
				[
					'test6.limit' => 30,
				]
			]
		];
	}

	/**
	 * @dataProvider api_users_ratelimit_check_data
	 * @param string $expected_value
	 * @param string $item
	 * @param array  $expected_settings
	 * @param array  $overrides
	 * @return void
	 */
	public function test_api_users_ratelimit_check($expected_value, $item = 'api', array $expected_settings = [], array $overrides = []) {
		// Create a new user in db
		$user_id = $this->create_new_user();

		foreach ($overrides as $setting => $value) {
			if (is_callable($value)) {
				$value = $value();
			}
			$this->assertTrue(api_users_setting_set($user_id, $setting, $value), "Failed overriding setting '$setting' = $value.");
		}

		$this->assertSameEquals($expected_value, api_users_ratelimit_check($user_id, $item));

		// assert settings
		foreach ($expected_settings as $setting => $expected_settings_value) {
			$this->assertSameEquals($expected_settings_value, api_users_setting_getsingle($user_id, $setting), "Failure asserting setting '$setting'");
		}
	}

	/**
	 * @return void
	 */
	public function test_api_users_password_resetrequest() {
		$expected_id = $this->get_expected_next_user_id();

		$this->assertFalse(api_users_password_resetrequest($expected_id));

		// Create a new user in db
		$user_id = $this->create_new_user();

		// user has no email
		$this->assertFalse(api_users_password_resetrequest($user_id));

		$this->assertTrue(api_users_setting_set($user_id, 'emailaddress', 'test@aqswde.fr'));
		$this->assertTrue(api_users_password_resetrequest($user_id));
	}

	/**
	 * @return void
	 */
	public function test_api_users_password_reset() {
		$expected_id = $this->get_expected_next_user_id();

		$this->assertFalse(api_users_password_reset($expected_id, 'myNewP@ssw0rd', 'myNewP@ssw0rd'));

		// Create a new user in db
		$user_id = $this->create_new_user();

		$password_reset_count = api_users_setting_getsingle($user_id, 'passwordresetcount');

		// reset pwd 1st time
		$this->assertTrue(api_users_password_reset($user_id, 'myNewP@ssw0rd', 'myNewP@ssw0rd'));
		$this->assertEquals(1, api_users_setting_getsingle($user_id, 'passwordresetcount'));

		// reset another pwd
		$this->assertTrue(api_users_password_reset($user_id, 'the2ndPwd', 'the2ndPwd'));
		$this->assertEquals(2, api_users_setting_getsingle($user_id, 'passwordresetcount'));

		// reset same pwd should fail
		$this->assertFalse(api_users_password_reset($user_id, 'the2ndPwd', 'the2ndPwd'));
		$this->assertEquals(2, api_users_setting_getsingle($user_id, 'passwordresetcount'));

		// passwordresetcount
		$this->assertFalse(api_users_password_reset($user_id, 'myNewP@ssw0rd', 'myNewP@ssw0rd', ['version' => '5']));
		$this->assertEquals(2, api_users_setting_getsingle($user_id, 'passwordresetcount'));

		// passwordresetsent
		$this->assertTrue(api_users_setting_set($user_id, 'passwordresetsent', time() - 7200));
		$this->assertFalse(api_users_password_reset($user_id, 'myNewP@ssw0rd', 'myNewP@ssw0rd', ['version' => '2']));
		$this->assertEquals(2, api_users_setting_getsingle($user_id, 'passwordresetcount'));

		// version but not passwordresetcount and not passwordresetsent
		$this->assertTrue(api_users_setting_set($user_id, 'passwordresetsent', time()));
		$this->assertTrue(api_users_password_reset($user_id, 'myNewP@ssw0rd', 'myNewP@ssw0rd', ['version' => '2']));
		$this->assertEquals(3, api_users_setting_getsingle($user_id, 'passwordresetcount'));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_users_isactive_data() {
		return [
			// Inactive - default
			[false],
			// Inactive - initial
			[false, USER_STATUS_INITIAL],
			// Inactive - initial legacy
			[false, USER_STATUS_INITIAL_LEGACY],
			// Inactive - disabled
			[false, USER_STATUS_DISABLED],
			// Inactive - disabled legacy
			[false, USER_STATUS_DISABLED_LEGACY],
			// Inactive - inactive
			[false, USER_STATUS_INACTIVE],
			// Inactive - locked
			[false, USER_STATUS_LOCKED],
			// Active
			[true, USER_STATUS_ACTIVE],
		];
	}

	/**
	 * @group api_users_isactive
	 * @dataProvider api_users_isactive_data
	 * @param boolean $expected_value
	 * @param integer $status
	 * @return void
	 */
	public function test_api_users_isactive($expected_value, $status = null) {
		// Create a new user in db
		$userid = $this->create_new_user();
		if ($status) {
			api_users_setting_set($userid, 'status', $status);
		}
		$this->assertEquals($expected_value, api_users_isactive($userid));
	}

	/**
	 * @return void
	 */
	public function test_api_users_list_all_by_groupowner() {
		$groupid1 = $this->create_new_group();
		$groupid2 = $this->create_new_group();
		$userid1 = $this->create_new_user(null, $groupid1);
		$userid2 = $this->create_new_user(null, $groupid2);
		$userid3 = $this->create_new_user(null, $groupid1);

		$this->assertSameEquals([$userid3, $userid1], api_users_list_all_by_groupowner($groupid1));
		$this->assertSameEquals([$userid2], api_users_list_all_by_groupowner($groupid2));
	}

	/**
	 * @return void
	 */
	public function test_api_users_list_by_groupowners_last_login() {
		$groupid1 = $this->create_new_group();
		$userid1 = $this->create_new_user(null, $groupid1);
		$userid2 = $this->create_new_user(null, $groupid1);
		$userid3 = $this->create_new_user(null, $groupid1);

		// Setup users last login, one recent, one mid age, and one old
		api_users_setting_set($userid1, "lastauth", strtotime("-1 day"));
		api_users_setting_set($userid2, "lastauth", strtotime("-10 days"));
		api_users_setting_set($userid3, "lastauth", strtotime("-100 days"));

		// Check various ages of users last time stamp login
		$recent = api_users_list_by_groupowners_last_login([$groupid1], new \DateTime("-2 days"));
		$mid = api_users_list_by_groupowners_last_login([$groupid1], new \DateTime("-12 days"));
		$old = api_users_list_by_groupowners_last_login([$groupid1], new \DateTime("-120 days"));

		$this->assertEmpty(array_diff([$userid1], array_keys($recent)));
		$this->assertCount(2, array_diff([$userid2, $userid3], array_keys($recent)));

		$this->assertEmpty(array_diff([$userid1, $userid2], array_keys($mid)));
		$this->assertCount(1, array_diff([$userid3], array_keys($mid)));

		$this->assertEmpty(array_diff([$userid1, $userid2, $userid3], array_keys($old)));
	}

	/**
	 * @return void
	 */
	public function test_api_users_list_by_groupowners_last_login_is_group_safe() {
		$groupid1 = $this->create_new_group();
		$groupid2 = $this->create_new_group();
		$userid1 = $this->create_new_user(null, $groupid1);
		$userid2 = $this->create_new_user(null, $groupid2);
		$userid3 = $this->create_new_user(null, $groupid1);

		// Setup users last login, one recent, one mid age, and one old
		api_users_setting_set($userid1, "lastauth", strtotime("-1 day"));
		api_users_setting_set($userid2, "lastauth", strtotime("-1 days"));
		api_users_setting_set($userid3, "lastauth", strtotime("-1 days"));

		$last_logins_group_1 = api_users_list_by_groupowners_last_login([$groupid1], new \DateTime("-20 days"));
		$last_logins_group_2 = api_users_list_by_groupowners_last_login([$groupid2], new \DateTime("-20 days"));

		$this->assertArrayNotHasKey($userid2, $last_logins_group_1);
		$this->assertArrayNotHasKey($userid1, $last_logins_group_2);
		$this->assertArrayNotHasKey($userid3, $last_logins_group_2);
	}

	/**
	 * @return void
	 */
	public function test_api_users_list_by_groupowners_last_login_with_multiple_groups() {
		$groupid1 = $this->create_new_group();
		$groupid2 = $this->create_new_group();
		$userid1 = $this->create_new_user(null, $groupid1);
		$userid2 = $this->create_new_user(null, $groupid2);

		// Setup users last login, one recent, one mid age, and one old
		api_users_setting_set($userid1, "lastauth", strtotime("-1 day"));
		api_users_setting_set($userid2, "lastauth", strtotime("-1 day"));

		$last_logins = api_users_list_by_groupowners_last_login([$groupid1, $groupid2], new \DateTime("-20 days"));
		$this->assertCount(2, $last_logins);
	}

	/**
	 * @return void
	 */
	public function test_api_users_list_by_groupowners_and_status() {
		$groupid1 = $this->create_new_group();
		$groupid2 = $this->create_new_group();
		$userid1 = $this->create_new_user(null, $groupid1);
		$userid2 = $this->create_new_user(null, $groupid2);

		api_users_setting_set($userid1, "status", USER_STATUS_ACTIVE);
		api_users_setting_set($userid2, "status", USER_STATUS_INACTIVE);

		$group1_users = api_users_list_by_groupowners_and_status([$groupid1], [USER_STATUS_ACTIVE]);
		$this->assertCount(1, $group1_users);
		$this->assertArrayHasKey($userid1, $group1_users);

		$group2_users = api_users_list_by_groupowners_and_status([$groupid2], [USER_STATUS_INACTIVE]);
		$this->assertCount(1, $group2_users);
		$this->assertArrayHasKey($userid2, $group2_users);

		$multi_group_users = api_users_list_by_groupowners_and_status([$groupid1, $groupid2], [USER_STATUS_ACTIVE, USER_STATUS_INACTIVE]);
		$this->assertCount(2, $multi_group_users);

		$group_no_users = api_users_list_by_groupowners_and_status([$groupid1], [USER_STATUS_INACTIVE]);
		$this->assertCount(0, $group_no_users);
	}

	/**
	 * @return void
	 */
	public function test_api_users_list_all_by_groupowners() {
		$groupid1 = $this->create_new_group();
		$groupid2 = $this->create_new_group();
		$groupid3 = $this->create_new_group();
		$userid1 = $this->create_new_user(null, $groupid1);
		$userid2 = $this->create_new_user(null, $groupid2);
		$userid3 = $this->create_new_user(null, $groupid1);
		$userid4 = $this->create_new_user(null, $groupid3);

		// Should be the same
		$this->assertEmpty(array_diff([$userid3, $userid2, $userid1], api_users_list_all_by_groupowners([$groupid1,$groupid2])));
		$this->assertSameEquals([$userid4], api_users_list_all_by_groupowners([$groupid3]));

		// Should not contain
		$this->assertNotEmpty(array_diff([$userid4], api_users_list_all_by_groupowners([$groupid1,$groupid2])));
	}

	/**
	 * Assert user key store fields
	 *
	 * @param integer $user_id
	 * @param string  $username
	 * @return void
	 */
	private function assert_user($user_id, $username) {
		// get new user fields
		$user_key_stores = $this->get_table_rows(
			'key_store',
			['type' => self::get_user_type(), 'id' => $user_id]
		);
		$default_expected_values = $this->get_default_expected_values(self::$type);

		// Assert number of expected fields
		$this->assertGreaterThanOrEqual(
			count($default_expected_values),
			count($user_key_stores)
		);

		$expected_user_values = array_merge(
			$this->get_default_expected_values(self::$type),
			['username' => $username]
		);

		// Assert each user key store values against the $expected_user_values
		foreach ($user_key_stores as $user_key_store) {
			$this->assertEquals($user_id, $user_key_store['id']);
			if (array_key_exists($user_key_store['item'], $expected_user_values)) {
				// Assert values from $expected_user_values
				$this->assertEquals(
					$expected_user_values[$user_key_store['item']],
					$user_key_store['value']
				);
			}
		}
	}
}
