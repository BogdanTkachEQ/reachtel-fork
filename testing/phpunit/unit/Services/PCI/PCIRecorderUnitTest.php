<?php
/**
 * PCIRecorderUnitTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\PCI;

use Services\PCI\PCIRecorder;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Unit test for PCIRecorder class
 *
 * @runTestsInSeparateProcesses
 */
class PCIRecorderUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	public function getInstanceData() {
		return [
			'normal instance' => [],
			'auto start NOT match reporting script' => ['scripts/reporting/whatever.php'],
			'auto start match autoload script' => ['scripts/autoload/whatever.php'],
		];
	}

	/**
	 * @dataProvider getInstanceData
	 * @param string $argv
	 * @return void
	 */
	public function testGetInstance($argv = null) {
		if ($argv) {
			$argvBackup = $_SERVER['argv'][0];
			$_SERVER['argv'][0] = $argv;
		}

		$pciRecorder = PCIRecorder::getInstance();
		$this->assertInstanceOf(
			PCIRecorder::class,
			$pciRecorder
		);

		if ($argv) {
			$_SERVER['argv'][0] = $argvBackup;
		}
	}

	/**
	 * @return void
	 */
	public function testDestruct() {
		$this->assertNull(
			PCIRecorder::getInstance()->destruct()
		);
	}

	/**
	 * @return void
	 */
	public function testStart() {
		$this->assertInstanceOf(
			PCIRecorder::class,
			PCIRecorder::getInstance()->start()
		);
	}

	/**
	 * @return void
	 */
	public function testStop() {
		$this->assertInstanceOf(
			PCIRecorder::class,
			PCIRecorder::getInstance()->stop()
		);
	}

	/**
	 * @return void
	 */
	public function testIsStarted() {
		$this->assertFalse(
			PCIRecorder::getInstance()->isStarted()
		);
	}

	/**
	 * @return void
	 */
	public function testIsAutoStarted() {
		$this->assertFalse(
			PCIRecorder::getInstance()->isAutoStarted()
		);

		// start but not auto started
		PCIRecorder::getInstance()->start();
		$this->assertFalse(
			PCIRecorder::getInstance()->isAutoStarted()
		);

		// auto started
		PCIRecorder::getInstance()->start(true);
		$this->assertTrue(
			PCIRecorder::getInstance()->isAutoStarted()
		);
	}

	/**
	 * @return void
	 */
	public function testAddTargetKey() {
		$this->assertInstanceOf(
			PCIRecorder::class,
			PCIRecorder::getInstance()->addTargetKey(1, 2)
		);
	}

	/**
	 * @return void
	 */
	public function testAddMergeData() {
		$this->assertInstanceOf(
			PCIRecorder::class,
			PCIRecorder::getInstance()->addMergeData(1, 2, 3, 4)
		);
	}

	/**
	 * @return void
	 */
	public function testResetRecords() {
		$records = PCIRecorder::getInstance()
			->start()
			->addMergeData(1, 2, 3, 4)
			->resetRecords()
			->getRecords();
		$this->assertInternalType('array', $records);
		$this->assertEmpty($records);
	}

	/**
	 * @return void
	 */
	public function testGetRecords() {
		$this->assertInternalType(
			'array',
			PCIRecorder::getInstance()->getRecords()
		);
		$this->assertEmpty(
			PCIRecorder::getInstance()->getRecords()
		);
	}
}
