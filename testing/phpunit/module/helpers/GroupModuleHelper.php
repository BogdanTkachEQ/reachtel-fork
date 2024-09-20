<?php
/**
 * GroupModuleHelper
 * Helper to create groups
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait Helper for groups
 */
trait GroupModuleHelper
{
	/**
	 * @return string
	 */
	protected static function get_group_type() {
		return 'GROUPS';
	}

	/**
	 * @return string
	 */
	protected function get_expected_next_group_id() {
		$val = $this->get_expected_next_id(self::get_group_type());
		return $val;
	}

	/**
	 * @return integer
	 */
	protected function get_default_group_id() {
		return (int) self::get_config('helpers.group.default_admin_id');
	}

	/**
	 * @param string $group_name
	 * @param array  $settings
	 * @return integer|false
	 */
	protected function create_new_group($group_name = null, array $settings = []) {
		$expected_group_id = $this->get_expected_next_group_id();
		$group_id = api_groups_add($group_name ? : uniqid('test'));

		if ($group_id) {
			$group_id = (int) $group_id;
			foreach ($settings as $setting => $value) {
				$this->assertTrue(api_groups_setting_set($group_id, $setting, $value));
			}
		}

		return $group_id;
	}

	/**
	 * @return void
	 */
	protected function purge_all_groups() {
		$all_groups = api_keystore_getentirenamespace("GROUPS");
		
		// Skip the admin group
		unset($all_groups[$this->get_default_group_id()]);
		foreach ($all_groups as $id => $entry) {
			foreach ($entry as $item => $value) {
				$this->assertTrue(
					api_groups_setting_delete_single($id, $item),
					"Failed deleting group id {$id} (maybe assigned to a campaign ?)"
				);
			}
		}
	}
}
