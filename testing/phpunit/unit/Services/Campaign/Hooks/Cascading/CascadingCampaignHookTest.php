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
use testing\AbstractPhpunitTest;

/**
 * Class CascadingCampaignHookTest
 */
class CascadingCampaignHookTest extends AbstractPhpunitTest {

	/**
	 * @return void
	 * @throws Exception Api_campaigns_setting_getsingle.
	 */
	public function testRun() {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[['params' => [1, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE], 'return' => 'test'],],
			false
		);

		$creator = \Phake::mock(CascadingCampaignCreatorInterface::class);
		\Phake::when($creator)->setupNextCampaign(\Phake::anyParameters())
			->thenThrow(new InvalidArgumentException("No template"));
		$factory = \Phake::mock(CascadingCampaignCreatorFactory::class);
		\Phake::when($factory)->makeCreator(\Phake::anyParameters())->thenReturn($creator);

		$hook = new CascadingCampaignHook($factory, 1);
		$this->expectException(InvalidArgumentException::class);
		$hook->run();
	}

	/**
	 * @return void
	 * @throws Exception Campaign failed to clone.
	 */
	public function testHasRun() {
		$creator = \Phake::mock(CascadingCampaignCreatorInterface::class);
		\Phake::when($creator)->setupNextCampaign(\Phake::anyParameters())->thenReturn(1);
		$factory = \Phake::mock(CascadingCampaignCreatorFactory::class);
		\Phake::when($factory)->makeCreator(\Phake::anyParameters())->thenReturn($creator);
		$hook = new CascadingCampaignHook($factory, 1);

		$this->assertNull($hook->run());
		$this->assertTrue($hook->hasRun());
	}

	/**
	 * @return void
	 */
	public function testGetName() {
		$creator = \Phake::mock(CascadingCampaignCreatorInterface::class);
		\Phake::when($creator)->setupNextCampaign(\Phake::anyParameters())->thenReturn(1);
		$factory = \Phake::mock(CascadingCampaignCreatorFactory::class);
		\Phake::when($factory)->makeCreator(\Phake::anyParameters())->thenReturn($creator);
		$hook = new CascadingCampaignHook($factory, 1);

		$this->assertEquals(get_class($hook), $hook->getName());
	}

	/**
	 * @return void
	 * @throws Exception Campaign failed to clone.
	 */
	public function testRunWasSuccess() {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [1, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE], 'return' => 'test']
			],
			false
		);
		$creator = \Phake::mock(CascadingCampaignCreatorInterface::class);
		\Phake::when($creator)->setupNextCampaign(\Phake::anyParameters())
			->thenThrow(new InvalidArgumentException("No template"));
		$factory = \Phake::mock(CascadingCampaignCreatorFactory::class);
		\Phake::when($factory)->makeCreator(\Phake::anyParameters())->thenReturn($creator);
		$hook = new CascadingCampaignHook($factory, 1);
		$this->expectException(InvalidArgumentException::class);
		$this->assertFalse($hook->runWasSuccess());
		$this->assertNull($hook->run());
		$this->assertFalse($hook->runWasSuccess());
	}

	/**
	 * @return void
	 * @throws Exception Campaign failed to clone.
	 */
	public function testGetErrors() {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [1, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE], 'return' => 'test']
			],
			false
		);
		$creator = \Phake::mock(CascadingCampaignCreatorInterface::class);
		\Phake::when($creator)->setupNextCampaign(\Phake::anyParameters())
			->thenThrow(new InvalidArgumentException("No template"));
		$factory = \Phake::mock(CascadingCampaignCreatorFactory::class);
		\Phake::when($factory)->makeCreator(\Phake::anyParameters())->thenReturn($creator);
		$hook = new CascadingCampaignHook($factory, 1);
		$this->expectException(InvalidArgumentException::class);
		$this->assertFalse($hook->runWasSuccess());
		$this->assertNull($hook->run());
		$this->assertNotEmpty($hook->getErrors());
	}
}
