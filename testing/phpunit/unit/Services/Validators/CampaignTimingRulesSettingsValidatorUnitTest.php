<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators;

use Models\CampaignSettings;
use Models\Entities\TimingGroup;
use Phake;
use Services\Campaign\CampaignTimingAccessor;
use Services\Validators\CampaignTimingPeriodSettingsValidator;
use Services\Validators\CampaignTimingRulesSettingsValidator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class CampaignTimingRulesSettingsValidatorUnitTest
 */
class CampaignTimingRulesSettingsValidatorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var CampaignTimingAccessor | \Phake_IMock */
	private $timingAccessor;

	/** @var CampaignTimingPeriodSettingsValidator | \Phake_IMock */
	private $timingPeriodValidator;

	/** @var CampaignSettings | \Phake_IMock */
	private $campaignSettings;

	/** @var CampaignTimingRulesSettingsValidator */
	private $validator;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->timingAccessor = Phake::mock(CampaignTimingAccessor::class);
		$this->timingPeriodValidator = Phake::mock(CampaignTimingPeriodSettingsValidator::class);
		$this->campaignSettings = Phake::mock(CampaignSettings::class);
		$this->validator = new CampaignTimingRulesSettingsValidator(
			$this->timingAccessor,
			$this->timingPeriodValidator
		);
	}

	/**
	 * @expectedException Services\Exceptions\Validators\CampaignTimingRuleValidatorException
	 * @expectedExceptionMessage Campaign settings not set for validation
	 * @return void
	 */
	public function testIsValidThrowsExceptionWhenCampaignSettingsNotSet() {
		$this->validator->isValid();
	}

	/**
	 * @expectedException Services\Exceptions\Validators\CampaignTimingRuleValidatorException
	 * @expectedExceptionMessage Campaign settings not set for validation
	 * @return void
	 */
	public function testIsValidDateTimeThrowsExceptionWhenCampaignSettingsNotSet() {
		$this->validator->isValidDateTime(new \DateTime());
	}

	/**
	 * @return void
	 */
	public function testIsValidReturnsTrueWhenNoTimingGroupFound() {
		Phake::when($this->timingAccessor)->getTimingGroup($this->campaignSettings)->thenReturn(null);
		$this->assertTrue($this->validator->setCampaignSettings($this->campaignSettings)->isValid());
		Phake::verify($this->timingPeriodValidator, Phake::times(0))->setTimingGroup(Phake::anyParameters());
	}

	/**
	 * @return array
	 */
	public function isValidDataProvider() {
		return [
			'valid' => [true, true],
			'in valid' => [false, false]
		];
	}

	/**
	 * @dataProvider isValidDataProvider
	 * @param boolean $timingPeriodValidatorReturn
	 * @param boolean $expected
	 * @return void
	 */
	public function testIsValid($timingPeriodValidatorReturn, $expected) {
		$timingGroup = Phake::mock(TimingGroup::class);
		Phake::when($this->timingAccessor)->getTimingGroup($this->campaignSettings)->thenReturn($timingGroup);
		Phake::when($this->timingPeriodValidator)->isValid()->thenReturn($timingPeriodValidatorReturn);
		$this->assertSameEquals($expected, $this->validator->setCampaignSettings($this->campaignSettings)->isValid());
		Phake::verify($this->timingPeriodValidator)->setTimingGroup($timingGroup);
	}

	/**
	 * @return array
	 */
	public function isValidDateTimeDataProvider() {
		return [
			'valid' => [Phake::mock(TimingGroup::class), true, true],
			'valid with no timingGroup' => [null, true, true],
			'in valid' => [Phake::mock(TimingGroup::class), false, false],
			'in valid with no timing group' => [null, false, false]
		];
	}

	/**
	 * @dataProvider isValidDateTimeDataProvider
	 * @param TimingGroup|null $timingGroup
	 * @param boolean          $timingPeriodValidatorReturn
	 * @param boolean          $expected
	 * @return void
	 */
	public function testIsValidDateTime(TimingGroup $timingGroup = null, $timingPeriodValidatorReturn, $expected) {
		$timeZone = Phake::mock(\DateTimeZone::class);
		$dateTime = new \DateTime();
		Phake::when($this->timingAccessor)->getTimingGroup($this->campaignSettings)->thenReturn($timingGroup);
		Phake::when($this->campaignSettings)->getTimeZone()->thenReturn($timeZone);
		Phake::when($this->timingPeriodValidator)
			->isValidDateTime(Phake::capture($newDateTime))
			->thenReturn($timingPeriodValidatorReturn);

		$this->assertSameEquals(
			$expected,
			$this->validator->setCampaignSettings($this->campaignSettings)->isValidDateTime($dateTime)
		);

		Phake::verify($this->timingPeriodValidator, Phake::times(is_null($timingGroup) ? 0 : 1))->setTimingGroup($timingGroup);
	}
}
