<?php
/**
 * @author		phillip.berry@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignSettings;
use Models\Entities\TimingGroup;
use Services\Exceptions\Campaign\Validators\CampaignTimingRangeValidationFailure;
use Services\Exceptions\Validators\ValidatorRuntimeException;
use Services\Validators\CampaignTimingPeriodSettingsValidator;
use Services\Validators\RecurringTimesTimingPeriodValidator;
use Services\Validators\SpecificTimesTimingPeriodValidator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class WeekendRunControllerUnitTest
 */
class CampaignTimingPeriodSettingsValidatorUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	public function validatorProvider() {
		return [
			[true, true, true],
			[true, false, false],
			[false, true, false],
			[false, false, false],
		];
	}

	/**
	 * @dataProvider validatorProvider
	 * @param boolean $isRecurringValid
	 * @param boolean $isSpecificValid
	 * @param boolean $expected
	 * @return void
	 */
	public function testIsValid($isRecurringValid, $isSpecificValid, $expected) {
		$campaignSettings = \Phake::mock(CampaignSettings::class);
		\Phake::when($campaignSettings)->getRecurringTimes()->thenReturn(new ArrayCollection());
		\Phake::when($campaignSettings)->getSpecificTimes()->thenReturn(new ArrayCollection());

		$recurringPeriod = \Phake::mock(RecurringTimesTimingPeriodValidator::class);
		$specificPeriod = \Phake::mock(SpecificTimesTimingPeriodValidator::class);
		\Phake::when($recurringPeriod)->isValid()->thenReturn($isRecurringValid);
		\Phake::when($specificPeriod)->isValid()->thenReturn($isSpecificValid);
		\Phake::when($recurringPeriod)->setRecurringTimes(\Phake::anyParameters())->thenReturn($recurringPeriod);
		\Phake::when($specificPeriod)->setSpecificTimes(\Phake::anyParameters())->thenReturn($specificPeriod);

		$validator = new CampaignTimingPeriodSettingsValidator($recurringPeriod, $specificPeriod);
		$validator->setCampaignSettings($campaignSettings);
		$this->assertEquals($expected, $validator->isValid());
	}

	/**
	 * @return void
	 */
	public function testSetTimingGroup() {
		$recurringPeriod = \Phake::mock(RecurringTimesTimingPeriodValidator::class);
		$specificPeriod = \Phake::mock(SpecificTimesTimingPeriodValidator::class);

		$validator = new CampaignTimingPeriodSettingsValidator($recurringPeriod, $specificPeriod);
		$timingGroup = \Phake::mock(TimingGroup::class);
		$this->assertSameEquals($validator, $validator->setTimingGroup($timingGroup));
		\Phake::verify($recurringPeriod)->setTimingGroup($timingGroup);
		\Phake::verify($specificPeriod)->setTimingGroup($timingGroup);
	}

	/**
	 * @expectedException Services\Exceptions\Validators\ValidatorRuntimeException
	 * @expectedExceptionMessage Campaign settings not set
	 * @return void
	 */
	public function testIsValidDateTimeThrowsExceptionWhenCampaignSettingsNotSet() {
		$recurringPeriod = \Phake::mock(RecurringTimesTimingPeriodValidator::class);
		$specificPeriod = \Phake::mock(SpecificTimesTimingPeriodValidator::class);

		$validator = new CampaignTimingPeriodSettingsValidator($recurringPeriod, $specificPeriod);
		$validator->isValidDateTime(new \DateTime());
	}

	/**
	 * @return void
	 */
	public function testIsValidDateTimeWhenSpecificIsValid() {
		$specificTimes = \Phake::mock(ArrayCollection::class);
		$recurringTimes = \Phake::mock(ArrayCollection::class);
		$recurringPeriod = \Phake::mock(RecurringTimesTimingPeriodValidator::class);
		$specificPeriod = \Phake::mock(SpecificTimesTimingPeriodValidator::class);
		$campaignSettings = \Phake::mock(CampaignSettings::class);
		$dateTime = \Phake::mock(\DateTime::class);

		\Phake::when($campaignSettings)->getSpecificTimes()->thenReturn($specificTimes);
		\Phake::when($campaignSettings)->getRecurringTimes()->thenReturn($recurringTimes);
		\Phake::when($recurringPeriod)->setRecurringTimes($recurringTimes)->thenReturnSelf();
		\Phake::when($specificPeriod)->setSpecificTimes($specificTimes)->thenReturnSelf();

		\Phake::when($specificPeriod)->isValidDateTime(\Phake::anyParameters())->thenReturn(true);
		$validator = new CampaignTimingPeriodSettingsValidator($recurringPeriod, $specificPeriod);
		$validator->setCampaignSettings($campaignSettings);
		$this->assertTrue($validator->isValidDateTime($dateTime));
		\Phake::verify($specificPeriod, \Phake::times(1))->isValidDateTime(\Phake::anyParameters());
		\Phake::verify($recurringPeriod, \Phake::times(0))->isValidDateTime(\Phake::anyParameters());
	}

	/**
	 * @return void
	 */
	public function testIsValidDateTimeWhenSpecificIsInValidAndRecurringIsValid() {
		$specificTimes = \Phake::mock(ArrayCollection::class);
		$recurringTimes = \Phake::mock(ArrayCollection::class);
		$recurringPeriod = \Phake::mock(RecurringTimesTimingPeriodValidator::class);
		$specificPeriod = \Phake::mock(SpecificTimesTimingPeriodValidator::class);
		$campaignSettings = \Phake::mock(CampaignSettings::class);
		$dateTime = \Phake::mock(\DateTime::class);

		\Phake::when($campaignSettings)->getSpecificTimes()->thenReturn($specificTimes);
		\Phake::when($campaignSettings)->getRecurringTimes()->thenReturn($recurringTimes);
		\Phake::when($recurringPeriod)->setRecurringTimes($recurringTimes)->thenReturnSelf();
		\Phake::when($specificPeriod)->setSpecificTimes($specificTimes)->thenReturnSelf();

		\Phake::when($specificPeriod)->isValidDateTime(\Phake::anyParameters())->thenThrow(new CampaignTimingRangeValidationFailure());
		\Phake::when($recurringPeriod)->isValidDateTime(\Phake::anyParameters())->thenReturn(true);
		$validator = new CampaignTimingPeriodSettingsValidator($recurringPeriod, $specificPeriod);
		$validator->setCampaignSettings($campaignSettings);
		$this->assertTrue($validator->isValidDateTime($dateTime));
		\Phake::verify($recurringPeriod, \Phake::times(1))->isValidDateTime(\Phake::anyParameters());
		\Phake::verify($specificPeriod, \Phake::times(1))->isValidDateTime(\Phake::anyParameters());
	}

	/**
	 * @expectedException Services\Exceptions\Campaign\Validators\CampaignTimingRangeValidationFailure
	 * @return void
	 */
	public function testIsValidDateTimeThrowsTimingRangeException() {
		$specificTimes = \Phake::mock(ArrayCollection::class);
		$recurringTimes = \Phake::mock(ArrayCollection::class);
		$recurringPeriod = \Phake::mock(RecurringTimesTimingPeriodValidator::class);
		$specificPeriod = \Phake::mock(SpecificTimesTimingPeriodValidator::class);
		$campaignSettings = \Phake::mock(CampaignSettings::class);
		$dateTime = \Phake::mock(\DateTime::class);

		\Phake::when($campaignSettings)->getSpecificTimes()->thenReturn($specificTimes);
		\Phake::when($campaignSettings)->getRecurringTimes()->thenReturn($recurringTimes);
		\Phake::when($recurringPeriod)->setRecurringTimes($recurringTimes)->thenReturnSelf();
		\Phake::when($specificPeriod)->setSpecificTimes($specificTimes)->thenReturnSelf();

		\Phake::when($specificPeriod)->isValidDateTime(\Phake::anyParameters())->thenThrow(new CampaignTimingRangeValidationFailure());
		\Phake::when($recurringPeriod)->isValidDateTime(\Phake::anyParameters())->thenThrow(new CampaignTimingRangeValidationFailure());
		$validator = new CampaignTimingPeriodSettingsValidator($recurringPeriod, $specificPeriod);
		$validator->setCampaignSettings($campaignSettings);
		$validator->isValidDateTime($dateTime);
	}
}
