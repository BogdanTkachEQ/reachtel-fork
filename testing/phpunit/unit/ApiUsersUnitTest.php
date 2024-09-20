<?php
/**
 * ApiUsersUnitTest
 * Unit test for api_users.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Api Users Unit Test class
 */
class ApiUsersUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_email_filetype_data() {
		return [
			// Failures user does not exists
			[false, '', '', [], false],

			// Failures version
			[false, '', '', ['version' => 3]],
			[false, '', '', ['version' => 5]],

			// Failures empty passwords
			[false, false, 'not-empty'],
			[false, null, 'not-empty'],
			[false, '', 'not-empty'],
			[false, 'not-empty', false],
			[false, 'not-empty', null],
			[false, 'not-empty', ''],

			// rules failures
			[false, 'pwd1', 'pwd2'], // rule different passwords
			[false, '<8chars', '<8chars'], // rule length < 8
			[false, str_repeat('a', 129), str_repeat('a', 129)], // rule length > 128
			[false, 'Firstname.Lastname', 'Firstname.Lastname'], // rule username in password
			[false, '1Firstname.Lastname', '1Firstname.Lastname'], // rule username in password
			[false, 'n0 upper case char', 'n0 upper case char'], // rule 1 uppercase character
			[false, 'N0 LOWERCASE CHAR', 'N0 LOWERCASE CHAR'], // rule 1 lowercase character
			[false, 'No Digits chars', 'No Digits chars'], // rule 1 digits

			// existing password failures
			[false, 'Firstname9Lastname', 'Firstname9Lastname', [], true, true],

			// Success
			[true, 'Firstname9Lastname', 'Firstname9Lastname'], // username using regular expression
			[true, 'MyNewPass0rd', 'MyNewPass0rd'],
			[true, 'MyNewPass0rd', 'MyNewPass0rd', ['version' => 5], true, false, time()],
		];
	}

	/**
	 * @dataProvider api_email_filetype_data
	 * @param boolean $expected_value
	 * @param mixed   $password
	 * @param mixed   $passwordagain
	 * @param array   $options
	 * @param boolean $user_exists
	 * @param boolean $existing_password
	 * @param integer $password_reset_sent
	 * @return void
	 */
	public function test_api_users_password_reset($expected_value, $password, $passwordagain, array $options = [], $user_exists = true, $existing_password = false, $password_reset_sent = 1442100000) {
		$this->mock_function_value('api_users_checkidexists', $user_exists);
		$this->mock_function_value('api_misc_audit', null);
		$this->mock_function_value('api_users_setting_set', null);
		$this->mock_function_value('api_users_setting_increment', null);
		$this->mock_function_value('api_users_setting_delete_single', null);
		$this->mock_function_value('password_verify', $existing_password);
		$this->mock_function_param_value(
			'api_users_setting_getsingle',
			[
				['params' => [1, 'passwordresetcount'], 'return' => 5],
				['params' => [1, 'passwordresetsent'], 'return' => $password_reset_sent],
				['params' => [1, 'saltedpassword'], 'return' => $existing_password]
			],
			'Firstname.Lastname'
		);
		$this->assertSameEquals($expected_value, api_users_password_reset(1, $password, $passwordagain, $options));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_users_store_session_id_data_provider() {
		return [
			'skip user id check' => [1, '234adfsr3432', true, false, true],
			'do not skip user id check and user id does not exist' => [1, '435sadfgd545', false, false, false],
			'do not skip user id check and user id exists' => [1, '5t6sdfgt345', false, true, true]
		];
	}

	/**
	 * @group api_users_store_session_id
	 * @dataProvider api_users_store_session_id_data_provider
	 * @param integer $user_id
	 * @param string  $session_id
	 * @param boolean $skip_user_id_check
	 * @param boolean $user_id_check_return
	 * @param boolean $expected_return
	 * @return void
	 */
	public function test_api_users_store_session_id(
		$user_id,
		$session_id,
		$skip_user_id_check,
		$user_id_check_return,
		$expected_return
	) {
		if ($skip_user_id_check || (!$skip_user_id_check && $user_id_check_return)) {
			$this->remove_mocked_functions('api_users_setting_set');
			$this->mock_function_param_value(
				'api_users_setting_set',
				[
					['params' => [$user_id, 'sessionid', $session_id], 'return' => $expected_return]
				],
				$expected_return
			);
		}

		if (!$skip_user_id_check) {
			$this->remove_mocked_functions('api_users_checkidexists');
			$this->mock_function_param_value(
				'api_users_checkidexists',
				[
					['params' => [$user_id], 'return' => $user_id_check_return]
				],
				false
			);
		}

		$this->assertSameEquals($expected_return, api_users_store_session_id($user_id, $session_id, $skip_user_id_check));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_users_destroy_session_id_data_provider() {
		return [
			'skip user id check' => [1, true, false, true],
			'do not skip user id check and user id does not exist' => [1, false, false, false],
			'do not skip user id check and user id exists' => [1, false, true, true]
		];
	}

	/**
	 * @group api_users_destroy_session_id
	 * @dataProvider api_users_destroy_session_id_data_provider
	 * @param integer $user_id
	 * @param boolean $skip_user_id_check
	 * @param boolean $user_id_check_return
	 * @param boolean $expected_return
	 * @return void
	 */
	public function test_api_users_destroy_session_id(
		$user_id,
		$skip_user_id_check,
		$user_id_check_return,
		$expected_return
	) {
		if ($skip_user_id_check || (!$skip_user_id_check && $user_id_check_return)) {
			$this->remove_mocked_functions('api_users_setting_set');
			$this->mock_function_param_value(
				'api_users_setting_delete_single',
				[
					['params' => [$user_id, 'sessionid'], 'return' => $expected_return]
				],
				$expected_return
			);
		}

		if (!$skip_user_id_check) {
			$this->remove_mocked_functions('api_users_checkidexists');
			$this->mock_function_param_value(
				'api_users_checkidexists',
				[
					['params' => [$user_id], 'return' => $user_id_check_return]
				],
				false
			);
		}

		$this->assertSameEquals($expected_return, api_users_destroy_session_id($user_id, $skip_user_id_check));
	}

	/**
	 * @group api_users_fetch_session_id
	 * @return void
	 */
	public function test_api_users_fetch_session_id() {
		$user_id = 234;
		$expected_return = '4gf5345g565';
		$this->remove_mocked_functions('api_users_setting_getsingle');
		$this->mock_function_param_value(
			'api_users_setting_getsingle',
			[
				['params' => [$user_id, 'sessionid'], 'return' => $expected_return]
			],
			false
		);

		$this->assertSameEquals($expected_return, api_users_fetch_session_id($user_id));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_users_has_password_expired_data_provider() {
		return [
			// feature disabled
			'password should reset but feature is disabled' => [false, 20, true],
			'password should not reset but feature is disabled' => [false, 11, true],
			// feature enabled/disabled for selected user (threshod = 180 days)
			'password should reset but feature disabled for this user' => [false, 30, time() - (86400 * 180)],
			'password should reset and feature enabled for user' => [true, 20, time() - (86400 * 180)],
			'password should reset and feature disabled for user without passwordresettime' => [false, 1, time() - (86400 * 180)],
			'password should reset and feature disabled for user with empty passwordresettime' => [false, 2, time() - (86400 * 180)],
			'password should not reset and feature enabled for user' => [false, 10, time() - (86400 * 180)],
			// setting not set so force password reset
			'User passwordresettime settings is false' => [true, 9999],
			'User passwordresettime settings is null' => [true, 1],
			'User passwordresettime settings is empty' => [true, 2],
			// password not expired
			'User password changed 10 seconds ago' => [false, 10],
			'User password changed 1 day ago' => [false, 11],
			'User password changed 89 days ago' => [false, 11],
			// password expired
			'User password changed 91 days ago' => [true, 20],
		];
	}

	/**
	 * @group api_users_has_password_expired
	 * @dataProvider api_users_has_password_expired_data_provider
	 * @param boolean $expected
	 * @param mixed   $user_id
	 * @param boolean $disabled
	 * @return void
	 */
	public function test_api_users_has_password_expired($expected, $user_id, $disabled = false) {
		$secondsInADay = 86400;

		$this->mock_function_param_value(
			'api_users_setting_getsingle',
			[
				['params' => [1], 'return' => null],
				['params' => [2], 'return' => ''],
				['params' => [10], 'return' => (time() - 10)],
				['params' => [11], 'return' => (time() - ($secondsInADay))],
				['params' => [12], 'return' => (time() - ($secondsInADay * 89))],
				['params' => [20], 'return' => (time() - ($secondsInADay * 91))],
				['params' => [30], 'return' => (time() - ($secondsInADay * 181))],
			],
			false
		);

		$this->mock_function_param_value(
			'defined',
			[
				['params' => 'USER_LOGIN_PASSWORD_EXPIRATION_DISABLED', 'return' => $disabled],
			],
			false
		);

		$this->mock_function_param_value(
			'constant',
			[
				['params' => 'USER_LOGIN_PASSWORD_EXPIRATION', 'return' => 90],
				['params' => 'USER_LOGIN_PASSWORD_EXPIRATION_DISABLED', 'return' => $disabled],
			],
			false
		);

		$this->assertSameEquals(
			$expected,
			api_users_has_password_expired($user_id)
		);

		$this->remove_mocked_functions();
	}

	/**
	 * @return array
	 */
	public function is_tech_admin_data_provider() {
		return [
			'constant TECHNICAL_ADMIN_USERIDS is not defined' => [false, 123, false],
			'user id not in TECHNICAL_ADMIN_USERIDS' => [[1, 2, 4], 123, false],
			'user id in TECHNICAL_ADMIN_USERIDS' => [[1, 123, 23, 4], 123, true]
		];
	}

	/**
	 * @group api_users_is_technical_admin
	 * @dataProvider is_tech_admin_data_provider
	 * @param mixed   $technical_admin_user_ids
	 * @param integer $userid
	 * @param boolean $expected
	 * @return void
	 */
	public function test_api_users_is_technical_admin($technical_admin_user_ids, $userid, $expected) {
		$this->mock_function_param_value(
			'defined',
			[
				['params' => 'TECHNICAL_ADMIN_USERIDS', 'return' => $technical_admin_user_ids ? true : false],
			],
			false
		);

		$this->mock_function_param_value(
			'constant',
			[
				['params' => 'TECHNICAL_ADMIN_USERIDS', 'return' => $technical_admin_user_ids]
			],
			false
		);

		$this->assertEquals($expected, api_users_is_technical_admin($userid));
		$this->remove_mocked_functions();
	}

	/**
	 * @return array
	 */
	public function apiUsersIsAdminUserDataProvider() {
		return [
			'is admin' => [true, true],
			'is not admin' => [false, false]
		];
	}

	/**
	 * @dataProvider apiUsersIsAdminUserDataProvider
	 * @param boolean $isadmin
	 * @param boolean $expected
	 * @return void
	 */
	public function testApiUsersIsAdminUser($isadmin, $expected) {
		$userid = 123;
		$this->mock_function_param_value(
			'api_security_groupaccess',
			[
				['params' => [$userid], 'return' => ['isadmin' => $isadmin]]
			],
			false
		);

		$this->assertSameEquals($expected, api_users_is_admin_user($userid));
	}
}
