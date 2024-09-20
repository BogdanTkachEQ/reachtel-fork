<?php
/**
 * ApiAssetsModuleTest
 * Module test for api_assets.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use testing\module\helpers\AssetModuleHelper;
use testing\module\helpers\MethodsCheckExistsModuleTrait;
use testing\module\helpers\MethodsSettingsModuleTrait;

/**
 * Api Assets Module Test
 */
class ApiAssetsModuleTest extends AbstractPhpunitModuleTest
{
	use AssetModuleHelper;
	use MethodsCheckExistsModuleTrait;
	use MethodsSettingsModuleTrait;

	/**
	 * Type value
	 */
	private static $type;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		self::$type = self::get_asset_type();
	}

	/**
	 * @group api_asset_add
	 * @return void
	 */
	public function test_api_asset_add() {
		$expected_id = $this->get_expected_next_asset_id();
		$asset_name = uniqid() . 'test_api_asset_add';

		// Asset does not exists and is created
		$this->assertSameEquals($expected_id, api_asset_add($asset_name));

		// Asset exists and is not created
		$this->assertFalse(api_asset_add($asset_name));

		// delete created asset
		$this->assertTrue(api_asset_setting_delete_single($expected_id, 'name'));
	}

	/**
	 * @group api_asset_delete
	 * @return void
	 */
	public function test_api_asset_delete() {
		$asset_name = uniqid() . '_test_api_asset_delete.pdf';

		// Asset does not exists
		$this->assertFalse(api_asset_delete($asset_name));

		// Asset exists
		$asset = $this->create_new_asset($asset_name);

		// check asset array
		$this->assertInternalType('array', $asset);
		$this->assertCount(2, $asset);
		$this->assertArrayHasKey('id', $asset);
		$this->assertArrayHasKey('filename', $asset);

		$this->assertTrue(api_asset_delete($asset['id']));
	}

	/**
	 * @group api_asset_fileupload
	 * @return void
	 */
	public function test_api_asset_fileupload() {
		$valid_extensions = self::get_asset_valid_extensions();
		$_file = $this->create_asset_file();
		$file = $_file;

		// failure error
		$file['error'] = 1;
		$this->assertFalse(api_asset_fileupload($file));
		$file['error'] = 2;
		$this->assertFalse(api_asset_fileupload($file));

		// failure wrong file type
		$file = $_file;
		$file['name'] = 'wrong-file.txt';
		$this->assertFalse(api_asset_fileupload($file));

		// failure wrong file type
		$file['name'] = 'file.txt';
		$this->assertFalse(api_asset_fileupload($file));

		// failure sanitize
		foreach ($valid_extensions as $extension) {
			$file['name'] = "$$$$$.$extension";
			$this->assertFalse(api_asset_fileupload($file));
		}

		// failure without mocking move_uploaded_file should fail
		$file = $_file;
		$this->assertFalse(api_asset_fileupload($file));

		// success all types files
		foreach ($valid_extensions as $extension) {
			$file = $this->create_asset_file($extension);
			$expected_id = $this->get_expected_next_asset_id();
			$this->mock_function_replace('move_uploaded_file', 'rename');
			$this->assertSameEquals($expected_id, (int) api_asset_fileupload($file));
			self::remove_mocked_functions('move_uploaded_file');
			$this->assertTrue(api_asset_delete($expected_id)); // assert that file exists too
		}

		// success test specific char in filename
		foreach ($valid_extensions as $extension) {
			$file = $this->create_asset_file($extension, "it's a test ({[<!@#$%^&*_+:\"?>]})");
			$expected_id = $this->get_expected_next_asset_id();
			$this->mock_function_replace('move_uploaded_file', 'rename');
			$this->assertSameEquals($expected_id, (int) api_asset_fileupload($file));
			self::remove_mocked_functions('move_uploaded_file');
			$this->assertTrue(api_asset_delete($expected_id)); // assert that file exists too
		}
	}

	/**
	 * @group api_asset_stream
	 * @return void
	 */
	public function test_api_asset_stream() {
		$expected_id = $this->get_expected_next_asset_id();
		$this->assertFalse(api_asset_stream($expected_id));

		foreach (self::get_asset_valid_extensions() as $extension) {
			$expected_id = $this->get_expected_next_asset_id();
			$asset_filename = uniqid() . "_test_api_asset_stream.$extension";
			$asset = $this->create_new_asset($asset_filename);

			// check asset array
			$this->assertInternalType('array', $asset);
			$this->assertCount(2, $asset);
			$this->assertArrayHasKey('id', $asset);
			$this->assertArrayHasKey('filename', $asset);
			$this->assertSameEquals(basename($asset_filename), $asset['filename']);
			$this->assertSameEquals($expected_id, $asset['id']);

			// mock headers to avoid ' headers already sent' PHP error
			$this->mock_function_value('header', true);
			$this->assertTrue(ob_start()); // disable output
			$this->assertNull(api_asset_stream($expected_id));
			$this->assertTrue(ob_end_clean()); // fliush output
			self::remove_mocked_functions('header');

			// handle fopen failure should return false
			$this->mock_function_value('fopen', false);
			$this->assertFalse(api_asset_stream($expected_id));
			self::remove_mocked_functions('fopen');

			// filename not found should return false
			$this->assertTrue(api_asset_setting_set($expected_id, 'name', "$asset_filename.not-exists"));
			$this->assertFalse(api_asset_stream($expected_id));
			$this->assertTrue(api_asset_setting_set($expected_id, 'name', $asset_filename));

			// delete created asset
			$this->assertTrue(api_asset_delete($expected_id));
		}
	}

	/**
	 * @group api_asset_listall
	 * @return void
	 */
	public function test_api_asset_listall() {
		$asset = $this->create_new_asset();

		$all_assets = api_asset_listall();
		$this->assertInternalType('array', $all_assets);
		$this->assertGreaterThanOrEqual(1, count($all_assets));

		// log param
		$all_assets = api_asset_listall(1);
		$this->assertGreaterThanOrEqual(1, count($all_assets));
		foreach ($all_assets as $asset) {
			$this->assertInternalType('array', $asset);
			$this->assertCount(3, $asset);
			$this->assertArrayHasKey('name', $asset);
			$this->assertArrayHasKey('size', $asset);
			$this->assertArrayHasKey('type', $asset);
		}

		// test list empty so purge all before
		$this->purge_all_assets();
		$all_assets = api_asset_listall();
		$this->assertEmpty(api_asset_listall());
	}

	/**
	 * @param string $extension
	 * @param string $filename
	 * @return string
	 */
	private function create_asset_file($extension = 'png', $filename = 'test_asset_file') {
		$path = $this->create_test_file($extension, sys_get_temp_dir(), 'php');
		return ['error' => 0, 'tmp_name' => $path, 'name' => uniqid() . "_{$extension}_{$filename}.{$extension}"];
	}
}
