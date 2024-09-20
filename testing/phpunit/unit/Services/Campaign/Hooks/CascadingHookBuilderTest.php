<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\Campaign\Hooks\Cascading;

use Exception;
use InvalidArgumentException;
use Services\Campaign\Hooks\CampaignHookBuilder;
use Services\Campaign\Hooks\Cascading\CascadingCampaignHook;
use Services\Campaign\Hooks\Cascading\Creators\CascadingCampaignCreatorFactory;
use Services\Campaign\Hooks\Cascading\Interfaces\CascadingCampaignCreatorInterface;
use testing\AbstractPhpunitTest;

/**
 * Class CascadingCampaignHookTest
 */
class CampaignHookBuilderTest extends AbstractPhpunitTest {

	/**
	 * @return void
	 */
	public function testBuild() {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[['params' => [1, CAMPAIGN_SETTING_CASCADING_CAMPAIGN], 'return' => '1']],
			false
		);

		$this->mock_function_param_value(
			'api_campaigns_tags_get',
			[['params' => [1, 'post-completion-hook'], 'return' => 'some-tag-hook']],
			false
		);

		$hooks = CampaignHookBuilder::build(1);
		$this->assertTrue($hooks->hasHooks());
		$reflectionClass = new \ReflectionClass($hooks);
		$property = $reflectionClass->getProperty("postHooks");
		$property->setAccessible(true);

		$this->assertCount(2, $property->getValue($hooks));
	}

	/**
	 * @return void
	 */
	public function testCascadingOnlyBuild() {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[['params' => [1, CAMPAIGN_SETTING_CASCADING_CAMPAIGN], 'return' => '1']],
			false
		);

		$hooks = CampaignHookBuilder::build(1);
		$this->assertTrue($hooks->hasHooks());
		$reflectionClass = new \ReflectionClass($hooks);
		$property = $reflectionClass->getProperty("postHooks");
		$property->setAccessible(true);

		$this->assertCount(1, $property->getValue($hooks));
	}

	/**
	 * @return void
	 */
	public function testTagsOnlyBuild() {
		$this->mock_function_param_value(
			'api_campaigns_tags_get',
			[['params' => [1, 'post-completion-hook'], 'return' => 'some-tag-hook']],
			false
		);

		$hooks = CampaignHookBuilder::build(1);
		$this->assertTrue($hooks->hasHooks());
		$reflectionClass = new \ReflectionClass($hooks);
		$property = $reflectionClass->getProperty("postHooks");
		$property->setAccessible(true);

		$this->assertCount(1, $property->getValue($hooks));
	}

	/**
	 * @return void
	 */
	public function testNoTagsBuild() {
		$hooks = CampaignHookBuilder::build(1);
		$this->assertFalse($hooks->hasHooks());
	}
}
