<?php
/**
 * CodingStandardsTest
 * Execute PHPCS coding standards tests
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\phpcs;

use Exception;
use Symfony\Component\Finder\Finder;
use testing\AbstractPhpunitTest;

/**
 * Execute PHPCS coding standards tests
 */
class CodingStandardsTest extends AbstractPhpunitTest
{
	const COVERAGE_STATUS_ANNOTATION = 'testCoverage';

	/** @var array */
	private $map = [
		'Morpheus api files' => [
			'path' => APP_ROOT_PATH,
			'pattern' => 'api_*.php',
			'annotations' => true,
		],
		'Services class' => [
			'path' => APP_ROOT_PATH . DIRECTORY_SEPARATOR . 'Services',
			'pattern' => '*.php',
			'standard' => 'PSR2',
		],
		'Testing class' => [
			'path' => APP_TESTING_PATH,
			'pattern' => '*.php',
			'ignore' => ['bootstrap.php', 'lib'],
		],
	];

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function coding_standards_data() {
		$data = [];

		foreach ($this->map as $desc => $map) {
			$finder = new Finder();
			$finder->files();
			$finder->in($map['path']);
			if (isset($map['pattern'])) {
				$finder->name($map['pattern']);
			}

			foreach ($finder as $file) {
				if (isset($map['annotations']) && $map['annotations']
					&& !$this->get_class_file_annotations($file->getRealPath(), self::COVERAGE_STATUS_ANNOTATION)) {
					continue;
				}

				$data["{$desc} for " . $file->getRelativePathname()] = [
					$file->getRealPath(),
					isset($map['ignore']) ? $map['ignore'] : null,
					isset($map['standard']) ? $map['standard'] : null
				];
			}
		}

		return $data;
	}

	/**
	 * Execute PHPCS command via shell and return the complete output
	 *
	 * @dataProvider coding_standards_data
	 * @param string $path
	 * @param mixed  $ignore
	 * @param string $standard
	 * @return void
	 */
	public function test_coding_standards_source_code_files($path, $ignore = null, $standard = null) {
		// file or dir exists
		$this->assertTrue(file_exists($path), "Failed asserting that file or directory '{$path}' exists");

		// Prepare ignore option
		$ignore_option = [];
		if ($ignore) {
			foreach ((array) $ignore as $value) {
				$ignore_option[] = "--ignore='{$value}'";
			}
		}

		// Execute PHPCS command via shell. Return NULL if checks passed
		// NOTE: "echo '' |" is there to fix ssh/bamboo execution
		exec(
			sprintf(
				"echo '' | %s --extensions=php --colors --standard='%s' %s '%s'",
				APP_ROOT_PATH . $this->get_config('phpcs.phpcs_bin_path'),
				$standard ?: APP_ROOT_PATH . $this->get_config('phpcs.standards_path'),
				implode(' ', $ignore_option),
				$path
			),
			$out
		);
		$out = implode("\n", $out);
		$this->assertEmpty($out, $out);
	}

	/**
	 * Return the value of a class file annotation
	 *
	 * @codeCoverageIgnore
	 * @param string $file
	 * @param string $annotation
	 * @return string|false
	 * @throws Exception If file does not exists or is not readable.
	 */
	private function get_class_file_annotations($file, $annotation) {
		if (!is_file($file) || !is_readable($file)) {
			throw new Exception("File '{$file}' does not exists or is not readable.");
		}
		// file content in array for each line
		$lines = file($file, FILE_IGNORE_NEW_LINES);

		// Find class file annotations
		if ($lines[0] === '<?php' && $lines[1] === '/**') {
			array_shift($lines); // remove <?php
			array_shift($lines); // remove /**
			// Loop other file class annotation only
			foreach ($lines as $line) {
				if (preg_match('/^\s\*\s@' . preg_quote(trim($annotation)) . '(|(?:\s+.*))$/', $line, $matches)) {
					return $matches[1] ? trim($matches[1]) : true;
				}
				if ($line === ' */') {
					break;
				}
			}
		}

		return false;
	}
}
