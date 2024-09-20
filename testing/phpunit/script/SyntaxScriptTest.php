<?php
/**
 * SyntaxScriptTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\script;

/**
 * SyntaxScriptTest Test class
 */
class SyntaxScriptTest extends AbstractPhpunitScriptTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function syntax_scripts_data() {
		$data = [];
		$syntax_check_map_keys = $this->get_syntax_check_map_keys();

		foreach ($syntax_check_map_keys as $key) {
			$files = $this->get_files($key);
			foreach ($files as $file) {
				$data[sprintf('%s: %s', $key, basename($file))] = [$file];
			}
		}

		return $data;
	}

	/**
	 * @dataProvider syntax_scripts_data
	 * @param string $file
	 * @return void
	 */
	public function test_syntax_scripts($file) {
		$this->check_file_syntax($file);
	}
}
