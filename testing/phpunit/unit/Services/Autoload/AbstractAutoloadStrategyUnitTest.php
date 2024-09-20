<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Autoload;

use Services\Autoload\AbstractAutoloadStrategy;
use Services\Autoload\AutoloadLogger;
use testing\AbstractPhpunitTest;

/**
 * Class AbstractAutoloadStrategyUnitTest
 */
abstract class AbstractAutoloadStrategyUnitTest extends AbstractPhpunitTest
{
	/**
	 * @var AbstractAutoloadStrategy
	 */
	protected $strategy;

	/**
	 * @var string
	 */
	protected $filePath;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->filePath = '/tmp/test.csv';
		$this->strategy = $this->getStrategy();
	}

	/**
	 * @return AbstractAutoloadStrategy
	 */
	abstract protected function getStrategy();

	/**
	 * @return void
	 */
	public function testSetLogger() {
		$logger = $this
			->getMockBuilder(AutoloadLogger::class)
			->disableOriginalConstructor()
			->getMock();

		$this
			->strategy
			->setLogger($logger);

		$this->assertSameEquals($logger, $this->strategy->getLogger());
	}
}
