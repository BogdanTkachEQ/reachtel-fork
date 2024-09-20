<?php
/**
 * AbstractPhpunitScriptTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\script;

use PHPUnit_Framework_TestCase;

/**
 * Abstract Script Test class
 */
abstract class AbstractPhpunitScriptTest extends PHPUnit_Framework_TestCase
{
	private $syntax_check_map = [
		'SMS Scripts' => [
			'path' => 'lib/smsscripts',
			'pattern' => '/^inbound_\d+\.php/',
		],
		'SMS' => [
			'path' => 'lib/SMS',
			'pattern' => '/^supplier_\d+\.php/',
		],
		'HLR' => [
			'path' => 'lib/HLR',
			'pattern' => '/^supplier_\d+\.php/',
		],
		'reporting' => [
			'path' => 'scripts/reporting'
		],
		'autoload' => [
			'path' => 'scripts/autoload'
		],
	];

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	protected function get_syntax_check_map_keys() {
		return array_keys($this->syntax_check_map);
	}

	/**
	 * @param string $file
	 * @return void
	 */
	protected function check_file_syntax($file) {
		exec("php -n -l '$file' 2>&1", $output, $return_value);
		$this->assertCount(
			1,
			$output,
			implode("\n", array_reverse($output))
		);
		$output = current($output);
		$this->assertContains(
			'No syntax errors detected',
			$output,
			"ERROR: {$output}"
		);
	}

	/**
	 * @param string $map_key
	 * @return array
	 */
	protected function get_files($map_key) {
		$this->assertArrayHasKey($map_key, $this->syntax_check_map, "No key '$map_key' map is set.");
		$options = $this->syntax_check_map[$map_key];
		$this->assertArrayHasKey('path', $options);

		// path
		$path = APP_ROOT_PATH . '/' . $options['path'];
		$this->assertTrue(is_dir($path), "Path '$path' is not a directory.");

		// remove . and ..
		$callback = function($file) use ($options) {
			if (isset($options['pattern']) && !preg_match($options['pattern'], $file)) {
				return false;
			}
			$path = $options['path'] . '/' . $file;
			return is_file($path) ? $path : false;
		};
		$files = array_filter(array_map($callback, scandir($path)));

		$this->assertNotEmpty($files, "No files found for key map '$map_key' in path '$path'.");

		return $files;
	}
}
