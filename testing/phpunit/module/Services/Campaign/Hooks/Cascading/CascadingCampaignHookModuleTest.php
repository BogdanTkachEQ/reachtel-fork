<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\Campaign\Hooks\Cascading;

use Exception;
use InvalidArgumentException;
use Services\Campaign\Hooks\Cascading\CascadingCampaignHook;
use Services\Campaign\Hooks\Cascading\Creators\CascadingCampaignCreatorFactory;
use Services\Campaign\Hooks\Cascading\Interfaces\CascadingCampaignCreatorInterface;
use testing\module\AbstractDatabasePhpunitModuleTest;
use testing\module\helpers\CampaignModuleHelper;
use testing\module\helpers\UserModuleHelper;

/**
 * Class CascadingCampaignHookTest
 */
class CascadingCampaignHookModuleTest extends AbstractDatabasePhpunitModuleTest {
	use CampaignModuleHelper;
	use UserModuleHelper;

	private $sourceCampaignId;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->purge_all_campaigns();
		$user = $this->create_new_user("test-user");
		$campaignSettings = [
			'cascadingcampaign' => 1,
			'defaultdestination1' => "CONTACT_NO_1"
		];

		$this->sourceCampaignId = $this->create_new_campaign("source-campaign", "phone", $user, $campaignSettings);
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		$this->purge_all_campaigns();
	}

	/**
	 * @return void
	 */
	public function testHasRun() {
		$creator = \Phake::mock(CascadingCampaignCreatorInterface::class);
		\Phake::when($creator)->setupNextCampaign(\Phake::anyParameters())->thenReturn(1);
		$factory = \Phake::mock(CascadingCampaignCreatorFactory::class);
		\Phake::when($factory)->makeCreator(\Phake::anyParameters())->thenReturn($creator);
		$hook = new CascadingCampaignHook($factory, $this->sourceCampaignId);
		$hook->run();
		$this->assertTrue($hook->hasRun());
		$this->assertContains("No next campaign", $hook->getErrors());
	}

	/**
	 * @return void
	 */
	public function testWillRun() {
		api_campaigns_setting_set($this->sourceCampaignId, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE, "next-template");

		$creator = \Phake::mock(CascadingCampaignCreatorInterface::class);
		\Phake::when($creator)->setupNextCampaign(\Phake::anyParameters())->thenThrow(new InvalidArgumentException("No template"));
		$factory = \Phake::mock(CascadingCampaignCreatorFactory::class);
		\Phake::when($factory)->makeCreator(\Phake::anyParameters())->thenReturn($creator);

		$hook = new CascadingCampaignHook($factory, $this->sourceCampaignId);
		$this->expectException(InvalidArgumentException::class);  //next-template doesn't exist, but if it throws this it tried to run
		$hook->run();
	}
}
