<?php
/**
 * SecurityModuleHelper
 * Helper to modify security zones
 *
 * @author christopher.colborne@reachtel.com.au
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait Helper for security
 */
trait SecurityModuleHelper
{
	/**
	 * @return string
	 */
	protected static function get_securityzone_type() {
		return 'SECURITYZONE';
	}

	/**
	 * @return string
	 */
	protected function get_expected_next_securityzone_id() {
		return $this->get_expected_next_id(self::get_securityzone_type());
	}

	/**
	 * @param integer $user_id
	 * @param integer $zone_id
	 *
	 * @return void
	 */
	protected function add_user_security_zone($user_id = null, $zone_id = null) {
		$security_zones = unserialize(api_users_setting_getsingle($user_id, "securityzones"));
		$security_zones[] = $zone_id;
		api_users_setting_set($user_id, "securityzones", serialize(array_unique($security_zones)));
	}

	/**
	 * @param integer $user_id
	 * @param integer $zone_id
	 *
	 * @return void
	 */
	protected function remove_user_security_zone($user_id = null, $zone_id = null) {
		$security_zones = unserialize(api_users_setting_getsingle($user_id, "securityzones"));
		$security_zones = array_filter(
			$security_zones,
			function($zone_id) {
				return (int) $zone_id !== $zone_id;
			}
		);
		api_users_setting_set($user_id, "securityzones", serialize($security_zones));
	}
}
