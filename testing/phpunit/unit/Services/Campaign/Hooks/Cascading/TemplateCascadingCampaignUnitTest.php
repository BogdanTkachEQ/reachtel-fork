<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\Campaign\Hooks\Cascading;

use Phake;
use Services\Campaign\Cloner\GenericCampaignCloner;
use Services\Campaign\Hooks\Cascading\Creators\AbstractGenericCascadingCampaignCreator;
use Services\Campaign\Hooks\Cascading\Creators\TemplateBasedCascadingCampaignCreator;
use Services\Exceptions\CampaignValidationException;
use testing\AbstractPhpunitTest;

/**
 * Class GenericCascadingCampaignCreatorUnitTest
 */
class TemplateCascadingCampaignCreatorUnitTest extends AbstractPhpunitTest {
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function campaign_name_data_provider() {
		return [
			'first iteration' => ['test-1', 'test-1-step-2', false, 1],
			'second iteration' => ['test-2', 'test-2-step-3', 'test-2-step-1', 2],
			'third iteration' => ['test-3', 'test-3-step-4', 'test-3-step-2', 3],
			'fourth iteration' => ['test-30', 'test-30-step-31', 'test-30-step-29', 30],
			'first iteration clean name' => ['test-step-1', 'test-step-2', false, 1],
			'second iteration clean name' => ['test-step-2', 'test-step-3', 'test-step-1', 2],
			'third iteration clean name' => ['test-step-3', 'test-step-4', 'test-step-2', 3],
			'fourth iteration clean name' => ['test-step-30', 'test-step-31', 'test-step-29', 30],
			'first iteration nothing' => ['test', 'test-step-2', false, 1],
			'second iteration nothing' => ['test', 'test-step-3', 'test-step-1', 2],
			'first iteration real' => ['test-template-2009-01-23', 'test-template-2009-01-23-step-2', false, 1],
			'second iteration real' => ['test-template-2009-01-23', 'test-template-2009-01-23-step-3', 'test-template-2009-01-23-step-1', 2]
		];
	}

	/**
	 * @dataProvider campaign_name_data_provider
	 * @param string  $currentName
	 * @param string  $nextName
	 * @param string  $prevName
	 * @param integer $iteration
	 * @return void
	 */
	public function testGetCurrentCampaignName($currentName, $nextName, $prevName, $iteration) {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[['params' => [1, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE], 'return' => 'next-template'],
			 ['params' => [1, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => $iteration],
			 ['params' => [1, CAMPAIGN_SETTING_NAME], 'return' => $currentName],
			],
			false
		);

		$cascade = new TemplateBasedCascadingCampaignCreator(1, Phake::mock(GenericCampaignCloner::class));
		$this->assertEquals($currentName, $cascade->getCurrentCampaignName());
	}

	/**
	 * @dataProvider campaign_name_data_provider
	 * @param string  $currentName
	 * @param string  $nextName
	 * @param string  $prevName
	 * @param integer $iteration
	 * @return void
	 */
	public function testGetCurrentCampaignIteration($currentName, $nextName, $prevName, $iteration) {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[['params' => [1, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE], 'return' => 'next-template'],
			 ['params' => [1, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => $iteration],
			 ['params' => [1, CAMPAIGN_SETTING_NAME], 'return' => $currentName],

			],
			false
		);

		$cascade = new TemplateBasedCascadingCampaignCreator(1, Phake::mock(GenericCampaignCloner::class));
		$this->assertEquals($iteration, $cascade->getCurrentCampaignIteration());
	}

	/**
	 * @dataProvider campaign_name_data_provider
	 * @param string  $currentName
	 * @param string  $nextName
	 * @param string  $prevName
	 * @param integer $iteration
	 * @return void
	 */
	public function testGetNextCampaignName($currentName, $nextName, $prevName, $iteration) {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[['params' => [1, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE], 'return' => 'next-template'],
			 ['params' => [1, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => $iteration],
			 ['params' => [1, CAMPAIGN_SETTING_NAME], 'return' => $currentName],

			],
			false
		);
		$cascade = new TemplateBasedCascadingCampaignCreator(1, Phake::mock(GenericCampaignCloner::class));
		$this->assertEquals($nextName, $cascade->getNextCampaignName());
	}

