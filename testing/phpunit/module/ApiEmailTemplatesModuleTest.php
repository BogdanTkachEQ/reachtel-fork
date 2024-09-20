<?php
/**
 * ApiEmailTemplatesModuleTest
 * @author Phillip Berry
 *
 * @copyright  ReachTel (ABN 40 133 677 933)
 *
 */

namespace testing\module;

use testing\module\helpers\GroupModuleHelper;

/**
 * Class ApiEmailTemplatesModuleTest
 */
class ApiEmailTemplatesModuleTest extends AbstractPhpunitModuleTest
{
	use GroupModuleHelper;

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		$name_frags = ['2', '3', '10', '11', 'renamed'];
		foreach ($name_frags as $frag) {
			$id = api_emailtemplates_nametoid("test-email-template-{$frag}");
			api_emailtemplates_delete($id);
		}
	}

	/**
	 * @return void
	 */
	public function test_api_emailtemplates_add() {
		$groupid2 = $this->create_new_group();
		$this->assertNotFalse(api_emailtemplates_add("test-email-template-2", $groupid2));
		$this->assertNotFalse(api_emailtemplates_add("test-email-template-3", $groupid2));
	}

	/**
	 * @return void
	 */
	public function test_api_emailtemplates_add_collision() {
		$groupid2 = $this->create_new_group();
		api_emailtemplates_add("test-email-template-1", $groupid2);
		$this->assertFalse(api_emailtemplates_add("test-email-template-1", $groupid2));
	}

	/**
	 * @return void
	 */
	public function test_api_emailtemplates_rename() {
		$groupid2 = $this->create_new_group();
		$id1 = api_emailtemplates_add("test-email-template-10", $groupid2);
		$id2 = api_emailtemplates_add("test-email-template-11", $groupid2);
		$this->assertNotFalse($id1);
		$this->assertNotFalse($id2);

		$this->assertTrue(api_emailtemplates_rename_template($id1, "test-email-template-renamed"));
		$this->assertSame("test-email-template-renamed", api_emailtemplates_setting_getsingle($id1, "name"));
		$this->assertSame("test-email-template-11", api_emailtemplates_setting_getsingle($id2, "name"));
	}

	/**
	 * @return void
	 */
	public function test_api_emailtemplates_sanitise_template_name() {
		$this->assertEquals("test", api_emailtemplates_sanitise_template_name("<test>test</test>"));
		$this->assertEquals("test", api_emailtemplates_sanitise_template_name("test"));
	}
}
