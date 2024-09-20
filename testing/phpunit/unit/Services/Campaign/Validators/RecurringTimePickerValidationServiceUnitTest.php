<?php
/**
 * @author		phillip.berry@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign\Validators;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignSettings;
use Models\Entities\TimingGroup;
use Services\Campaign\Builders\RecurringTimeBuilder;
use Services\Campaign\Builders\RecurringTimesDirector;
use Services\Campaign\CampaignTimingAccessor;
use Services\Campaign\Validators\RecurringTimePickerValidationService;
use Services\Validators\RecurringTimesTimingPeriodValidator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class WeekendRunControllerUnitTest
 */
class RecurringTimePickerValidationServiceUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	public function validatorProvider() {
		return [
			[true, true],
			[false, false],
		];
	}

	/**
	 * @dataProvider validatorProvider
	 * @param boolean $isRecurringValid
	 * @param boolean $expected
	 * @return void
	 */
	public function testIsValid($isRecurringValid, $expected) {
		$campaignSettings = \Phake::mock(CampaignSettings::class);
		\Phake::when($campaignSettings)->getRecurringTimes()->thenReturn(new ArrayCollection());

		$recurringPeriod = \Phake::mock(RecurringTimesTimingPeriodValidator::class);
		\Phake::when($recurringPeriod)->isValid()->thenReturn($isRecurringValid);
		\Phake::when($recurringPeriod)->setRecurringTimes(\Phake::anyParameters())->thenReturn($recurringPeriod);

		$director = new RecurringTimesDirector(new RecurringTimeBuilder());
		$accessor = \Phake::mock(CampaignTimingAccessor::class);

		$validator = new RecurringTimePickerValidationService($recurringPeriod, $director, $accessor);
		$this->assertEquals($expected, $validator->isValid($campaignSettings, ['timezone' => "Australia/Brisbane"]));
	}

	/**
	 * @return void
	 */
	public function testIsValidWithTimingGroup() {
		$campaignSettings = \Phake::mock(CampaignSettings::class);
		\Phake::when($campaignSettings)->getRecurringTimes()->thenReturn(new ArrayCollection());

		$recurringPeriod = \Phake::mock(RecurringTimesTimingPeriodValidator::class);
		\Phake::when($recurringPeriod)->isValid()->thenReturn(true);
		\Phake::when($recurringPeriod)->setRecurringTimes(\Phake::anyParameters())->thenReturn($recurringPeriod);

		$director = new RecurringTimesDirector(new RecurringTimeBuilder());
		$accessor = \Phake::mock(CampaignTimingAccessor::class);
		\Phake::when($accessor)->getTimingGroup(\Phake::anyParameters())->thenReturn(\Phake::mock(TimingGroup::class));
		$validator = new RecurringTimePickerValidationService($recurringPeriod, $director, $accessor);
		$this->assertEquals(true, $validator->isValid($campaignSettings, ['timezone' => "Australia/Brisbane"]));
		\Phake::verify($recurringPeriod, \Phake::times(1))->setTimingGroup(\Phake::anyParameters());
	}
}
