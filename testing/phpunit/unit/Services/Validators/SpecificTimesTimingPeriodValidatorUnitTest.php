<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignSpecificTime;
use Phake;
use Services\Validators\SpecificTimesTimingPeriodValidator;

/**
 * Class SpecificTimesTimingPeriodValidatorUnitTest
 */
class SpecificTimesTimingPeriodValidatorUnitTest extends AbstractCampaignTimingPeriodValidatorUnitTest
{
	/** @var SpecificTimesTimingPeriodValidator */
	private $validator;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->validator = new SpecificTimesTimingPeriodValidator($this->periodValidator);
	}

	/**
	 * @return SpecificTimesTimingPeriodValidator
	 */
	protected function getValidator() {
		return $this->validator;
	}

	/** @return ArrayCollection */
	protected function getCampaignTimingRanges() {
		$specificTimes = new ArrayCollection(
			[
				Phake::mock(CampaignSpecificTime::class),
				Phake::mock(CampaignSpecificTime::class)
			]
		);
		$this->getValidator()->setSpecificTimes($specificTimes);
		return $specificTimes;
	}

	/**
	 * @return void
	 */
	public function testSetSpecificTimes() {
		$this->assertSameEquals($this->getValidator(), $this->getValidator()->setSpecificTimes(new ArrayCollection()));
	}

	/**
	 * @return void
	 */
	public function testIsValidIsTrueWhenNoTimingGroupAvaliable() {
		$time = Phake::mock(CampaignSpecificTime::class);
		Phake::when($time)->validate()->thenReturn(true);
		Phake::when($time)->getStatus()->thenReturn(CampaignSpecificTime::STATUS_CURRENT);
		$times = new ArrayCollection();
		$times->add($time);
		$this->getValidator()->setSpecificTimes($times);
		$this->assertTrue($this->getValidator()->isValid());
	}

	/**
	 * @return array
	 */
	public function isValidDataProvider() {
		return [
			'when period validator returns valid and status is past' => [-1, true, true],
			'when period validator returns invalid and status is past' => [-1, false, true],
			'when period validator returns valid and status is not past' => [0, true, true],
			'when period validator returns invalid and status is not past' => [1, false, false],
		];
	}

	/**
	 * @dataProvider isValidDataProvider
	 * @param integer $status
	 * @param boolean $periodValidatorReturn
	 * @param boolean $expected
	 * @return null
	 */
	public function testIsValid($status, $periodValidatorReturn, $expected) {
		$specificTime = Phake::mock(CampaignSpecificTime::class);
		$start = \DateTime::createFromFormat('d-m-Y H:i:s', '20-12-2019 10:00:00');
		$end = \DateTime::createFromFormat('d-m-Y H:i:s', '20-12-2019 17:00:00');
		Phake::when($specificTime)->getStartDateTime()->thenReturn($start);
		Phake::when($specificTime)->getEndDateTime()->thenReturn($end);
		Phake::when($specificTime)->validate()->thenReturn(true);
		Phake::when($specificTime)->getStatus()->thenReturn($status);
		Phake::when($this->periodValidator)->isValidDateTime(Phake::anyParameters())->thenReturn($periodValidatorReturn);
		$this->assertSameEquals(
			$expected,
			$this
				->getValidator()
				->setTimingGroup($this->timingGroup)
				->setSpecificTimes(new ArrayCollection([$specificTime]))->isValid()
		);

		if ($status === -1) {
			Phake::verify($this->periodValidator, Phake::times(0))->isValidDateTime(Phake::anyParameters());
			return null;
		}

		if (!$periodValidatorReturn) {
			Phake::verify($this->periodValidator, Phake::times(1))
				->isValidDateTime(Phake::capture($date));

			$this->assertInstanceOf(\DateTime::class, $date);
			$this->assertSameEquals($start, $date);

			return null;
		}

		Phake::verify($this->periodValidator, Phake::times(2))
			->isValidDateTime(Phake::captureAll($date));

		Phake::verify($specificTime)->validate();
		$this->assertInstanceOf(\DateTime::class, $date[0]);
		$this->assertSameEquals($start, $date[0]);

		$this->assertInstanceOf(\DateTime::class, $date[1]);
		$this->assertSameEquals($end, $date[1]);
	}
}
