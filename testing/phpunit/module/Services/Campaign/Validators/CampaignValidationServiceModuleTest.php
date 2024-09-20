<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\Campaign\Validators;

use Exception;
use Services\Campaign\Builders\CampaignSettingsDirector;
use Services\Campaign\Validators\CompositeCampaignValidationService;
use Services\Container\ContainerAccessor;
use testing\module\AbstractDatabasePhpunitModuleTest;
use testing\module\helpers\CampaignModuleHelper;

/**
 * Class CampaignValidationServiceModuleTest
 */
class CampaignValidationServiceModuleTest extends AbstractDatabasePhpunitModuleTest
{
	use CampaignModuleHelper;

	/**
	 * @var CompositeCampaignValidationService
	 */
	private $validationService;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->validationService = ContainerAccessor::getContainer()
			->get(CompositeCampaignValidationService::class);
	}

	/**
	 * @return array
	 */
	public function campaignValidationDataProvider() {
		return [
			'when there are no timings' => [
				CAMPAIGN_TYPE_VOICE,
				CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING,
				'Australia/Sydney',
				false,
				null,
			],
			'when recurring time is outside acma' => [
				CAMPAIGN_TYPE_VOICE,
				CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING,
				'Australia/Sydney',
				true,
				[
					'recurring' => [
						['starttime' => '10:00:00', 'endtime' => '13:00:00', 'daysofweek' => 31],
						['starttime' => '14:00:00', 'endtime' => '21:00:00', 'daysofweek' => 31]
					],
					'specific' => []
				],
			],
			'when recurring times are inside acma' => [
				CAMPAIGN_TYPE_VOICE,
				CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH,
				'Australia/Sydney',
				false,
				[
					'recurring' => [
						['starttime' => '10:00:00', 'endtime' => '13:00:00', 'daysofweek' => 31],
						['starttime' => '14:00:00', 'endtime' => '16:00:00', 'daysofweek' => 31]
					],
					'specific' => []
				],
			],
			'when specific times are outside acma' => [
				CAMPAIGN_TYPE_VOICE,
				CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING,
				'Australia/Sydney',
				true,
				[
					'recurring' => [],
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 08:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 16:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					]
				],
			],
			'when specific times are inside acma' => [
				CAMPAIGN_TYPE_VOICE,
				CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING,
				'Australia/Sydney',
				false,
				[
					'recurring' => [],
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 09:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 16:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					]
				],
			],
			'when caller id is withheld' => [
				CAMPAIGN_TYPE_VOICE,
				CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH,
				'Australia/Sydney',
				true,
				[],
				true
			],
			'when caller id is withheld but classification is exempt' => [
				CAMPAIGN_TYPE_VOICE,
				CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT,
				'Australia/Sydney',
				false,
				[],
				true
			],
			'when campaign classification is exempt but timings don\'t comply with acma' => [
				CAMPAIGN_TYPE_VOICE,
				CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT,
				'Australia/Sydney',
				false,
				[
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 08:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 16:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					],
					'recurring' => [
						['starttime' => '10:00:00', 'endtime' => '13:00:00', 'daysofweek' => 31],
						['starttime' => '14:00:00', 'endtime' => '21:00:00', 'daysofweek' => 31]
					],
				],
			],
			'when times are outside acma, classification not excempt but country is not Australia' => [
				CAMPAIGN_TYPE_VOICE,
				CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING,
				'Australia/Sydney',
				false,
				[
					'recurring' => [],
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 08:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 16:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					]
				],
				false,
				'SG'
			]
		];
	}

	/**
	 * @dataProvider campaignValidationDataProvider
	 * @param string  $type
	 * @param string  $classification
	 * @param string  $campaignTimeZone
	 * @param boolean $expected
	 * @param mixed   $timing
	 * @param boolean $callerIdWithHeld
	 * @param string  $country
	 * @return void
	 */
	public function testIfCampaignRulesAreViolated(
		$type,
		$classification,
		$campaignTimeZone,
		$expected,
		$timing = null,
		$callerIdWithHeld = false,
		$country = null
	) {
		$settings = [
			CAMPAIGN_SETTING_CLASSIFICATION => $classification,
			CAMPAIGN_SETTING_TYPE => $type,
			CAMPAIGN_SETTING_TIMEZONE => $campaignTimeZone,
			CAMPAIGN_SETTING_WITHHOLD_CALLER_ID => $callerIdWithHeld ? 'on' : 'off'
		];

		if ($timing) {
			$settings['timing'] = serialize($timing);
		}

		if ($country) {
			$settings[CAMPAIGN_SETTING_REGION] = $country;
		}

		$campaignId = $this->create_new_campaign(null, $type, null, $settings);
		$campaignSettings = ContainerAccessor::getContainer()
			->get(CampaignSettingsDirector::class)
			->buildCampaignSettings($campaignId);

		$this->assertSameEquals($expected, $this->validationService->violatesValidationRules($campaignSettings));
	}
}
