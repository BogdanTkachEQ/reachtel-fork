<?php
/**
 * AbstractCoverageTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\coverage;

use Devsdmf\Annotations\Reader;
use LogicException;
use RuntimeException;
use SimpleXMLElement;
use testing\AbstractPhpunitTest;

/**
 * Abstract Coverage Test
 */
abstract class AbstractCoverageTest extends AbstractPhpunitTest
{
	const ENV_VAR_FILE_FILTER_NAME = 'PHPUNIT_COVERAGE_FILE_FILTER';
	const ENV_VAR_FILE_START_FILTER_NAME = 'PHPUNIT_COVERAGE_FILE_START_FILTER';
	const ENV_VAR_FUNCTION_FILTER_NAME = 'PHPUNIT_COVERAGE_FUNCTION_FILTER';
	const MORPHEUS_NAMING_PATTERN = '/^api_([a-z_]+)/';
	const COMMENT_END = "\n */";
	const COMMENT_COVERAGE_REGEXP = '/\s*\s@(testCoverage)\s+([^\n]+)\s*\n/';

	/**
	 * @var array
	 */
	private $testTypes = ['unit', 'module', 'helpers'];

	/**
	 * @var array
	 */
	protected $groups = [];

	/**
	 * @var string
	 */
	protected static $coveragePath;

	/**
	 * @return array
	 */
	abstract public function getTestGroups();

	/**
	 * @param SimpleXMLElement $xml
	 * @return mixed
	 */
	abstract protected function getXMLRootDirectory(SimpleXMLElement $xml);

	/**
	 * @return void
	 * @throws RuntimeException If Xdebug PHP extention is not loaded.
	 */
	public static function setUpBeforeClass() {
		// check xdebug is loaded
		$extensions = get_loaded_extensions();
		if (!in_array('xdebug', $extensions)) {
			throw new RuntimeException("Xdebug PHP extention is not loaded");
		}

		$type = static::TEST_TYPE;
		self::$coveragePath = sys_get_temp_dir() . "/phpunit/{$type}/" . uniqid();
		if (!is_dir(self::$coveragePath)) {
			mkdir(self::$coveragePath, 0777, true);
		}

		// make sure we remove the code coverage on error, exit() or CTRL-C
		register_shutdown_function([__CLASS__, 'tearDownAfterClass']);
		pcntl_signal(SIGINT, [__CLASS__, 'tearDownAfterClass']);
	}

	/**
	 * @param mixed $exit
	 * @return void
	 */
	public static function tearDownAfterClass($exit = false) {
		if (is_dir(self::$coveragePath)) {
			exec("rm -rf " . self::$coveragePath);
		}

		if ($exit) {
			exit;
		}
	}

	/**
	 * @dataProvider getTestGroups
	 * @param string $file
	 * @param string $group
	 * @return void
	 */
	public function test_check_coverage($file, $group) {
		$this->runTestCoverage($file, $group);
		$this->assertXML($file, $group);
	}

	/**
	 * @return array
	 * @throws LogicException If empty results.
	 */
	protected function getApiGroups() {
		if (!$this->groups) {
			$file_filter = getenv(self::ENV_VAR_FILE_FILTER_NAME);
			$function_filter = getenv(self::ENV_VAR_FUNCTION_FILTER_NAME);
			$file_start_filter = getenv(self::ENV_VAR_FILE_START_FILTER_NAME);

			// define a file / functions map
			$functionsMap = [];
			$reader = new Reader();
			foreach (get_defined_functions()['user'] as $function) {
				$r = new \ReflectionFunction($function);
				$reader = new Reader();

				if (preg_match(self::MORPHEUS_NAMING_PATTERN, basename($r->getFileName()))
					&& is_null($reader->getAnnotation($r, 'codeCoverageIgnore'))) {
					$functionsMap[$r->getFileName()][] = $function;
				}
			}

			foreach (glob(APP_ROOT_PATH . '/*.php') as $file) {
				// morpheus files only
				if (!preg_match(self::MORPHEUS_NAMING_PATTERN, basename($file))) {
					continue;
				}

				// apply file filter
				if ($file_filter && $file_filter != basename($file)) {
					continue;
				}

				// apply start file filter
				if ($file_start_filter) {
					if (!isset($this->_file_start_filter) && $file_start_filter != basename($file)) {
						continue;
					}
					$this->_file_start_filter = true;
				}

				// file exists
				$this->assertFileExists($file);

				// Get file comments
				$content = file_get_contents($file);
				$content = substr($content, 0, strpos($content, self::COMMENT_END) + strlen(self::COMMENT_END));

				if (preg_match_all(self::COMMENT_COVERAGE_REGEXP, $content, $matches)) {
					$this->assertArrayHasKey(
						$file,
						$functionsMap,
						"File - Function Map error for file '{$file}'"
					);

					$annotations = array_combine($matches[1], $matches[2]);
					foreach ($functionsMap[$file] as $function) {
						// apply function filter
						if ($function_filter && $function_filter != $function) {
							continue;
						}

						$this->groups[] = [$file, $function];
					}
				}
			}

			if (!$this->groups) {
				throw new LogicException("No files found for file filter = '{$file_filter}' and function filter = '{$function_filter}'");
			}
		}

		return $this->groups;
	}

