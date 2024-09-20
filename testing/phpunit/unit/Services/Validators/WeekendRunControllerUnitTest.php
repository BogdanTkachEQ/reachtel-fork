<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators;

use Services\Validators\WeekendRunController;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class WeekendRunControllerUnitTest
 */
class WeekendRunControllerUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @var \DateTime
	 */
	private $dateTime;

	/**
	 * @var WeekendRunController
	 */
	private $runController;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->dateTime = \Phake::mock(\DateTime::class);
		$this->runController = new WeekendRunController($this->dateTime);
	}

	/**
	 * @return array
	 */
	public function stopRunDataProvider() {
		return [
			[1, false],
			[2, false],
			[3, false],
			[4, false],
			[5, false],
			[6, true],
			[7, true],
		];
	}

	/**
	 * @dataProvider stopRunDataProvider
	 * @param integer $day
	 * @param boolean $expected
	 * @return void
	 */
	public function testStopRun($day, $expected) {
		\Phake::when($this->dateTime)->format('w')->thenReturn($day);
		self::assertSameEquals($expected, $this->runController->stopRun());
		self::assertSameEquals('Weekend', $this->runController->getStopReason());
	}
}
