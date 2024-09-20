<?php
/**
 * AppConfigTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

use testing\AbstractPhpunitTest;

/**
 * Application Configuration Test
 */
class AppConfigTest extends AbstractPhpunitTest
{
	/**
	 * Test SETTINGS_ID constant is set and numeric
	 *
	 * @return void
	 */
	public function test_setting_id_constant() {
		$this->assertTrue(
			defined('SETTINGS_ID'),
			"Failed asserting that constant SETTINGS_ID is defined."
		);

		$this->assertTrue(
			is_numeric(SETTINGS_ID),
			"Failed asserting that constant SETTINGS_ID is numeric."
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function constants_data() {
		return [
			['APP_ENVIRONMENT', 'phpunit'],
			['APP_PHPUNIT_PATH', dirname(__DIR__)],
			['APP_TESTING_PATH', dirname(dirname(__DIR__))],
			['APP_ROOT_PATH', dirname(dirname(dirname(__DIR__)))],
			['MEMCACHE_DISABLE', 1]
		];
	}

	/**
	 * @dataProvider constants_data
	 * @param string $constant_name
	 * @param mixed  $expected_value
	 * @return void
	 */
	public function test_constants($constant_name, $expected_value) {
		$this->assertTrue(
			defined($constant_name),
			"Failed asserting that constant {$constant_name} is defined."
		);

		$value = constant($constant_name);
		$this->assertEquals(
			$expected_value,
			$value,
			"Failed asserting that constant {$constant_name} value match expected."
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function modules_compiled_and_loaded_data() {
		return [
			['runkit'],
			['mysqli'],
			['mcrypt'],
			['soap'],
			['openssl'],
			['zip'],
			['imap'],
			['mbstring'],
		];
	}

	/**
	 * Test extension loaded
	 *
	 * @dataProvider modules_compiled_and_loaded_data
	 * @param string $extension_name
	 * @return void
	 */
	public function test_modules_compiled_and_loaded($extension_name) {
		$this->assertTrue(
			extension_loaded($extension_name),
			"Failed asserting that extension {$extension_name} is loaded."
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function php_libraries_data() {
		return [
			['StatsD'],
			['PEAR_ErrorStack']
		];
	}

	/**
	 * @dataProvider php_libraries_data
	 * @param string $classname
	 * @return void
	 */
	public function test_php_libraries($classname) {
		$this->assertTrue(class_exists($classname));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function commands_utility_data() {
		return [
			['SoX', 'sox --version', '/SoX v14.\d+.\d+$/i'],
			['wget', 'wget --version', '/^GNU Wget 1.\d+/i'],
			['PHPUnit', APP_ROOT_PATH . '/vendor/bin/phpunit --version', '/^PHPUnit 5.\d.\d/i'],
		];
	}

	/**
	 * @dataProvider commands_utility_data
	 * @param string $utility_name
	 * @param string $command
	 * @param string $pattern
	 * @return void
	 */
	public function test_commands_utility($utility_name, $command, $pattern) {
		$error_message = "{$utility_name} not found or incorrect version.";
		$out = shell_exec($command);
		$this->assertInternalType('string', $out, $error_message);
		$out = trim($out);
		$this->assertRegExp($pattern, $out, $error_message . "\n{$out}\n");
	}
}
