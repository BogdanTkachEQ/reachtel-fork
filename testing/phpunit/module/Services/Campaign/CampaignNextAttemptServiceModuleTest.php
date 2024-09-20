<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\Campaign;

use Services\Campaign\Builders\CampaignSettingsDirector;
use Services\Campaign\CampaignNextAttemptService;
use Services\Container\ContainerAccessor;
use testing\module\AbstractDatabasePhpunitModuleTest;
use testing\module\helpers\CampaignModuleHelper;

/**
 * Class CampaignNextAttemptServiceModuleTest
 */
class CampaignNextAttemptServiceModuleTest extends AbstractDatabasePhpunitModuleTest
{
	use CampaignModuleHelper;

	/**
	 * @return array
	 */
	public function validNextAttemptDateTimeDataProvider() {
		return [
			'when classification is exempt' => [
				CAMPAIGN_TYPE_VOICE,
				CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT,
				'Australia/Brisbane',
				\DateTime::createFromFormat('d-m-Y H:i:s', '17-12-2019 18:59:00', new \DateTimeZone('Australia/Sydney')),
				new \DateInterval('PT3H'),
				\DateTime::createFromFormat('d-m-Y H:i:s', '17-12-2019 21:59:00', new \DateTimeZone('Australia/Sydney')),
			],
			'when type is not voice' => [
				CAMPAIGN_TYPE_WASH,
				CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING,
				'Australia/Brisbane',
				\DateTime::createFromFormat('d-m-Y H:i:s', '17-12-2019 18:59:00', new \DateTimeZone('Australia/Sydney')),
				new \DateInterval('PT3H'),
				\DateTime::createFromFormat('d-m-Y H:i:s', '17-12-2019 21:59:00', new \DateTimeZone('Australia/Sydney')),
			],
			'when type is voice and classification is not exempt' => [
				CAMPAIGN_TYPE_VOICE,
				CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING,
				'Australia/Brisbane',
				\DateTime::createFromFormat('d-m-Y H:i:s', '17-12-2019 17:59:00', new \DateTimeZone('Australia/Brisbane')),
				new \DateInterval('PT3H'),
				\DateTime::createFromFormat('d-m-Y H:i:s', '18-12-2019 09:00:00', new \DateTimeZone('Australia/Brisbane')),
			],
			'when there is a public holiday in between' => [
				CAMPAIGN_TYPE_VOICE,
				CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING,
				'Australia/Brisbane',
				\DateTime::createFromFormat('d-m-Y H:i:s', '31-12-2019 17:59:00', new \DateTimeZone('Australia/Brisbane')),
				new \DateInterval('PT3H'),
				\DateTime::createFromFormat('d-m-Y H:i:s', '02-01-2020 09:00:00', new \DateTimeZone('Australia/Brisbane')),
			],
			'when it is before timing period start' => [
				CAMPAIGN_TYPE_VOICE,
				CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING,
				'Australia/Brisbane',
				\DateTime::createFromFormat('d-m-Y H:i:s', '17-12-2019 07:00:00', new \DateTimeZone('Australia/Brisbane')),
				new \DateInterval('PT1H'),
				\DateTime::createFromFormat('d-m-Y H:i:s', '17-12-2019 09:00:00', new \DateTimeZone('Australia/Brisbane')),
			]
		];
	}

	/**
	 * @dataProvider validNextAttemptDateTimeDataProvider
	 * @param string        $type
	 * @param string        $classification
	 * @param string        $campaignTimeZone
	 * @param \DateTime     $currentTime
	 * @param \DateInterval $nextAttemptInterval
	 * @param \DateTime     $expected
	 * @return void
	 */
	public function testGetValidNextAttemptDateTime(
		$type,
		$classification,
		$campaignTimeZone,
		\DateTime $currentTime,
		\DateInterval $nextAttemptInterval,
		\DateTime $expected
	) {
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
		$settings = [
			CAMPAIGN_SETTING_CLASSIFICATION => $classification,
			CAMPAIGN_SETTING_TYPE => $type,
			CAMPAIGN_SETTING_TIMEZONE => $campaignTimeZone
		];

		$campaignId = $this->create_new_campaign(null, $type, null, $settings);
		$campaignSettings = ContainerAccessor::getContainer()
			->get(CampaignSettingsDirector::class)
			->buildCampaignSettings($campaignId);

		$actual = ContainerAccessor::getContainer()
			->get(CampaignNextAttemptService::class)
			->getValidNextAttemptDateTime($campaignSettings, $currentTime, $nextAttemptInterval);

		$this->assertSameEquals($expected->format('d-m-Y H:i:s'), $actual->format('d-m-Y H:i:s'));
		$this->purge_all_campaigns();
	}
}
