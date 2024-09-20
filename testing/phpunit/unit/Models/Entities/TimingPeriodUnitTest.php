<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models\Entities;

use DateTime;
use Models\Day;
use Models\Entities\TimingGroup;
use Models\Entities\TimingPeriod;
use Phake;
use testing\unit\AbstractModelTestCase;

/**
 * Class TimingPeriodUnitTest
 */
class TimingPeriodUnitTest extends AbstractModelTestCase
{
	/**
	 * @return array
	 */
	protected function getData() {
		return [
			'id' => 767,
			'day' => Phake::mock(Day::class),
			'start' => Phake::mock(\DateTime::class),
			'end' => Phake::mock(\DateTime::class),
			'timingGroup' => Phake::mock(TimingGroup::class)
		];
	}

	/**
	 * @return mixed
	 */
	protected function getObject() {
		return new TimingPeriod();
	}

	/**
	 * @return void
	 */
	public function testGetStartByDate() {
		$period = new TimingPeriod();
		$date = DateTime::createFromFormat('d-m-Y H:i:s', '01-12-2019 00:00:00');
		$date->setTime('00', '00', '00');
		$this->assertNull($period->getStartByDate($date));

		$period->setStart(DateTime::createFromFormat('d-m-Y H:i:s', '10-12-2019 10:15:25'));

		$this->assertInstanceOf(DateTime::class, $period->getStartByDate($date));
		$this->assertSameEquals(
			'01-12-2019 10:15:25',
			$period->getStartByDate($date)->format('d-m-Y H:i:s')
		);
	}

	/**
	 * @return void
	 */
	public function testGetEndByDate() {
		$period = new TimingPeriod();
		$date = DateTime::createFromFormat('d-m-Y H:i:s', '01-12-2019 00:00:00');
		$date->setTime('00', '00', '00');
		$this->assertNull($period->getEndByDate($date));

		$period->setEnd(DateTime::createFromFormat('d-m-Y H:i:s', '10-12-2019 10:15:25'));

		$this->assertInstanceOf(DateTime::class, $period->getEndByDate($date));
		$this->assertSameEquals(
			'01-12-2019 10:15:25',
			$period->getEndByDate($date)->format('d-m-Y H:i:s')
		);
	}

	/**
	 * @return array
	 */
	public function inPeriodDataProvider() {
		return [
			['01-12-2019 10:10:20', '05:00:00', '11:00:00', true],
			['01-12-2019 10:10:20', '05:00:00', '09:00:00', false],
			['01-12-2019 10:10:20', '10:10:20', '11:00:00', true],
			['01-12-2019 10:10:20', '05:00:00', '10:10:20', true],
			['01-12-2019 10:10:20', '11:00:00', '14:10:20', false]
		];
	}

	/**
	 * @dataProvider inPeriodDataProvider
	 * @param string  $date
	 * @param string  $start
	 * @param string  $end
	 * @param boolean $expected
	 * @return void
	 */
	public function testinPeriod($date, $start, $end, $expected) {
		$dateTime = DateTime::createFromFormat('d-m-Y H:i:s', $date);
		$period = new TimingPeriod();
		$period->setStart(DateTime::createFromFormat('H:i:s', $start));
		$period->setEnd(DateTime::createFromFormat('H:i:s', $end));
		$this->assertSameEquals($expected, $period->inPeriod($dateTime));
	}
}
