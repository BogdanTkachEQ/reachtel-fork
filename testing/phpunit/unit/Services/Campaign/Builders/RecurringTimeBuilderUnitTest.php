<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign\Builders;

use Doctrine\Common\Collections\ArrayCollection;
use Models\Day;
use Phake;
use Services\Campaign\Builders\RecurringTimeBuilder;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class RecurringTimeBuilderUnitTest
 */
class RecurringTimeBuilderUnitTest extends AbstractPhpunitUnitTest
{
	/** @var RecurringTimeBuilder */
	private $builder;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->builder = new RecurringTimeBuilder();
	}

	/**
	 * @return void
	 */
	public function testGetRecurringTime() {
		$this->assertDefaults();
		$start = Phake::mock(\DateTime::class);
		$end = Phake::mock(\DateTime::class);
		Phake::when($start)->format('H:i:s')->thenReturn('2019-12-20 10:00:00');
		Phake::when($end)->format('H:i:s')->thenReturn('2019-12-20 11:00:00');
		$activeDay1 = Phake::mock(Day::class);
		$activeDay2 = Phake::mock(Day::class);
		$this
			->builder
			->setStartTime($start)
			->setEndTime($end)
			->addActiveDay($activeDay1)
			->addActiveDay($activeDay2);

		$recurringTime = $this->builder->getRecurringTime();
		$this->assertSameEquals($start, $recurringTime->getStartTime());
		$this->assertSameEquals($end, $recurringTime->getEndTime());
		$this->assertInstanceOf(ArrayCollection::class, $recurringTime->getActiveDays());
		$this->assertSameEquals($activeDay1, $recurringTime->getActiveDays()->first());
		$this->assertSameEquals($activeDay2, $recurringTime->getActiveDays()->next());

		$this->builder->reset();
		$this->assertDefaults();
	}

	/**
	 * @return void
	 */
	public function assertDefaults() {
		$recurringTime = $this->builder->getRecurringTime();
		$this->assertNull($recurringTime->getStartTime());
		$this->assertNull($recurringTime->getEndTime());
		$this->assertInstanceOf(ArrayCollection::class, $recurringTime->getActiveDays());
		$this->assertSameEquals(0, $recurringTime->getActiveDays()->count());
	}
}
