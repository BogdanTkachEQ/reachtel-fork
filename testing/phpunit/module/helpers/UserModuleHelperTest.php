<?php
/**
 * UserModuleHelperTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * User Module Helper Test
 */
class UserModuleHelperTest extends AbstractModuleHelperTest
{
	use UserModuleHelper;

	const EXPECTED_TYPE = 'USERS';

	/**
	 * @group get_default_admin_id
	 * @return void
	 */
	public function test_get_default_admin_id() {
		$this->assertSameEquals(2, $this->get_default_admin_id());
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function create_new_user_data() {
		return [
			// Failure username too short
			[false, '5'],

			// Failure username chars
			[false, '#wrong ch@rs#'],

			// Success
			[true],
			[true, null, $this->get_default_group_id()],
		];
	}

	/**
	 * @group create_new_user
	 * @dataProvider create_new_user_data
	 * @param boolean $expected_success
	 * @param string  $username
	 * @param integer $group_id
	 * @return void
	 */
	public function test_create_new_user($expected_success, $username = null, $group_id = null) {
		$this->do_test_create_new($expected_success, [$username, $group_id]);
	}

	/**
	 * @group create_new_user
	 * @return void
	 */
	public function test_failure_create_new_user_exists() {
		$expected_id = $this->get_expected_next_user_id();
		$username = uniqid('test');
		$this->assertSameEquals($expected_id, $this->create_new_user($username));
		$this->assertFalse($this->create_new_user($username));
	}
}
