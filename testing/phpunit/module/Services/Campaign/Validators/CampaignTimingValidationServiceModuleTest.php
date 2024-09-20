<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\Campaign\Validators;

use Services\Campaign\Builders\CampaignSettingsDirector;
use Services\Campaign\Validators\CampaignTimingValidationService;
use Services\Container\ContainerAccessor;
use testing\module\AbstractDatabasePhpunitModuleTest;
use testing\module\helpers\CampaignModuleHelper;

/**
 * Class CampaignTimingValidationServiceModuleTest
 */
class CampaignTimingValidationServiceModuleTest extends AbstractDatabasePhpunitModuleTest
{
	use CampaignModuleHelper;

	/**
	 * @expectedException Services\Exceptions\Campaign\Validators\PublicHolidayValidationFailure
	 * @expectedExceptionMessage Public holiday validation failed
	 * @return void
	 */
	public function testIsValidDateTimeThrowsPublicHolidayException() {
		$this->purge_all_campaigns();
		$settings = [
			CAMPAIGN_SETTING_CLASSIFICATION => CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH
		];
		$campaignId = $this->create_new_campaign(null, CAMPAIGN_TYPE_VOICE, null, $settings);
		$campaignSettings = ContainerAccessor::getContainer()
			->get(CampaignSettingsDirector::class)
			->buildCampaignSettings($campaignId);

		ContainerAccessor::getContainer()
			->get(CampaignTimingValidationService::class)
			->isValidDateTime(\DateTime::createFromFormat('d-m-Y', '01-01-2035'), $campaignSettings);
	}

