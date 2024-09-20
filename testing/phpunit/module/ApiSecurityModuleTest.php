<?php
/**
 * ApiSecurityModuleTest
 * Module test for api_security.php
 *
 * @author christopher.colborne@reachtel.com.au
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use testing\module\helpers\SecurityModuleHelper;
use testing\module\helpers\UserModuleHelper;

/**
 * Api Users Module Test
 */
class ApiSecurityModuleTest extends AbstractPhpunitModuleTest
{
	use UserModuleHelper;
	use SecurityModuleHelper;

	/**
	 * Type value
	 */
	private static $type;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * @return void
	 */
	public function test_api_security_check() {
		$group_id = $this->create_new_group(uniqid('non admin'));
		$user_id = $this->create_new_user(uniqid('nonadminguy'), $group_id);
		$this->assertFalse(api_security_isadmin($user_id), 'User should not be admin');

		// Check user without zone does not get access
		$result = api_security_check(ZONE_MORPHEUS_ACCESS, null, true, $user_id);
		$this->assertFalse($result, 'Non-admin user without security zone should not have access');

		// Check user with zone HAS access
		$this->add_user_security_zone($user_id, ZONE_MORPHEUS_ACCESS);
		$result = api_security_check(ZONE_MORPHEUS_ACCESS, null, true, $user_id);
		$this->assertTrue($result, 'Non-admin user with security zone should have access');

		api_users_delete($user_id);
		api_groups_delete($group_id);
	}

	/**
	 * @return void
	 */
	public function test_api_security_check_admin_grants_most() {
		$admin_group_id = $this->get_default_group_id();
		$user_id = $this->create_new_user(uniqid('adminguy'), $admin_group_id);
		$this->assertTrue(api_security_isadmin($user_id), 'User should be admin');

		// Check admin user without zone does NOT have access to morpheus
		$result = api_security_check(ZONE_CAMPAIGNS_LISTALL, null, true, $user_id);
		$this->assertTrue($result, 'Admin user without security zone should still have access');

		api_users_delete($user_id);
	}

	/**
	 * @return void
	 */
	public function test_api_security_check_admin_no_include_morpheus() {
		$admin_group_id = $this->get_default_group_id();
		$user_id = $this->create_new_user(uniqid('adminguy'), $admin_group_id);
		$this->assertTrue(api_security_isadmin($user_id), 'User should be admin');

		// Check admin user without zone does NOT have access to morpheus
		$result = api_security_check(ZONE_MORPHEUS_ACCESS, null, true, $user_id);
		$this->assertFalse($result, 'Admin user without morpheus security zone should not have access');

		// Check admin user with zone HAS access to morpheus
		$this->add_user_security_zone($user_id, ZONE_MORPHEUS_ACCESS);
		$result = api_security_check(ZONE_MORPHEUS_ACCESS, null, true, $user_id);
		$this->assertTrue($result, 'Admin user with morpheus security zone should have morpheus access');

		api_users_delete($user_id);
	}

	/**
	 * @return void
	 */
	public function test_api_security_check_admin_no_include_plotter() {
		$admin_group_id = $this->get_default_group_id();
		$user_id = $this->create_new_user(uniqid('adminguy'), $admin_group_id);
		$this->assertTrue(api_security_isadmin($user_id), 'User should be admin');

		// Check admin user without zone does NOT have access to plotter
		$result = api_security_check(ZONE_PLOTTER_ACCESS, null, true, $user_id);
		$this->assertFalse($result, 'Admin user without plotter security zone should not have access');

		// Check admin user with zone HAS access to plotter
		$this->add_user_security_zone($user_id, ZONE_PLOTTER_ACCESS);
		$result = api_security_check(ZONE_PLOTTER_ACCESS, null, true, $user_id);
		$this->assertTrue($result, 'Admin user with plotter security zone should have plotter access');

		api_users_delete($user_id);
	}
	/**
	 * @return void
	 */
	public function test_api_security_check_admin_no_include_dialplangen() {
		$admin_group_id = $this->get_default_group_id();
		$user_id = $this->create_new_user(uniqid('adminguy'), $admin_group_id);
		$this->assertTrue(api_security_isadmin($user_id), 'User should be admin');

		// Check admin user without zone does NOT have access to dialplan generator
		$result = api_security_check(ZONE_DIALPLANGEN_ACCESS, null, true, $user_id);
		$this->assertFalse($result, 'Admin user without dialplan generator security zone should not have access');

		// Check admin user with zone HAS access to dialplan generator
		$this->add_user_security_zone($user_id, ZONE_DIALPLANGEN_ACCESS);
		$result = api_security_check(ZONE_DIALPLANGEN_ACCESS, null, true, $user_id);
		$this->assertTrue($result, 'Admin user with dialplan generator security zone should have dialplan generator access');

		api_users_delete($user_id);
	}
}
