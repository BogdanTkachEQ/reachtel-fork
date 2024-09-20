<?php
/**
 * AppSavePathTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

use testing\AbstractPhpunitTest;

/**
 * Paths Test
 */
class AppSavePathTest extends AbstractPhpunitTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function paths_data() {
		return [
			// save path dirs
			['BASE_LOCATION'],
			['READ_LOCATION'],
			['SAVE_LOCATION'],
			['SAVE_LOCATION/ASSET_LOCATION'],
			['SAVE_LOCATION/AUDIO_LOCATION'],
			['SAVE_LOCATION/DIALPLAN_LOCATION'],
			['SAVE_LOCATION/EMAILBODY_LOCATION'],
			['SAVE_LOCATION/EMAILTEMPLATE_LOCATION'],
			['SAVE_LOCATION/INVOICES_LOCATION'],
			['SAVE_LOCATION/LISTS_LOCATION'],
			['SAVE_LOCATION/REMOTEATTACHMENTS_LOCATION'],
			['SAVE_LOCATION/SIP_LOCATION'],
			['SAVE_LOCATION/IAX_LOCATION'],
			['SAVE_LOCATION/SMSSCRIPTS_LOCATION'],
			// email assets (templates , icons ...)
			['READ_LOCATION/EMAILTEMPLATE_LOCATION', 'default-html.tpl', 'TESTING'],
			['READ_LOCATION/EMAILTEMPLATE_LOCATION', 'default-text.tpl', 'TESTING'],
			['READ_LOCATION/EMAILTEMPLATE_LOCATION', 'autodialer-html-ReachTEL.tpl', "HTML\n{date}\n{content}"],
			['READ_LOCATION/EMAILTEMPLATE_LOCATION', 'autodialer-text-ReachTEL.tpl', "TEXT\n{date}\n{content}"],
			['READ_LOCATION/ASSET_LOCATION', 'reachtel-150.png'],
			['READ_LOCATION/EMAILTEMPLATE_LOCATION', 'recycle-small.png'],
		];
	}

	/**
	 * @dataProvider paths_data
	 * @param string $constant_path
	 * @param string $file
	 * @param string $file_content
	 * @return void
	 */
	public function test_paths($constant_path, $file = null, $file_content = null) {
		$path = $this->assertConstantPath($constant_path);

		$this->assertTrue(
			is_dir($path),
			"Failed asserting that directory {$path} exists."
		);

		$this->assertTrue(
			is_writable($path),
			"Failed asserting that directory {$path} has writable permissions."
		);

		if ($file) {
			$file = "{$path}/{$file}";

			// create file if not exists
			if (!is_file($file)) {
				@file_put_contents($file, $file_content ? : uniqid($file));
			}

			$this->assertTrue(
				is_file($file),
				"Failed asserting that file {$file} exists."
			);

			$this->assertTrue(
				is_readable($file),
				"Failed asserting that directory {$path} has writable permissions."
			);
		}
	}

	/**
	 * @param string $constant_path
	 * @return string
	 */
	private function assertConstantPath($constant_path) {
		$path = '';

		foreach (explode('/', $constant_path) as $constant) {
			$this->assertTrue(
				defined($constant),
				"Failed asserting that constant {$constant} is defined."
			);
			$path .= constant($constant);
		}

		// create directory if not exists
		if (!is_dir($path)) {
			$this->assertTrue(
				@mkdir($path, 0777, true),
				"Failed to create dir: {$path}"
			);
		}

		return $path;
	}
}
