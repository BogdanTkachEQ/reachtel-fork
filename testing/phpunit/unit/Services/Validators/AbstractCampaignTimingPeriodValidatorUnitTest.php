<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators;

use Doctrine\Common\Collections\ArrayCollection;
use Models\Entities\TimingGroup;
use Phake;
use Services\Validators\AbstractCampaignTimingPeriodValidator;
use Services\Validators\TimingPeriodValidator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class AbstractCampaignTimingPeriodValidator
 */
abstract class AbstractCampaignTimingPeriodValidatorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var TimingPeriodValidator | \Phake_IMock */
	protected $periodValidator;

	/** @var TimingGroup | \Phake_IMock */
	protected $timingGroup;

	/**
	 * @return AbstractCampaignTimingPeriodValidator
	 */
	abstract protected function getValidator();

	/** @return ArrayCollection */
	abstract protected function getCampaignTimingRanges();

	/**
	 * @return void
	 */
	public function setUp() {
		$this->periodValidator = Phake::mock(TimingPeriodValidator::class);
		$this->timingGroup = Phake::mock(TimingGroup::class);
	}

	/**
	 * @return void
	 */
	public function testSetTimingGroup() {
		$this->assertInstanceOf(
			get_class($this->getValidator()),
			$this->getValidator()->setTimingGroup($this->timingGroup)
		);
		Phake::verify($this->periodValidator)->setTimingGroup(Phake::capture($timingGroup));
		$this->assertSameEquals($this->timingGroup, $timingGroup);
	}

	/**
	 * @return void
	 */
	public function testIsValidDateTimeReturnsFalseWhenPeriodValidatorReturnsFalse() {
		$dateTime = new \DateTime();
		Phake::when($this->periodValidator)->isValidDateTime($dateTime)->thenReturn(false);
		$this->getValidator()->setTimingGroup($this->timingGroup);
		$this->assertFalse($this->getValidator()->isValidDateTime($dateTime));
	}

	/**
	 * @return void
	 */
	public function testIsValidDateTime() {
		$dateTime = new \DateTime();
		Phake::when($this->periodValidator)->isValidDateTime($dateTime)->thenReturn(true);
		Phake::when($this->getCampaignTimingRanges()->get(1))->isValidDateTime($dateTime)->thenReturn(true);
		$this->getValidator()->setTimingGroup($this->timingGroup);
		$this->assertTrue($this->getValidator()->isValidDateTime($dateTime));
	}

	/**
	 * @expectedException Services\Exceptions\Campaign\Validators\CampaignTimingRangeValidationFailure
	 * @expectedExceptionMessage Time passed is out of range
	 * @return void
	 */
	public function testIsValidDateTimeThrowsException() {
		$dateTime = new \DateTime();
		Phake::when($this->periodValidator)->isValidDateTime($dateTime)->thenReturn(true);
		foreach ($this->getCampaignTimingRanges() as $campaignTimingRange) {
			Phake::when($campaignTimingRange)->isValidDateTime($dateTime)->thenReturn(false);
		}

		$this->getValidator()->setTimingGroup($this->timingGroup);
		$this->getValidator()->isValidDateTime($dateTime);
	}
}
