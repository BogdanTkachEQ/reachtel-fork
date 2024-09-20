<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\Campaign\Hooks\Cascading;

use Exception;
use Phake;
use Services\Campaign\Cloner\GenericCampaignCloner;
use Services\Campaign\Hooks\Cascading\Creators\InitialTemplateBasedCascadingCampaignCreator;
use testing\AbstractPhpunitTest;

/**
 *
 * Class GenericCascadingCampaignCreatorUnitTest
 */
class InitialTemplateCascadingCampaignCreatorUnitTest extends AbstractPhpunitTest {

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function campaign_name_data_provider() {
		return [
			'integer 1' => ['test-1', 'test-1-' . InitialTemplateBasedCascadingCampaignCreator::INITIAL_CASCADING_NAME_SUFFIX],
			'integer 2' => ['test-2', 'test-2-' . InitialTemplateBasedCascadingCampaignCreator::INITIAL_CASCADING_NAME_SUFFIX],
			'integer 3' => ['test-3', 'test-3-' . InitialTemplateBasedCascadingCampaignCreator::INITIAL_CASCADING_NAME_SUFFIX],
			'double digit' => ['test-30', 'test-30-' . InitialTemplateBasedCascadingCampaignCreator::INITIAL_CASCADING_NAME_SUFFIX],
			'no suffix' => ['test', 'test-' . InitialTemplateBasedCascadingCampaignCreator::INITIAL_CASCADING_NAME_SUFFIX],

		];
	}

	/**
	 * @dataProvider campaign_name_data_provider
	 * @param string $desiredName
	 * @param string $expectedName
	 * @return void
	 * @throws Exception Api_campaigns_setting_getsingle fails.
	 */
	public function testGetCurrentCampaignName($desiredName, $expectedName) {

		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [1, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE], 'return' => 'next-template'],
				['params' => [1, CAMPAIGN_SETTING_NAME], 'return' => $desiredName],

			],
			false
		);

		$cascade = new InitialTemplateBasedCascadingCampaignCreator(1, Phake::mock(GenericCampaignCloner::class), $desiredName);
		$this->assertEquals($expectedName, $cascade->getCurrentCampaignName());
	}

	/**
	 * @return void
	 */
	public function testGetCurrentCampaignIteration() {
		$cascade = new InitialTemplateBasedCascadingCampaignCreator(1, Phake::mock(GenericCampaignCloner::class), "first-name");
		$this->assertEquals(1, $cascade->getCurrentCampaignIteration());
	}

	/**
	 * @return void
	 */
	public function testGetPreviousCampaignIteration() {
		$cascade = new InitialTemplateBasedCascadingCampaignCreator(1, Phake::mock(GenericCampaignCloner::class), "first-name");
		$this->assertFalse($cascade->getPreviousCampaignIteration());
	}

	/**
	 * @return void
	 */
	public function testGetFirstCampaign() {
		$cascade = new InitialTemplateBasedCascadingCampaignCreator(1, Phake::mock(GenericCampaignCloner::class), "first-name");
		$this->assertFalse($cascade->getFirstCampaign());
	}

	/**
	 * @return void
	 */
	public function testGetFirstCampaignName() {
		$cascade = new InitialTemplateBasedCascadingCampaignCreator(1, Phake::mock(GenericCampaignCloner::class), "first-name");
		$this->assertEquals("first-name-" . InitialTemplateBasedCascadingCampaignCreator::CASCADING_NAME_SUFFIX . "1", $cascade->getFirstCampaignName());

		$cascade = new InitialTemplateBasedCascadingCampaignCreator(1, Phake::mock(GenericCampaignCloner::class), "first-name-step-1");
		$this->assertEquals("first-name-" . InitialTemplateBasedCascadingCampaignCreator::CASCADING_NAME_SUFFIX . "1", $cascade->getFirstCampaignName());
	}
}
