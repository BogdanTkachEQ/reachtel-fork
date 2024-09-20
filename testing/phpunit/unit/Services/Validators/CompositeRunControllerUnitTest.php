<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators;

use Services\Validators\CompositeRunController;
use Services\Validators\Interfaces\RunControllerInterface;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class CompositeRunControllerUnitTest
 */
class CompositeRunControllerUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @var CompositeRunController
	 */
	private $compositeRunController;

	/**
	 * @var string
	 */
	private $stopReason;

	/**
	 * @var RunControllerInterface
	 */
	private $runController1;

	/**
	 * @var RunControllerInterface
	 */
	private $runController2;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->compositeRunController = new CompositeRunController();
		$this->runController1 = \Phake::mock(RunControllerInterface::class);
		$this->runController2 = \Phake::mock(RunControllerInterface::class);
		$this->stopReason = 'Test stop reason';
		\Phake::when($this->runController1)->stopRun()->thenReturn(false);
		\Phake::when($this->runController2)->stopRun()->thenReturn(true);
		\Phake::when($this->runController2)->getStopReason()->thenReturn($this->stopReason);
		$this
			->compositeRunController
			->addRunController($this->runController1)
			->addRunController($this->runController2);
	}

	/**
	 * @return void
	 */
	public function testStopRun() {
		self::assertTrue($this->compositeRunController->stopRun());
		\Phake::verify($this->runController1)->stopRun();
		\Phake::verify($this->runController2)->stopRun();
	}

	/**
	 * @return void
	 */
	public function testGetStopReason() {
		$this->compositeRunController->stopRun();
		self::assertSameEquals($this->stopReason, $this->compositeRunController->getStopReason());
	}
}