	/**
	 * @dataProvider campaign_name_data_provider
	 * @param string  $currentName
	 * @param string  $nextName
	 * @param string  $prevName
	 * @param integer $iteration
	 * @return void
	 */
	public function testGetPrevCampaignName($currentName, $nextName, $prevName, $iteration) {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[['params' => [10, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE], 'return' => 'next-template'],
			 ['params' => [10, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => $iteration],
			 ['params' => [10, CAMPAIGN_SETTING_NAME], 'return' => $currentName],
			 ['params' => [10, CAMPAIGN_SETTING_CASCADING_PREVIOUS_ITERATION_ID], 'return' => 9],
			 ['params' => [9, CAMPAIGN_SETTING_NAME], 'return' => $prevName],

			],
			false
		);
		$cascade = new TemplateBasedCascadingCampaignCreator(10, Phake::mock(GenericCampaignCloner::class));
		$this->assertEquals($prevName, $cascade->getPreviousCampaignName());
	}

	/**
	 * @dataProvider campaign_name_data_provider
	 * @param string  $currentName
	 * @param string  $nextName
	 * @param string  $prevName
	 * @param integer $iteration
	 * @return void
	 */
	public function testGetNextCampaignIteration($currentName, $nextName, $prevName, $iteration) {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[['params' => [1, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE], 'return' => 'next-template'],
			 ['params' => [1, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => $iteration],
			 ['params' => [1, CAMPAIGN_SETTING_NAME], 'return' => $currentName],

			],
			false
		);
		$cascade = new TemplateBasedCascadingCampaignCreator(1, Phake::mock(GenericCampaignCloner::class));
		$this->assertEquals($iteration + 1, $cascade->getNextCampaignIteration());
	}

	/**
	 * @dataProvider campaign_name_data_provider
	 * @param string  $currentName
	 * @param string  $nextName
	 * @param string  $prevName
	 * @param integer $iteration
	 * @return void
	 */
	public function testGetPreviousCampaignIteration($currentName, $nextName, $prevName, $iteration) {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[['params' => [10, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE], 'return' => 'next-template'],
			 ['params' => [10, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => $iteration],
			 ['params' => [10, CAMPAIGN_SETTING_NAME], 'return' => $currentName],
			 ['params' => [10, CAMPAIGN_SETTING_CASCADING_PREVIOUS_ITERATION_ID], 'return' => 9],
			 ['params' => [9, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => $iteration - 1],
			],
			false
		);
		$cascade = new TemplateBasedCascadingCampaignCreator(10, Phake::mock(GenericCampaignCloner::class));
		$this->assertEquals($iteration - 1, $cascade->getPreviousCampaignIteration());
	}

	/**
	 * @return void
	 */
	public function testGetFirstCampaignName() {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[['params' => [4, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => 4],
				['params' => [4, CAMPAIGN_SETTING_NAME], 'return' => "test-campaign-name-step-4"],
				['params' => [4, CAMPAIGN_SETTING_CASCADING_PREVIOUS_ITERATION_ID], 'return' => 3],
				['params' => [3, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => 3],
				['params' => [3, CAMPAIGN_SETTING_CASCADING_PREVIOUS_ITERATION_ID], 'return' => 2],
				['params' => [2, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => 2],
				['params' => [2, CAMPAIGN_SETTING_CASCADING_PREVIOUS_ITERATION_ID], 'return' => 1],
				['params' => [1, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => 1],
				['params' => [1, CAMPAIGN_SETTING_NAME], 'return' => "test-campaign-name-step-1"]
			],
			false
		);
		$cascade = new TemplateBasedCascadingCampaignCreator(4, Phake::mock(GenericCampaignCloner::class));
		$this->assertEquals("test-campaign-name-step-1", $cascade->getFirstCampaignName());
		$cascade = new TemplateBasedCascadingCampaignCreator(3, Phake::mock(GenericCampaignCloner::class));
		$this->assertEquals("test-campaign-name-step-1", $cascade->getFirstCampaignName());
		$cascade = new TemplateBasedCascadingCampaignCreator(2, Phake::mock(GenericCampaignCloner::class));
		$this->assertEquals("test-campaign-name-step-1", $cascade->getFirstCampaignName());
		$cascade = new TemplateBasedCascadingCampaignCreator(1, Phake::mock(GenericCampaignCloner::class));
		$this->assertEquals("test-campaign-name-step-1", $cascade->getFirstCampaignName());
	}

	/**
	 * @return void
	 */
	public function testGetFirstCampaignNameFirstIteration() {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [1, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => 1],
				['params' => [1, CAMPAIGN_SETTING_NAME], 'return' => "test-campaign-name-step-1"],
			],
			false
		);
		$cascade = new TemplateBasedCascadingCampaignCreator(1, Phake::mock(GenericCampaignCloner::class));
		$this->assertEquals("test-campaign-name-step-1", $cascade->getFirstCampaignName());
	}

	/**
	 * @return void
	 */
	public function testGetFirstCampaignNameBrokenIterationChain() {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [3, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => 3],
				['params' => [3, CAMPAIGN_SETTING_CASCADING_PREVIOUS_ITERATION_ID], 'return' => 2],
				['params' => [1, CAMPAIGN_SETTING_NAME], 'return' => "test-campaign-name-step-1"]
			],
			false
		);

		$cascade = new TemplateBasedCascadingCampaignCreator(3, Phake::mock(GenericCampaignCloner::class));
		$this->assertFalse($cascade->getFirstCampaignName());
	}

