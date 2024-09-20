<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators;

use Models\Entities\TimingGroup;
use Models\Entities\TimingPeriod;
use Phake;
use Services\Validators\TimingPeriodValidator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class TimingPeriodValidatorUnitTest
 */
class TimingPeriodValidatorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var TimingGroup | \Phake_IMock */
	private $timingGroup;

	/** @var TimingPeriodValidator */
	private $validator;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->timingGroup = Phake::mock(TimingGroup::class);
		$this->validator = new TimingPeriodValidator();
	}

	/**
	 * @return void
	 */
	public function testSetTimingGroup() {
		$this->assertSameEquals($this->validator, $this->validator->setTimingGroup($this->timingGroup));
	}

	/**
	 * @expectedException Services\Exceptions\Validators\TimingPeriodException
	 * @expectedExceptionMessage Missing timing group when attempting to validate
	 * @return void
	 */
	public function testIsValidThrowsTimingPeriodException() {
		$this->validator->isValidDateTime(new \DateTime());
	}

	/**
	 * @return array
	 */
	public function isValidDataProvider() {
		return [
			'when date time is valid for period' => [\Phake::mock(TimingPeriod::class), true, true],
			'when date time is invalid for period' => [Phake::mock(TimingPeriod::class), false, false],
			'when there are no timing periods it is not valid' => [null, null, false]
		];
	}

	/**
	 * @dataProvider isValidDataProvider
	 * @param \Phake_IMock $period
	 * @param mixed        $isValidDatetimeForPeriod
	 * @param boolean      $expected
	 * @return void
	 */
	public function testIsValidDateTime(\Phake_IMock $period = null, $isValidDatetimeForPeriod, $expected) {
		$dateTime = new \DateTime();
		Phake::when($this->timingGroup)->getTimingPeriodByDateTime($dateTime)->thenReturn($period);

		if ($period) {
			Phake::when($period)->inPeriod($dateTime)->thenReturn($isValidDatetimeForPeriod);
		}

		$this->assertSameEquals(
			$expected,
			$this->validator->setTimingGroup($this->timingGroup)->isValidDateTime($dateTime)
		);
	}
}
