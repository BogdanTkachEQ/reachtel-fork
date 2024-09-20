<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators;

use Services\Validators\DailyRunController;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class DailyRunControllerUnitTest
 */
class DailyRunControllerUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @var DailyRunController
	 */
	protected $runController;

	/**
	 * @var \DateTime
	 */
	protected $dateTime;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->dateTime = \Phake::mock(\DateTime::class);
		$this->runController = new DailyRunController($this->dateTime);
	}

	/**
	 * @return void
	 */
	public function testSetStopDay() {
		self::assertInstanceOf(DailyRunController::class, $this->runController->setStopDay(4));
	}

	/**
	 * @return void
	 */
	public function testGetStopReason() {
		$stopReason = 'Test Reason';
		$this->runController->setStopReason($stopReason);
		self::assertSameEquals($stopReason, $this->runController->getStopReason());
	}

	/**
	 * @return void
	 */
	public function testStopRun() {
		$this->runController->setStopDay(3)->setStopDay(5);
		\Phake::when($this->dateTime)->format('w')->thenReturn(5);
		self::assertTrue($this->runController->stopRun());
		\Phake::when($this->dateTime)->format('w')->thenReturn(3);
		self::assertTrue($this->runController->stopRun());
		\Phake::when($this->dateTime)->format('w')->thenReturn(2);
		self::assertFalse($this->runController->stopRun());
	}
}
