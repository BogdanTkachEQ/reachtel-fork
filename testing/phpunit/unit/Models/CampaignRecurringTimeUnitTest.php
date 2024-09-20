<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignRecurringTime;
use Models\Day;
use Phake;
use testing\unit\AbstractModelTestCase;

/**
 * Class CampaignRecurringTimeUnitTest
 */
class CampaignRecurringTimeUnitTest extends AbstractModelTestCase
{
	/**
	 * @return array
	 */
	protected function getData() {
		return [
			'startTime' => Phake::mock(\DateTime::class),
			'endTime' => Phake::mock(\DateTime::class),
			'activeDays' => Phake::mock(ArrayCollection::class)
		];
	}

	/**
	 * @return CampaignRecurringTime
	 */
	protected function getObject() {
		return new CampaignRecurringTime();
	}

	/**
	 * @expectedException Services\Exceptions\Validators\InvalidRecurringTimeException
	 * @expectedExceptionMessage Start time and end time can not be empty
	 * @return void
	 */
	public function testValidateThrowsExceptionWhenStartOrEndTimeNotSet() {
		$recurring = $this->getObject();
		$recurring->validate();
	}

	/**
	 * @expectedException Services\Exceptions\Validators\InvalidRecurringTimeException
	 * @expectedExceptionMessage Start time can not be greater than or equal to end time
	 * @return void
	 */
	public function testValidateThrowsExceptionWhenStartGreaterThanEnd() {
		$recurring = $this->getObject();
		$recurring->setStartTime(\DateTime::createFromFormat('H:i:s', '11:00:00'));
		$recurring->setEndTime(\DateTime::createFromFormat('H:i:s', '10:00:00'));
		$recurring->validate();
	}

	/**
	 * @expectedException Services\Exceptions\Validators\InvalidRecurringTimeException
	 * @expectedExceptionMessage Start time can not be greater than or equal to end time
	 * @return void
	 */
	public function testValidateThrowsExceptionWhenStartEndAreSame() {
		$recurring = $this->getObject();
		$recurring->setStartTime(\DateTime::createFromFormat('H:i:s', '11:00:00'));
		$recurring->setEndTime(\DateTime::createFromFormat('H:i:s', '11:00:00'));
		$recurring->validate();
	}

	/**
	 * @return void
	 */
	public function testValidate() {
		$recurring = $this->getObject();
		$recurring->setStartTime(\DateTime::createFromFormat('H:i:s', '10:00:00'));
		$recurring->setEndTime(\DateTime::createFromFormat('H:i:s', '11:00:00'));
		$this->assertTrue($recurring->validate());
	}

	/**
	 * @return void
	 */
	public function testGetActiveDaysByDefault() {
		$this->assertInstanceOf(ArrayCollection::class, $this->getObject()->getActiveDays());
	}

	/**
	 * @expectedException Services\Exceptions\Validators\InvalidRecurringTimeException
	 * @expectedExceptionMessage Start time and end time can not be empty
	 * @return void
	 */
	public function testIsValidDateTimeThrowsExceptionWhenStartOrEndTimeNotSet() {
		$recurring = $this->getObject();
		$recurring->isValidDateTime(new \DateTime());
	}

	/**
	 * @expectedException Services\Exceptions\Validators\InvalidRecurringTimeException
	 * @expectedExceptionMessage Start time can not be greater than or equal to end time
	 * @return void
	 */
	public function testIsValidDateTimeThrowsExceptionWhenStartGreaterThanEnd() {
		$recurring = $this->getObject();
		$recurring->setStartTime(\DateTime::createFromFormat('H:i:s', '11:00:00'));
		$recurring->setEndTime(\DateTime::createFromFormat('H:i:s', '10:00:00'));
		$recurring->isValidDateTime(new \DateTime());
	}

	/**
	 * @expectedException Services\Exceptions\Validators\InvalidRecurringTimeException
	 * @expectedExceptionMessage Start time can not be greater than or equal to end time
	 * @return void
	 */
	public function testIsValidDateTimeThrowsExceptionWhenStartEndAreSame() {
		$recurring = $this->getObject();
		$recurring->setStartTime(\DateTime::createFromFormat('H:i:s', '11:00:00'));
		$recurring->setEndTime(\DateTime::createFromFormat('H:i:s', '11:00:00'));
		$recurring->isValidDateTime(new \DateTime());
	}

	/**
	 * @return void
	 */
	public function testIsValidDateTimeFailsWhenNotInActiveDays() {
		$recurring = $this->getObject();
		$recurring->setStartTime(\DateTime::createFromFormat('H:i:s', '10:00:00'));
		$recurring->setEndTime(\DateTime::createFromFormat('H:i:s', '11:00:00'));
		$recurring->setActiveDays(new ArrayCollection([Day::TUESDAY(), Day::FRIDAY(), Day::SATURDAY()]));
		$this->assertFalse($recurring->isValidDateTime(new \DateTime('TUESDAY 08:00:00')));
	}

	/**
	 * @return array
	 */
	public function isValidDateTimeDataProvider() {
		return [
			'when with in range' => ['10:00:00', '14:00:00', '12:00:00', true],
			'when lesser than start' => ['10:00:00', '14:00:00', '09:00:00', false],
			'when greater than end' => ['10:00:00', '14:00:00', '15:30:00', false],
			'when equal to start' => ['10:00:00', '14:00:00', '10:00:00', true],
			'when equal to end' => ['10:00:00', '14:00:00', '14:00:00', true],
		];
	}

	/**
	 * @dataProvider  isValidDateTimeDataProvider
	 * @param string  $start
	 * @param string  $end
	 * @param string  $time
	 * @param boolean $expected
	 * @return void
	 */
	public function testIsValidDateTime($start, $end, $time, $expected) {
		$recurring = $this->getObject();
		$recurring->setStartTime(\DateTime::createFromFormat('H:i:s', $start));
		$recurring->setEndTime(\DateTime::createFromFormat('H:i:s', $end));
		$recurring->setActiveDays(new ArrayCollection([Day::TUESDAY(), Day::FRIDAY(), Day::SATURDAY()]));
		$this->assertSameEquals($expected, $recurring->isValidDateTime(new \DateTime('TUESDAY ' . $time)));
	}
}
