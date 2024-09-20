<?php
/**
 * UserModuleHelper
 * Helper to create users
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

use Services\User\UserTypeEnum;

/**
 * Trait Helper for users
 */
trait UserModuleHelper
{
	use GroupModuleHelper;

	/**
	 * @return string
	 */
	protected static function get_user_type() {
		return 'USERS';
	}

	/**
	 * @return string
	 */
	protected function get_expected_next_user_id() {
		return $this->get_expected_next_id(self::get_user_type());
	}

	/**
	 * @return string
	 */
	protected function get_default_admin_id() {
		return (int) self::get_config('helpers.user.default_admin_id');
	}

	/**
	 * @param string       $username
	 * @param integer      $group_id
	 * @param UserTypeEnum $user_type
	 * @return integer
	 */
	protected function create_new_user($username = null, $group_id = null, UserTypeEnum $user_type = null) {
		$user_id = api_users_add($username ? : uniqid('test'), null, null, $user_type);

		if ($user_id) {
			$group_id = $group_id ? : $this->get_default_group_id();
			$this->assertTrue(
				api_groups_checkidexists($group_id),
				"Group id {$group_id} does not exists."
			);
			$this->assertTrue(
				api_users_setting_set($user_id, "groupowner", $group_id),
				"Can not assign 'groupowner' key value '{$group_id}' to user id {$user_id}."
			);
		}

		return $user_id;
	}
}
