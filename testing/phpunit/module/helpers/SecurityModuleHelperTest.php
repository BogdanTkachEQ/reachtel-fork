<?php
/**
 * SecurityModuleHelperTest
 *
 * @author christopher.colborne@reachtel.com.au
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * User Module Helper Test
 */
class SecurityModuleHelperTest extends AbstractModuleHelperTest
{
	use SecurityModuleHelper;
	use UserModuleHelper;

	const EXPECTED_TYPE = 'SECURITYZONE';

	/**
	 * @return void
	 */
	public function test_add_user_security_zone() {
		$user_id = $this->create_new_user();
		$security_zones = api_users_setting_getsingle($user_id, "securityzones");
		$this->assertFalse($security_zones); // no security zones

		$this->add_user_security_zone($user_id, ZONE_PLOTTER_ACCESS);

		// security zone exists
		$security_zones = unserialize(api_users_setting_getsingle($user_id, "securityzones"));
		$this->assertEquals([ZONE_PLOTTER_ACCESS], $security_zones);
		api_users_delete($user_id);
	}

	/**
	 * @return void
	 */
	public function test_remove_user_security_zone() {
		$user_id = $this->create_new_user();
		$security_zones = api_users_setting_getsingle($user_id, "securityzones");
		$this->assertFalse($security_zones); // no security zones

		$this->add_user_security_zone($user_id, ZONE_PLOTTER_ACCESS);

		// security zone exists
		$security_zones = unserialize(api_users_setting_getsingle($user_id, "securityzones"));
		$this->assertEquals([ZONE_PLOTTER_ACCESS], $security_zones);

		$this->remove_user_security_zone($user_id, ZONE_PLOTTER_ACCESS);

		// security zone deleted
		$security_zones = unserialize(api_users_setting_getsingle($user_id, "securityzones"));
		$this->assertEquals([], $security_zones);
		api_users_delete($user_id);
	}
}
