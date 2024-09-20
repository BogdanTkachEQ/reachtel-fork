<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators;

use Services\Validators\PublicHolidayRunController;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class PublicHolidayRunControllerUnitTest
 */
class PublicHolidayRunControllerUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @var string
	 */
	private $region;

	/**
	 * @var \DateTime | \Phake_IMock
	 */
	private $dateTime;

	/**
	 * @var PublicHolidayRunController
	 */
	private $runController;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->region = 'AU';
		$this->dateTime = \Phake::mock(\DateTime::class);
		$this->runController = new PublicHolidayRunController($this->dateTime, $this->region);
	}

	/**
	 * @return void
	 */
	public function testStopRun() {
		$holidayTimestamp = 1234556;
		$this->mock_function_param_value(
			'api_misc_ispublicholiday',
			[
				['params' => [$this->region, $holidayTimestamp], 'return' => true]
			],
			false
		);

		\Phake::when($this->dateTime)->getTimestamp()->thenReturn($holidayTimestamp);
		self::assertTrue($this->runController->stopRun());

		\Phake::when($this->dateTime)->getTimestamp()->thenReturn(4545454);
		self::assertFalse($this->runController->stopRun());
	}

	/**
	 * @return void
	 */
	public function testGetStopReason() {
		self::assertSameEquals('Public Holiday', $this->runController->getStopReason());
	}
}