	/**
	 * @return array
	 */
	public function IsValidDateTimeDataProvider() {
		return [
			'when campaign type is sms it bypasses timing descriptor validation' => [
				CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH,
				CAMPAIGN_TYPE_SMS,
				[
					'recurring' => [],
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 04:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 21:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					]
				],
				'Australia/Sydney',
				'17-12-2025 04:30:00',
				true
			],
			'when campaign type is email it bypasses timing descriptor validation' => [
				CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH,
				CAMPAIGN_TYPE_EMAIL,
				[
					'recurring' => [],
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 04:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 21:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					]
				],
				'Australia/Sydney',
				'17-12-2025 04:30:00',
				true
			],
			'when campaign type is wash it bypasses timing descriptor validation' => [
				CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH,
				CAMPAIGN_TYPE_WASH,
				[
					'recurring' => [],
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 04:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 21:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					]
				],
				'Australia/Sydney',
				'17-12-2025 04:30:00',
				true
			],
			'when campaign type is phone' => [
				CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH,
				CAMPAIGN_TYPE_VOICE,
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
				'Australia/Sydney',
				'17-12-2025 10:30:00',
				true
			],
		];
	}

	/**
	 * @dataProvider IsValidDateTimeDataProvider
	 * @param string  $classification
	 * @param string  $type
	 * @param array   $timings
	 * @param string  $timeZone
	 * @param string  $time
	 * @param boolean $expected
	 * @return void
	 */
	public function testIsValidDateTime(
		$classification,
		$type,
		array $timings,
		$timeZone,
		$time,
		$expected
	) {
		$this->purge_all_campaigns();
		$settings = [
			CAMPAIGN_SETTING_CLASSIFICATION => $classification,
			CAMPAIGN_SETTING_TIMEZONE => $timeZone,
			CAMPAIGN_SETTING_TIMING => serialize($timings)
		];
		$campaignId = $this->create_new_campaign(null, $type, null, $settings);
		$campaignSettings = ContainerAccessor::getContainer()
			->get(CampaignSettingsDirector::class)
			->buildCampaignSettings($campaignId);

		$return = ContainerAccessor::getContainer()
			->get(CampaignTimingValidationService::class)
			->isValidDateTime(\DateTime::createFromFormat('d-m-Y H:i:s', $time), $campaignSettings);

		$this->assertSameEquals($expected, $return);
	}

	/**
	 * @return array
	 */
	public function isValidDateTimeTimingRangeExceptionDataProvider() {
		return [
			'when campaign type is sms' => [
				CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT,
				CAMPAIGN_TYPE_SMS,
				[
					'recurring' => [],
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 04:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 21:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					]
				],
				'17-12-2025 03:55:00',
				'Australia/Sydney'
			],
			'when campaign type is sms and classification is non-exempt' => [
				CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH,
				CAMPAIGN_TYPE_SMS,
				[
					'recurring' => [],
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 04:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 21:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					]
				],
				'17-12-2025 03:55:00',
				'Australia/Sydney'
			],
			'when campaign type is sms with recurring times' => [
				CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT,
				CAMPAIGN_TYPE_SMS,
				[
					'recurring' => [
						['starttime' => '04:00:00', 'endtime' => '22:00:00', 'daysofweek' => 31],
						['starttime' => '14:00:00', 'endtime' => '16:00:00', 'daysofweek' => 31]
					],
					'specific' => []
				],
				'17-12-2025 22:30:00',
				'Australia/Sydney'
			],
			'when campaign type is wash' => [
				CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT,
				CAMPAIGN_TYPE_WASH,
				[
					'recurring' => [],
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 04:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 21:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					]
				],
				'17-12-2025 03:55:00',
				'Australia/Sydney'
			],
			'when campaign type is wash and classification is non-exempt' => [
				CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH,
				CAMPAIGN_TYPE_WASH,
				[
					'recurring' => [],
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 04:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 21:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					]
				],
				'17-12-2025 03:55:00',
				'Australia/Sydney'
			],
			'when campaign type is wash with recurring times' => [
				CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT,
				CAMPAIGN_TYPE_WASH,
				[
					'recurring' => [
						['starttime' => '04:00:00', 'endtime' => '22:00:00', 'daysofweek' => 31],
						['starttime' => '14:00:00', 'endtime' => '16:00:00', 'daysofweek' => 31]
					],
					'specific' => []
				],
				'17-12-2025 22:30:00',
				'Australia/Sydney'
			],
			'when campaign type is email' => [
				CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT,
				CAMPAIGN_TYPE_EMAIL,
				[
					'recurring' => [],
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 04:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 21:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					]
				],
				'17-12-2025 03:55:00',
				'Australia/Sydney'
			],
			'when campaign type is email and classification is non-exempt' => [
				CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING,
				CAMPAIGN_TYPE_EMAIL,
				[
					'recurring' => [],
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 04:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 21:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					]
				],
				'17-12-2025 03:55:00',
				'Australia/Sydney'
			],
			'when campaign type is email with recurring times' => [
				CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT,
				CAMPAIGN_TYPE_EMAIL,
				[
					'recurring' => [
						['starttime' => '04:00:00', 'endtime' => '22:00:00', 'daysofweek' => 31],
						['starttime' => '14:00:00', 'endtime' => '16:00:00', 'daysofweek' => 31]
					],
					'specific' => []
				],
				'17-12-2025 22:30:00',
				'Australia/Sydney'
			],
			'when campaign type is phone' => [
				CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT,
				CAMPAIGN_TYPE_VOICE,
				[
					'recurring' => [],
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 04:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 21:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					]
				],
				'17-12-2025 03:55:00',
				'Australia/Sydney'
			],
			'when campaign type is phone and classification is non exempt' => [
				CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING,
				CAMPAIGN_TYPE_VOICE,
				[
					'recurring' => [],
					'specific' => [
						[
							'starttime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 10:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp(),
							'endtime' => \DateTime::createFromFormat(
								'd-m-Y H:i:s',
								'17-12-2025 15:00:00',
								new \DateTimeZone('Australia/Sydney')
							)->getTimestamp()
						]
					]
				],
				'17-12-2025 09:55:00',
				'Australia/Sydney'
			],
			'when campaign type is phone and classification is non exempt with recurring times' => [
				CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING,
				CAMPAIGN_TYPE_VOICE,
				[
					'recurring' => [
						['starttime' => '10:00:00', 'endtime' => '12:00:00', 'daysofweek' => 31],
						['starttime' => '14:00:00', 'endtime' => '16:00:00', 'daysofweek' => 31]
					],
					'specific' => []
				],
				'17-12-2025 13:00:00',
				'Australia/Sydney'
			],
		];
	}

	/**
	 * @expectedException Services\Exceptions\Campaign\Validators\CampaignTimingRangeValidationFailure
	 * @dataProvider isValidDateTimeTimingRangeExceptionDataProvider
	 * @param string $classification
	 * @param string $type
	 * @param array  $timings
	 * @param string $time
	 * @param string $timeZone
	 * @return void
	 */
	public function testIsValidDateTimeThrowsTimingRangeException(
		$classification,
		$type,
		array $timings,
		$time,
		$timeZone
	) {
		$this->purge_all_campaigns();
		$settings = [
			CAMPAIGN_SETTING_CLASSIFICATION => $classification,
			CAMPAIGN_SETTING_TIMEZONE => $timeZone,
			CAMPAIGN_SETTING_TIMING => serialize($timings)
		];
		$campaignId = $this->create_new_campaign(null, $type, null, $settings);
		$campaignSettings = ContainerAccessor::getContainer()
			->get(CampaignSettingsDirector::class)
			->buildCampaignSettings($campaignId);

		$dateTime = \DateTime::createFromFormat('d-m-Y H:i:s', $time, new \DateTimeZone('Australia/Sydney'));
		ContainerAccessor::getContainer()
			->get(CampaignTimingValidationService::class)
			->isValidDateTime($dateTime, $campaignSettings);
	}

	/**
	 * @expectedException Services\Exceptions\Campaign\Validators\TimingRuleValidationFailure
	 * @expectedExceptionMessage Timing rule validation failed
	 * @return void
	 */
	public function testIsValidDateTimeThrowsTimingRuleException() {
		$this->purge_all_campaigns();
		$settings = [
			CAMPAIGN_SETTING_CLASSIFICATION => CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING,
			CAMPAIGN_SETTING_TIMEZONE => 'Australia/Sydney',
			CAMPAIGN_SETTING_TIMING => serialize(
				[
					'recurring' => [
						['starttime' => '03:00:00', 'endtime' => '12:00:00', 'daysofweek' => 31],
						['starttime' => '14:00:00', 'endtime' => '16:00:00', 'daysofweek' => 31]
					],
					'specific' => []
				]
			)
		];
		$campaignId = $this->create_new_campaign(null, CAMPAIGN_TYPE_VOICE, null, $settings);
		$dateTime = \DateTime::createFromFormat('d-m-Y H:i:s', '17-12-2030 04:00:00', new \DateTimeZone('Australia/Sydney'));

		$campaignSettings = ContainerAccessor::getContainer()
			->get(CampaignSettingsDirector::class)
			->buildCampaignSettings($campaignId);

		ContainerAccessor::getContainer()
			->get(CampaignTimingValidationService::class)
			->isValidDateTime($dateTime, $campaignSettings);
	}
}