	/**
	 * @return void
	 */
	public function testTooManyIterations() {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [1, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => 20],
				['params' => [1, CAMPAIGN_SETTING_NAME], 'return' => "test-campaign-name-step-1"],
			],
			false
		);
		$cascade = new TemplateBasedCascadingCampaignCreator(1, Phake::mock(GenericCampaignCloner::class));
		$this->expectException(CampaignValidationException::class);
		$cascade->setupNextCampaign();
	}

	/**
	 * @return array
	 */
	public function nextCampaignDataProvider() {
		return ['not active' => [1, 2, false], 'active' => [2, 3, true]];
	}

	/**
	 * @dataProvider nextCampaignDataProvider
	 * @param integer $campaignId
	 * @param integer $nextCampaignId
	 * @param boolean $active
	 * @return void
	 */
	public function testsetupNextCampaign($campaignId, $nextCampaignId, $active) {
		$creator = Phake::partialMock(
			AbstractGenericCascadingCampaignCreator::class,
			$campaignId,
			Phake::mock(GenericCampaignCloner::class)
		);
		Phake::when($creator)->cloneCampaign(Phake::anyParameters())->thenReturn($nextCampaignId);
		Phake::when($creator)->copyTargets(Phake::anyParameters())->thenReturn(5);

		$this->listen_mocked_function('api_campaigns_setting_getsingle');
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[['params' => [$campaignId, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => 1],
			 ['params' => [$campaignId, CAMPAIGN_SETTING_CASCADING_RATE_MODIFIER], 'return' => 10],
			 ['params' => [$campaignId, CAMPAIGN_SETTING_CASCADING_DELAY], 'return' => 5]],
			false
		);

		if ($active) {
			$this->listen_mocked_function('api_campaigns_setting_set');
			$this->mock_function_param_value(
				'api_campaigns_setting_set',
				[['params' => [$nextCampaignId, "sendrate", 1], 'return' => true],
											  ['params' => [$nextCampaignId, CAMPAIGN_SETTING_STATUS,
															CAMPAIGN_SETTING_STATUS_VALUE_ACTIVE], 'return' => true],],
				false
			);
		}

		$id = $creator->setupNextCampaign($active);
		$this->assertEquals($id, $nextCampaignId);
		$this->assertListenMockFunction(
			'api_campaigns_setting_getsingle',
			[['args' => [$campaignId, CAMPAIGN_SETTING_CASCADING_ITERATION], 'return' => 1],
			 ['args' => [$campaignId, CAMPAIGN_SETTING_CASCADING_DELAY], 'return' => 5],
			 ['args' => [$campaignId, CAMPAIGN_SETTING_CASCADING_RATE_MODIFIER], 'return' => 10]
			]
		);

		if ($active) {
			$this->assertListenMockFunction(
				'api_campaigns_setting_set',
				[['args' => [$id, "sendrate", 1], 'return' => true],
				 ['args' => [$id, CAMPAIGN_SETTING_STATUS, CAMPAIGN_SETTING_STATUS_VALUE_ACTIVE], 'return' => true]
				]
			);
		}
		$this->remove_mocked_functions('api_campaigns_setting_setsingle');
	}
}
