<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Hooks;

use testing\AbstractPhpunitTest;

/**
 * Class TagCampaignHookTest
 */
class TagCampaignHookTest extends AbstractPhpunitTest {

	/**
	 * @return void
	 */
	public function testHasRun() {
		$this->mock_function_value("api_campaigns_tags_get", "testhook"); // Get the hook tag
		$hook = new TagCampaignHook(1);
		$this->assertFalse($hook->hasRun());
		$hook->run();
		$this->assertTrue($hook->hasRun());
	}

	/**
	 * @return void
	 */
	public function testRunWasSuccess() {
		$this->mock_function_value("api_campaigns_tags_get", "testhook");
		$hook = new TagCampaignHook(1);
		$hook->run();
		$this->assertTrue($hook->runWasSuccess());
	}

	/**
	 * @return void
	 */
	public function testHasNoFile() {
		$this->mock_function_value("api_campaigns_tags_get", "testhook1");
		$hook = new TagCampaignHook(1);
		$this->assertFalse($hook->run());
		$this->assertTrue($hook->hasRun());
	}

	/**
	 * @return void
	 */
	public function testGetErrors() {
		$this->mock_function_value("api_campaigns_tags_get", "testhook");
		$hook = new TagCampaignHook(0);
		$this->assertFalse($hook->run());
		$this->assertTrue($hook->hasRun());
		$this->assertNotEmpty($hook->getErrors());
	}

	/**
	 * @return void
	 */
	public function testGetName() {
		$this->mock_function_value("api_campaigns_tags_get", "testhook");
		$hook = new TagCampaignHook(1);
		$this->assertEquals(get_class($hook), $hook->getName());
	}

	/**
	 * @return void
	 */
	public function testRun() {
		$this->mock_function_value("api_campaigns_tags_get", "testhook");
		$hook = new TagCampaignHook(1);
		$this->assertTrue($hook->run());
		$this->assertTrue($hook->hasRun());
		$this->assertEmpty($hook->getErrors());
	}
}
