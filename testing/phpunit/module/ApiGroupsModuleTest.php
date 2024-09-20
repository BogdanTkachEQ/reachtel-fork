<?php
/**
 * ApiGroupsModuleTest
 * Module test for api_groups.php
 *
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use testing\module\helpers\GroupModuleHelper;
use testing\module\helpers\SmsDidModuleHelper;

/**
 * Class ApiGroupsModuleTest
 */
class ApiGroupsModuleTest extends AbstractPhpunitModuleTest
{
	use SmsDidModuleHelper;
	use GroupModuleHelper;

	/**
	 * @group api_groups_get_all_dids
	 * @return void
	 */
	public function test_api_groups_get_all_dids() {
		$sms_dids = [];
		$group_ids = [];
		for ($i = 0; $i < 2; $i++) {
			$group_ids[] = $this->create_new_group();
			for ($j = 0; $j <= 2; $j++) {
				$did = $this->create_new_smsdid();
				$sms_dids[$group_ids[$i]][] = $did;
				api_sms_dids_setting_set($did, 'groupowner', $group_ids[$i]);
			}
		}

		foreach ($group_ids as $id) {
			$results = api_groups_get_all_dids($id, 'SMSDIDS');
			$this->assertSameEquals($sms_dids[$id], array_keys($results));
		}
	}
}
