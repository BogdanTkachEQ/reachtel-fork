<?php
/**
 * AssetModuleHelperTrait
 * Helper to create assets
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait Helper for assets
 */
trait AssetModuleHelper
{
	/**
	 * @return string
	 */
	protected static function get_asset_type() {
		return 'ASSET';
	}

	/**
	 * List of valid file extentions
	 *
	 * @return array
	 */
	protected static function get_asset_valid_extensions() {
		return self::get_config('helpers.asset.valid_extensions');
	}

	/**
	 * @return string
	 */
	protected function get_expected_next_asset_id() {
		return $this->get_expected_next_id(self::get_asset_type());
	}

	/**
	 * @param string $asset_filename
	 * @return integer
	 */
	protected function create_new_asset($asset_filename = null) {
		$expected_id = $this->get_expected_next_asset_id();

		$asset_types = self::get_asset_valid_extensions();
		$asset_type = $asset_types[rand(0, count($asset_types) - 1)];
		if ($asset_filename) {
			$asset_type = pathinfo($asset_filename, PATHINFO_EXTENSION);
		}

		$asset_filename = $this->create_test_file($asset_type, sys_get_temp_dir(), $asset_filename);
		$this->assertInternalType('string', $asset_filename);
		$this->assertTrue(file_exists($asset_filename));

		$file = ['error' => 0, 'tmp_name' => $asset_filename, 'name' => basename($asset_filename)];

		// mock move_uploaded_file to just copy file
		$this->mock_function_replace('move_uploaded_file', 'rename');
		$this->assertSameEquals($expected_id, (int) api_asset_fileupload($file));
		self::remove_mocked_functions('move_uploaded_file');

		return ['id' => $expected_id, 'filename' => basename($asset_filename)];
	}

	/**
	 * @return void
	 */
	protected function purge_all_assets() {
		$all_assets = api_asset_listall();
		$this->assertInternalType('array', $all_assets);
		foreach ($all_assets as $asset_id => $asset) {
			$this->assertTrue(api_asset_delete($asset_id));
		}
		$this->assertEmpty(api_asset_listall());
	}
}
