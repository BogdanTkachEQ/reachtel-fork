<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models;

use DateTime;
use Models\CampaignSpecificTime;
use Phake;
use testing\unit\AbstractModelTestCase;

/**
 * Class CampaignSpecificTime
 */
class CampaignSpecificTimeUnitTest extends AbstractModelTestCase
{
	/**
	 * @return array
	 */
	protected function getData() {
		return [
			'startDateTime' => Phake::mock(DateTime::class),
			'endDateTime' => Phake::mock(DateTime::class),
			'status' => 3
		];
	}

	/**
	 * @return CampaignSpecificTime
	 */
	protected function getObject() {
		return new CampaignSpecificTime();
	}

	/**
	 * @expectedException Services\Exceptions\Validators\InvalidSpecificTimeException
	 * @expectedExceptionMessage Start and end date can not be empty
	 * @return void
	 */
	public function testValidateThrowsExceptionWhenStartOrEndTimesEmpty() {
		$specific = $this->getObject();
		$specific->validate();
	}

	/**
	 * @expectedException Services\Exceptions\Validators\InvalidSpecificTimeException
	 * @expectedExceptionMessage Start date can not be greater than or equal to end date
	 * @return void
	 */
	public function testValidateThrowsExceptionWhenStartIsGreaterThanEnd() {
		$specific = $this->getObject();
		$specific->setStartDateTime(DateTime::createFromFormat('H:i:s', '11:00:00'));
		$specific->setEndDateTime(DateTime::createFromFormat('H:i:s', '10:00:00'));
		$specific->validate();
	}

	/**
	 * @expectedException Services\Exceptions\Validators\InvalidSpecificTimeException
	 * @expectedExceptionMessage Start date can not be greater than or equal to end date
	 * @return void
	 */
	public function testValidateThrowsExceptionWhenStartAndEndAreSame() {
		$specific = $this->getObject();
		$specific->setStartDateTime(DateTime::createFromFormat('H:i:s', '11:00:00'));
		$specific->setEndDateTime(DateTime::createFromFormat('H:i:s', '11:00:00'));
		$specific->validate();
	}

	/**
	 * @expectedException Services\Exceptions\Validators\InvalidSpecificTimeException
	 * @expectedExceptionMessage Start & end dates are not on the same calendar day
	 * @return void
	 */
	public function testValidateThrowsExceptionWhenStartAndEndAreOnSeparateDays() {
		$specific = $this->getObject();
		$specific->setStartDateTime(DateTime::createFromFormat('d-m-Y H:i:s', '10-12-2019 10:00:00'));
		$specific->setEndDateTime(DateTime::createFromFormat('d-m-Y H:i:s', '11-12-2019 11:00:00'));
		$specific->validate();
	}

	/**
	 * @return void
	 */
	public function testValidate() {
		$specific = $this->getObject();
		$specific->setStartDateTime(DateTime::createFromFormat('H:i:s', '10:00:00'));
		$specific->setEndDateTime(DateTime::createFromFormat('H:i:s', '11:00:00'));
		$this->assertTrue($specific->validate());
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
			'when equal to end' => ['10:00:00', '14:00:00', '14:00:00', false],
		];
	}

	/**
	 * @dataProvider isValidDateTimeDataProvider
	 * @param string  $start
	 * @param string  $end
	 * @param string  $time
	 * @param boolean $expected
	 * @return void
	 */
	public function testIsValidDateTime($start, $end, $time, $expected) {
		$specific = $this->getObject();
		$specific->setStartDateTime(\DateTime::createFromFormat('H:i:s', $start));
		$specific->setEndDateTime(\DateTime::createFromFormat('H:i:s', $end));
		$this->assertSameEquals(
			$expected,
			$specific->isValidDateTime(\DateTime::createFromFormat('H:i:s', $time))
		);
	}
}
