<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignRecurringTime;
use Models\Day;
use Phake;
use Services\Validators\RecurringTimesTimingPeriodValidator;

/**
 * Class RecurringTimesTimingPeriodValidatorUnitTest
 */
class RecurringTimesTimingPeriodValidatorUnitTest extends AbstractCampaignTimingPeriodValidatorUnitTest
{
	/** @var RecurringTimesTimingPeriodValidator */
	private $validator;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->validator = new RecurringTimesTimingPeriodValidator($this->periodValidator);
	}

	/**
	 * @return RecurringTimesTimingPeriodValidator
	 */
	protected function getValidator() {
		return $this->validator;
	}

	/**
	 * @return ArrayCollection
	 */
	protected function getCampaignTimingRanges() {
		$recurringTimes = new ArrayCollection(
			[
				Phake::mock(CampaignRecurringTime::class),
				Phake::mock(CampaignRecurringTime::class)
			]
		);
		$this->getValidator()->setRecurringTimes($recurringTimes);
		return $recurringTimes;
	}

	/**
	 * @return void
	 */
	public function testSetRecurringTimes() {
		$this
			->assertSameEquals(
				$this->getValidator(),
				$this->getValidator()->setRecurringTimes(Phake::mock(ArrayCollection::class))
			);
	}

	/**
	 * @return void
	 */
	public function testIsValidIsTrueWhenNoTimingGroupAvaliable() {
		$time = Phake::mock(CampaignRecurringTime::class);
		Phake::when($time)->validate()->thenReturn(true);
		$times = new ArrayCollection();
		$times->add($time);
		$this->getValidator()->setRecurringTimes($times);
		$this->assertTrue($this->getValidator()->isValid());
	}

	/**
	 * @return array
	 */
	public function isValidDataProvider() {
		return [
			'when valid' => [true, true],
			'when invalid' => [false, false]
		];
	}

	/**
	 * @dataProvider isValidDataProvider
	 * @param boolean $periodValidatorReturn
	 * @param boolean $expected
	 * @return void
	 */
	public function testIsValid($periodValidatorReturn, $expected) {
		Phake::when($this->periodValidator)->isValidDateTime(Phake::anyParameters())->thenReturn($periodValidatorReturn);
		$recurringTime = Phake::mock(CampaignRecurringTime::class);
		Phake::when($recurringTime)->validate()->thenReturn(true);
		$start = \DateTime::createFromFormat('H:i:s', '10:15:25');
		$end = \DateTime::createFromFormat('H:i:s', '12:00:00');
		Phake::when($recurringTime)->getStartTime()->thenReturn($start);
		Phake::when($recurringTime)->getEndTime()->thenReturn($end);
		$day1 = Phake::mock(Day::class);
		$day2 = Phake::mock(Day::class);
		$dayName1 = 'MONDAY';
		$dayName2 = 'WEDNESDAY';
		Phake::when($day1)->getDateTimeDayName()->thenReturn($dayName1);
		Phake::when($day2)->getDateTimeDayName()->thenReturn($dayName2);
		Phake::when($recurringTime)->getActiveDays()->thenReturn(new ArrayCollection([$day1, $day2]));

		$recurringTimes = new ArrayCollection([$recurringTime]);

		$this->assertSameEquals(
			$expected,
			$this
				->getValidator()
				->setTimingGroup($this->timingGroup)
				->setRecurringTimes($recurringTimes)
				->isValid()
		);

		Phake::verify(
			$this->periodValidator,
			Phake::times($periodValidatorReturn ? 4 : 1)
		)->isValidDateTime(Phake::captureAll($dateTime));

		Phake::verify($recurringTime)->validate();

		for ($i = 0; $i < 4; $i++) {
			if (!$periodValidatorReturn && $i > 0) {
				break;
			}
			$date = $dateTime[$i];

			$this->assertInstanceOf(\DateTime::class, $date);

			$this->assertSameEquals(($i === 0 || $i === 1) ? 'Monday' : 'Wednesday', $date->format('l'));
			$this->assertSameEquals(
				($i === 0 || $i === 2) ? '10:15:25' : '12:00:00',
				$date->format('H:i:s')
			);
		}
	}
}
