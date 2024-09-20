<?php
/**
 * ApiSessionModuleTest
 * Module test for api_session.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use testing\module\helpers\UserModuleHelper;

/**
 * Api Session Module Test
 */
class ApiSessionModuleTest extends AbstractPhpunitModuleTest
{
	use UserModuleHelper;

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_session_checkuserid_data() {
		return [
			'USER_STATUS_CLOSED' => [USER_STATUS_CLOSED, true], // @FIXME REACHTEL-550 should be false
			'USER_STATUS_DISABLED' => [USER_STATUS_DISABLED, true], // @FIXME REACHTEL-550 should be false
			'USER_STATUS_INACTIVE' => [USER_STATUS_INACTIVE, true], // @FIXME REACHTEL-550 should be false
			'USER_STATUS_LOCKED' => [USER_STATUS_LOCKED, true], // @FIXME REACHTEL-550 should be false
			'USER_STATUS_INITIAL' => [USER_STATUS_INITIAL, true], // @FIXME REACHTEL-550 should be false
			'USER_STATUS_INITIAL_LEGACY' => [USER_STATUS_INITIAL_LEGACY, true], // @FIXME REACHTEL-550 should be false
			'USER_STATUS_DISABLED_LEGACY' => [USER_STATUS_DISABLED_LEGACY, false],
			'USER_STATUS_ACTIVE' => [USER_STATUS_ACTIVE, true],
		];
	}

	/**
	 * @group        api_session_checkuserid
	 * @dataProvider api_session_checkuserid_data
	 * @param string $status
	 * @param string $isactive
	 * @return void
	 */
	public function test_api_session_checkuserid($status, $isactive) {
		$userid = $this->create_new_user();
		$this->assertTrue(api_users_setting_set($userid, 'status', $status));

		$this->assertSameEquals(
			($isactive ? $userid : false),
			api_session_checkuserid($userid),
			"User id={$userid} status={$status}"
		);
	}

	/**
	 * @group api_session_checkauth
	 * @return void
	 */
	public function test_api_session_checkauth() {

		// failure empty username
		foreach ([null, '', false, 1] as $username) {
			api_error_purge();
			$this->assertFalse(
				api_session_checkauth($username, 'password'),
				'Empty username ' . var_export($username, true)
			);
			$this->assertEquals(
				'Invalid authentication details',
				api_error_printiferror(['return' => true]),
				'Empty username ' . var_export($username, true)
			);
		}

		// failure username does not exists
		api_error_purge();
		$username = uniqid('username_does_not_exists');
		$this->assertFalse(
			api_session_checkauth($username, 'password'),
			"Username {$username} does not exists"
		);
		$this->assertEquals(
			'Invalid authentication details',
			api_error_printiferror(['return' => true]),
			"Username {$username} does not exists"
		);

		// check statuses
		$username = uniqid('checkauth');
		$user_id = $this->create_new_user($username);

		// test suspended user can not login
		api_error_purge();
		$this->assertTrue(
			api_users_setting_set($user_id, "status", '-4')
		);
		$this->assertFalse(api_session_checkauth($username, 'pwd'));
		$this->assertEquals(
			'Sorry, your account has been suspended.',
			api_error_printiferror(['return' => true]),
			"Suspended user {$username}"
		);

		// test disabled user can not login
		api_error_purge();
		$this->assertTrue(
			api_users_setting_set($user_id, "status", '-3')
		);
		$this->assertFalse(api_session_checkauth($username, 'pwd'));
		$this->assertEquals(
			'Your account has been suspended as it has been inactive for some time. Please contact ReachTEL support at support@reachtel.com.au to reactivate your account.',
			api_error_printiferror(['return' => true]),
			"Disabled user {$username}"
		);

		// test locked user can not login
		api_error_purge();
		$this->assertTrue(
			api_users_setting_set($user_id, "status", '-2')
		);
		$this->assertFalse(api_session_checkauth($username, 'pwd'));
		$this->assertEquals(
			'Sorry, your account has been locked due to excessive incorrect password attempts.',
			api_error_printiferror(['return' => true]),
			"Locked user {$username}"
		);

		// test user needs activation can not login
		api_error_purge();
		$this->assertTrue(
			api_users_setting_set($user_id, "status", '-1')
		);
		$this->assertFalse(api_session_checkauth($username, 'pwd'));
		$this->assertEquals(
			'Sorry, your account has not yet been activated. Please contact ReachTEL support at support@reachtel.com.au to activate your account.',
			api_error_printiferror(['return' => true]),
			"Need activation user {$username}"
		);

		// test legacy user needs activation can not login
		api_error_purge();
		$this->assertTrue(
			api_users_setting_set($user_id, "status", 'disabled')
		);
		$this->assertFalse(api_session_checkauth($username, 'pwd'));
		$this->assertEquals(
			'Sorry, your account has not yet been activated. Please contact ReachTEL support at support@reachtel.com.au to activate your account.',
			api_error_printiferror(['return' => true]),
			"Need activation (legacy) user {$username}"
		);

		// test legacy disabled user can not login
		api_error_purge();
		$this->assertTrue(
			api_users_setting_set($user_id, "status", '0')
		);
		$this->assertFalse(api_session_checkauth($username, 'pwd'));
		$this->assertEquals(
			'Sorry, your account has been suspended.',
			api_error_printiferror(['return' => true]),
			"Disabled (legacy) user {$username}"
		);

		// test active user can login but wrong password
		api_error_purge();
		$this->assertTrue(
			api_users_setting_set($user_id, "status", '1')
		);
		$this->assertFalse(api_session_checkauth($username, 'pwd'));
		$this->assertEquals(
			'Invalid authentication details',
			api_error_printiferror(['return' => true]),
			"Active user {$username} wrong password"
		);

		$password = uniqid('p@a$$w0Rd');
		$this->assertTrue(
			api_users_password_reset($user_id, $password, $password),
			'Reset password'
		);

		// test active user can login but right password
		api_error_purge();
		$this->assertTrue(
			api_users_setting_set($user_id, "status", '1')
		);
		$this->assertEquals(
			$user_id,
			api_session_checkauth($username, $password)
		);
		// no errors
		$this->assertFalse(api_error_printiferror(['return' => true]));

		// test active user max attemps
		$expectedMaxAttempts = 6;
		for ($x = 1; $x <= ($expectedMaxAttempts + 1); $x++) {
			$this->assertFalse(
				api_session_checkauth($username, "wrong_password_{$x}")
			);

			$expectedMessage = 'Invalid authentication details';
			$expectedStatus = 1; // user still active until max attempts is reached
			if ($x === $expectedMaxAttempts) {
				$expectedMessage = 'Sorry, your account has been suspended';
				$expectedStatus = '-2'; // user is locked
			} elseif ($x > $expectedMaxAttempts) {
				$expectedMessage = 'Sorry, your account has been locked due to excessive incorrect password attempts.';
				$expectedStatus = '-2'; // user is still locked
			}

			$this->assertEquals(
				$expectedStatus,
				api_users_setting_get_multi_byitem($user_id, ['status'])['status'],
				"User status after {$x} login attempts"
			);

			$this->assertEquals(
				$expectedMessage,
				api_error_printiferror(['return' => true]),
				"Error message after {$x} login attempts"
			);
		}
	}
}
