<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Models\Day;
use Models\Entities\TimingGroup;
use Models\Entities\TimingPeriod;
use Phake;
use testing\unit\AbstractModelTestCase;

/**
 * Class TimingGroupUnitTest
 */
class TimingGroupUnitTest extends AbstractModelTestCase
{
	/**
	 * @return array
	 */
	protected function getData() {
		return [
			'id' => 656,
			'name' => 'test timing group',
			'timingPeriods' => Phake::mock(ArrayCollection::class),
			'regions' => Phake::mock(ArrayCollection::class)
		];
	}

	/**
	 * @return TimingGroup
	 */
	protected function getObject() {
		return new TimingGroup();
	}

	/**
	 * @return void
	 */
	public function testGetTimingPeriodByDateTime() {
		$group = $this->getObject();
		$dateTime = new \DateTime();
		$timingPeriod1 = Phake::mock(TimingPeriod::class);
		$timingPeriod2 = Phake::mock(TimingPeriod::class);
		$timingPeriod3 = Phake::mock(TimingPeriod::class);

		$mockDay = Phake::mock(Day::class);
		Phake::when($mockDay)->is($mockDay)->thenReturn(false);
		Phake::when($timingPeriod2)->getDay()->thenReturn(Day::byDateTime($dateTime));
		Phake::when($timingPeriod1)->getDay()->thenReturn($mockDay);
		Phake::when($timingPeriod3)->getDay()->thenReturn($mockDay);
		$periods = new ArrayCollection();
		$periods->add($timingPeriod1);
		$periods->add($timingPeriod2);
		$periods->add($timingPeriod3);

		$group->setTimingPeriods($periods);
		$this->assertSameEquals($timingPeriod2, $group->getTimingPeriodByDateTime($dateTime));
	}
}
