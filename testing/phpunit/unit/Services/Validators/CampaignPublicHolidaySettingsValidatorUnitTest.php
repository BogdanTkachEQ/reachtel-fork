<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignSettings;
use Models\CampaignSpecificTime;
use Models\Entities\Region;
use Services\Campaign\Classification\CampaignClassificationEnum;
use Services\Exceptions\Validators\ValidatorRuntimeException;
use Services\Utils\PublicHolidayChecker;
use Services\Validators\CampaignPublicHolidaySettingsValidator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class WeekendRunControllerUnitTest
 */
class CampaignPublicHolidaySettingsValidatorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var PublicHolidayChecker | \Phake_IMock */
	private $holidayChecker;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->holidayChecker = \Phake::mock(PublicHolidayChecker::class);
	}

	/**
	 * @return array
	 */
	public function validatorProvider() {
		return [
			[CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH(), false, true],
			[CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH(), true, false],
			[CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING(), false, true],
			[CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING(), true, false],
			[CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT(), true, true],
			[CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT(), true, true],
		];
	}

	/**
	 * @expectedException Services\Exceptions\Validators\ValidatorRuntimeException
	 * @expectedExceptionMessage Campaign classification not found
	 * @return void
	 */
	public function testEmptyValidator() {
		$campaignSettings = \Phake::mock(CampaignSettings::class);
		$validator = new CampaignPublicHolidaySettingsValidator($this->holidayChecker);
		$validator->setCampaignSettings($campaignSettings);
		$this->assertTrue($validator->isValid());
	}

	/**
	 * @return void
	 */
	public function testExemptValidator() {
		$campaignSettings = \Phake::mock(CampaignSettings::class);
		$validator = new CampaignPublicHolidaySettingsValidator($this->holidayChecker);
		\Phake::when($campaignSettings)->getClassificationEnum()->thenReturn(CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT());
		$validator->setCampaignSettings($campaignSettings);
		$this->assertTrue($validator->isValid());
	}

	/**
	 * @return void
	 */
	public function testResearchValidator() {
		$campaignSettings = \Phake::mock(CampaignSettings::class);
		$region = \Phake::mock(Region::class);
		$dateTime = new \DateTime();
		\Phake::when($campaignSettings)->getClassificationEnum()->thenReturn(CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH());
		\Phake::when($campaignSettings)->getRegion()->thenReturn($region);

		$time = \Phake::mock(CampaignSpecificTime::class);
		\Phake::when($time)->validate()->thenReturn(true);
		\Phake::when($time)->getStartDateTime()->thenReturn($dateTime);
		\Phake::when($time)->getStatus()->thenReturn(1);
		\Phake::when($campaignSettings)->getSpecificTimes()->thenReturn(new ArrayCollection([$time]));
		\Phake::when($this->holidayChecker)->isPublicHoliday($dateTime, $region)->thenReturn(false);
		$validator = new CampaignPublicHolidaySettingsValidator($this->holidayChecker);
		$validator->setCampaignSettings($campaignSettings);
		$this->assertTrue($validator->isValid());

		\Phake::when($this->holidayChecker)->isPublicHoliday($dateTime, $region)->thenReturn(true);
		$validator = new CampaignPublicHolidaySettingsValidator($this->holidayChecker);
		$validator->setCampaignSettings($campaignSettings);
		$this->assertFalse($validator->isValid());
	}
}
