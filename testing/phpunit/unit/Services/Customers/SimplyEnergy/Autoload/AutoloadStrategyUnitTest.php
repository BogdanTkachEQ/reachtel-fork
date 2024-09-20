<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Customers\SimplyEnergy\Autoload;

use Services\Autoload\AbstractAutoloadStrategy;
use Services\Campaign\Hooks\Cascading\Creators\CascadingCampaignCreatorFactory;
use Services\Campaign\Hooks\Cascading\Creators\InitialTemplateBasedCascadingCampaignCreator;
use Services\Customers\SimplyEnergy\Autoload\AutoloadStrategy;
use testing\unit\Services\Autoload\AbstractLineItemProcessorStrategyUnitTest;

/**
 * Class AutoloadStrategyUnitTest
 */
class AutoloadStrategyUnitTest extends AbstractLineItemProcessorStrategyUnitTest
{
	const DATE = '20190827';
	/**
	 * @var \DateTime
	 */
	private $runDateTime;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->runDateTime = \Phake::mock(\DateTime::class);
		\Phake::when($this->runDateTime)->format('Ymd')->thenReturn(self::DATE);
		parent::setUp();
	}

	/**
	 * @return AbstractAutoloadStrategy
	 */
	protected function getStrategy() {
		$factory = \Phake::mock(CascadingCampaignCreatorFactory::class);
		$creator = \Phake::mock(InitialTemplateBasedCascadingCampaignCreator::class);
		\Phake::when($creator)->setupNextCampaign(\Phake::anyParameters())
			->thenReturn(100)
			->thenReturn(101)
			->thenReturn(102)
			->thenReturn(103);
		\Phake::when($factory)->makeCreator(\Phake::anyParameters())->thenReturn($creator);
		return new AutoloadStrategy($this->fileProcessor, $this->runDateTime, $factory);
	}

	/**
	 * @return array
	 */
	protected function getRequiredColumns() {
		return ['PREFER TIME', 'C1_PHONE1'];
	}

	/**
	 * @return void
	 */
	public function testProcess() {
		$destination = '61412345678';

		$timeCampaignMap = [
			'8AM - 12PM' => ['SimplyEnergy-YYYYMMDD-EarlyCollectionsIVR-9-12-Contact1', 12, 100, true],
			'12PM - 3PM' => ['SimplyEnergy-YYYYMMDD-EarlyCollectionsIVR-12-15-Contact1', 14, 101, true],
			'3PM - 7PM' => ['SimplyEnergy-YYYYMMDD-EarlyCollectionsIVR-15-19-Contact1', 16, 102, true],
			'default' => ['SimplyEnergy-YYYYMMDD-EarlyCollectionsIVR-9-19-Contact1', 18, 103, true]
		];

		$contents = [];
		$checkNameExistsParams = [];
		$createCampaignParams = [];
		$addTargetParams = [];
		$dedupeParams = [];
		$campaignSettingArgs = [];
		$duplicateCampaignId = 123;
		foreach ($timeCampaignMap as $time => $campaign) {
			$contents[] = ['PREFER TIME' => $time, 'C1_PHONE1' => $destination];

			if (!$campaign[3]) {
				$createCampaignParams[] = ['params' => [$campaign[0], $duplicateCampaignId], 'return' => $campaign[1]];
			} else {
				$checkNameExistsParams[] = ['params' => [$campaign[0]], 'return' => $campaign[1]];
			}
			$addTargetParams[] = [
				'args' => [$campaign[2], $destination, null, null, ['PREFER TIME' => $time, 'C1_PHONE1' => $destination]],
				'return' => true
			];

			$dedupeParams[] = [
				'args' => [$campaign[2]],
				'return' => true
			];
			$campaignSettingArgs[] = [
				'args' => [$campaign[2], 'sendrate', 75.0],
				'return' => true
			];
			$campaignSettingArgs[] = [
				'args' => [$campaign[2], 'status', 'ACTIVE'],
				'return' => true
			];
		}

		\Phake::when($this->fileProcessor)->convertFileToArray($this->filePath)->thenReturn($contents);

		$this->mock_function_param_value(
			'api_campaigns_checknameexists',
			$checkNameExistsParams,
			false
		);

		$this->mock_function_value('api_campaigns_list_all', [$duplicateCampaignId => []]);

		$this->mock_function_param_value(
			'api_campaigns_checkorcreate',
			$createCampaignParams,
			false
		);

		$this->listen_mocked_function('api_targets_add_single');
		$this->mock_function_value('api_targets_add_single', true);

		$this->listen_mocked_function('api_targets_dedupe');
		$this->mock_function_value('api_targets_dedupe', true);

		$this->listen_mocked_function('api_campaigns_setting_set');
		$this->mock_function_value('api_campaigns_setting_set', true);

		$this->mock_function_value('api_data_target_status', ['TOTAL' => 100]);
		$this->strategy->processFile($this->filePath);
		$this->assertListenMockFunction('api_targets_add_single', $addTargetParams);
		$this->assertListenMockFunction('api_targets_dedupe', $dedupeParams);
		$this->assertListenMockFunction('api_campaigns_setting_set', $campaignSettingArgs);
	}
}
