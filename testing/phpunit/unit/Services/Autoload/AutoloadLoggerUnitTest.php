<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Autoload;

use Services\Autoload\AutoloadLogger;
use testing\AbstractPhpunitTest;

/**
 * Class AutoloadLoggerUnitTest
 */
class AutoloadLoggerUnitTest extends AbstractPhpunitTest
{
	/**
	 * @return void
	 */
	public function testAddLogs() {
		$logger = new AutoloadLogger();
		$logs = [
			'Test log 1',
			'Test log 2',
			'Test log 3'
		];

		foreach ($logs as $log) {
			$logger->addLog($log);
		}

		$this->assertSameEquals($logs, $logger->getLogs());
		$expected = "Test log 1\nTest log 2\nTest log 3";

		$this->assertSameEquals($expected, $logger->flush());
	}
}
