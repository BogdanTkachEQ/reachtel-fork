<?php
/**
 * ConfigReaderUnitTest
 * Unit test for ConfigReader class
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services;

use Services\ConfigReader;
use Symfony\Component\Yaml\Parser;

/**
 * ConfigReaderUnitTest
 */
class ConfigReaderUnitTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var Parser
	 */
	private $yamlMock;

	/**
	 * setUp for each test
	 *
	 * @return void
	 */
	public function setUp() {
		$this->yamlMock = $this->createMock(Parser::class);

		$this->yamlMock->method('parseFile')
			->will(
				$this->returnCallback(
					function($path) {
						if (strpos($path, 'success')) {
							return ['a' => 1];
						}

						return false;
					}
				)
			);
	}

	/**
	 * test ConfigReader::getInstance()
	 *
	 * @return void
	 */
	public function testGetInstance() {
		// test without mock
		$this->assertInstanceOf(
			ConfigReader::class,
			ConfigReader::getInstance()
		);
		// test with mock
		$this->assertInstanceOf(
			ConfigReader::class,
			ConfigReader::getInstance($this->yamlMock)
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function getConfigData() {
		$root = APP_ROOT_PATH;

		return [
			// failure
			['failure', false],
			// success
			['success', ['a' => 1]]
		];
	}

	/**
	 * test ConfigReader::getConfig()
	 *
	 * @dataProvider getConfigData
	 * @param string $configType
	 * @param mixed  $expected
	 * @return void
	 */
	public function testGetConfig($configType, $expected) {
		$configReader = ConfigReader::getInstance($this->yamlMock);
		$this->assertInstanceOf(ConfigReader::class, $configReader);

		$this->assertEquals(
			$expected,
			$configReader->getConfig($configType)
		);
	}
}