	/**
	 * @param string $filePath
	 * @param string $group
	 * @param string $coverageType
	 * @return void
	 */
	protected function runTestCoverage($filePath, $group, $coverageType = 'xml') {
		$type = static::TEST_TYPE;

		$this->assertContains(
			$type,
			$this->testTypes,
			"Invalid PHPUnit type '{$type}'"
		);

		$this->assertContains(
			$coverageType,
			['xml', 'html'],
			"Invalid coverage type '{$coverageType}'"
		);

		$phpBinPath = APP_ROOT_PATH . '/vendor/bin/phpunit';
		$this->assertFileExists(
			$phpBinPath,
			"phpunit bin not found"
		);

		$testFilePath = $this->getTestFile($filePath, $type);

		$coveragePath = self::$coveragePath;
		$cmd = sprintf(
			'%s -c%s/phpunit.xml --group=%s --coverage-%s=%s %s',
			$phpBinPath,
			APP_PHPUNIT_PATH,
			$group,
			$coverageType,
			$coveragePath,
			$testFilePath
		);

		exec($cmd, $out);

		$this->assertRegExp(
			"/^Generating code coverage report in (PHPUnit XML|HTML) format ... done$/",
			end($out),
			"Failed {$coverageType} code coverage for group {$group}: \n" .  implode("\n", $out)
		);
	}

	/**
	 * @param string $filePath
	 * @param string $group
	 * @return void
	 */
	protected function assertXML($filePath, $group) {
		$isUnitTest = ('unit' == static::TEST_TYPE);
		$fileName = basename($filePath);
		$error = "ERROR for test file {$fileName}:\n";

		// Parse index file
		$path = self::$coveragePath . '/index.xml';
		$this->assertFileExists($path);
		$directory = $this->getXMLRootDirectory(simplexml_load_file($path));

		foreach ($directory->file as $xmlFile) {
			// Api file should have executed test only to itself
			$totals = (array) $xmlFile->totals->lines;
			$totals = $totals['@attributes'];
			$xmlFile = (array) $xmlFile;
			$xmlFileName = $xmlFile['@attributes']['name'];
			$sameFile = ($xmlFileName == $fileName);

			// Different files should not have executed test at all
			$this->assertTrue(
				($isUnitTest ? ($sameFile || $totals['executed'] == 0) : (!$sameFile || $totals['executed'] > 0)),
				"{$error}{$xmlFileName} file should " . ($isUnitTest ? 'not ' : '') . "have executed test for test {$group}"
			);

			if ($sameFile) {
				$path = self::$coveragePath . '/' . $xmlFile['@attributes']['href'];
				$this->assertFileExists($path);
				$file = (array) simplexml_load_file($path)->file;

				if (isset($file['function'])) {
					foreach ($file['function'] as $function) {
						$function = (array) $function;
						$function = $function['@attributes'];
						$sameFunction = ($function['name'] == $group);

						$this->assertTrue(
							(!$sameFunction || ($function['executed'] > 0 && $function['executed'] == $function['executable'])),
							"{$error}Function {$function['name']}() incomplete code coverage.\n" . var_export($function, true)
						);

						$this->assertTrue(
							(!$isUnitTest || $sameFunction || $function['executed'] == 0),
							"{$error}Function {$function['name']}() file called during test {$group}.\n" . var_export($function, true)
						);
					}
				} elseif (isset($file['trait'])) {
					foreach ($file['trait']->method as $method) {
						$method = (array) $method;
						$method = $method['@attributes'];
						$sameMethod = ($method['name'] == $group);

						$this->assertTrue(
							(!$sameMethod || ($method['coverage'] == 100)),
							"{$error}Method {$method['name']}() incomplete code coverage.\n" . var_export($method, true)
						);
					}
				}
			}
		}
	}

	/**
	 * @param string $file
	 * @return string
	 * @throws LogicException If test file can not be found.
	 */
	protected function getTestFile($file) {
		$type = static::TEST_TYPE;
		$this->assertFileExists(
			$file,
			"Api file not found '{$file}'"
		);
		$this->assertContains(
			$type,
			$this->testTypes,
			"Invalid test type '{$type}'"
		);
		$file = basename($file);

		if (preg_match(self::MORPHEUS_NAMING_PATTERN, $file, $matches)) {
			$testFile = sprintf(
				'%s/%s/Api%s%sTest.php',
				APP_PHPUNIT_PATH,
				strtolower($type),
				ucfirst($matches[1]),
				ucfirst($type)
			);

			return $testFile;
		}

		throw new LogicException("{$type} test file can not be found for {$file}");
	}
}
