<?php
/**
 * GroupModuleHelperTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Group Module Helper Test
 */
class GroupModuleHelperTest extends AbstractModuleHelperTest
{
	use GroupModuleHelper;

	const EXPECTED_TYPE = 'GROUPS';

	/**
	 * @group get_default_group_id
	 * @return void
	 */
	public function test_get_default_group_id() {
		$this->assertSameEquals(2, $this->get_default_group_id());
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function create_new_group_data() {
		return [
			// Failure group name too short
			[false, '5'],

			// Failure group name too long
			[false, uniqid(str_repeat('x', 23))], // > 35 chars

			// Failure group name chars
			[false, '#wrong ch@rs#'],

			// Success
			[true],
			[true, uniqid(str_repeat('OK', 11))], // = 35 chars
		];
	}

	/**
	 * @group create_new_group
	 * @dataProvider create_new_group_data
	 * @param boolean $expected_success
	 * @param string  $group_name
	 * @return void
	 */
	public function test_create_new_group($expected_success, $group_name = null) {
		$this->do_test_create_new($expected_success, [$group_name]);
	}

	/**
	 * @group create_new_group
	 * @return void
	 */
	public function test_create_new_group_settings() {
		$settings = [
			'customername' => 'Test Group',
			'sftpemailnotificationto' => 'sftp@customer.com',
			'selcommaccountno' => '12345',
		];
		$id = $this->do_test_create_new(true, [uniqid('new_group_settings'), $settings]);

		foreach ($settings as $key => $expected_value) {
			$value = api_groups_setting_getsingle($id, $key);
			$this->assertEquals($expected_value, $value, "Failed asserting group setting '{$key}'.\n- '$expected_value'\n+ '$value'");
		}
	}

	/**
	 * @return void
	 */
	public function test_purge_all() {
		// overrides test_purge_all to not be executed
		return;
	}

	/**
	 * @group purge_all_groups
	 * @dataProvider create_new_group_data
	 * @param boolean $expected_success
	 * @param string  $group_name
	 * @return void
	 */
	public function test_purge_all_groups($expected_success, $group_name = null) {
		$group_id = $this->create_new_group();
		$this->assertTrue(api_groups_checkidexists($group_id));
		$all_groups = api_groups_listall();

		$this->purge_all_groups();

		foreach (array_keys($all_groups) as $group_id) {
			if ($group_id == $this->get_default_group_id()) {
				$this->assertTrue(api_groups_checkidexists($group_id), "Failed admin group id {$group_id} has not been deleted");
			} else {
				$this->assertFalse(api_groups_checkidexists($group_id), "Failed group id {$group_id} has been deleted");
			}
		}
	}
}
