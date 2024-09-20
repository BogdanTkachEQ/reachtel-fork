<?php
/**
 * ApiAssetsTest
 * Unit test for api_assets.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

use testing\unit\helpers\MethodsCheckExistsUnitTrait;
use testing\unit\helpers\MethodsSettingsUnitTrait;

/**
 * Api Assets Unit Test class
 */
class ApiAssetsUnitTest extends AbstractPhpunitUnitTest
{
	use MethodsCheckExistsUnitTrait;
	use MethodsSettingsUnitTrait;

	/**
	 * Type value
	 */
	const TYPE = 'ASSET';

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_asset_add_data() {
		return [
			// Failures name_exists
			[false, 'asset name', true],

			// Success
			[123456, 'asset name']
		];
	}

	/**
	 * @group api_asset_add
	 * @dataProvider api_asset_add_data
	 * @param mixed   $expected_value
	 * @param string  $name
	 * @param boolean $name_exists
	 * @return void
	 */
	public function test_api_asset_add($expected_value, $name, $name_exists = false) {
		$this->mock_function_value('api_asset_checknameexists', $name_exists);
		$this->mock_function_value('api_keystore_increment', 123456);
		$this->mock_function_value('api_asset_setting_set', null);

		$this->assertSameEquals($expected_value, api_asset_add($name));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_asset_delete_data() {
		return [
			// Failures assetid does not exists
			[false, false],

			// Success
			[true],
			[true, true, false], // file not found
			[true, true, true, false], // name not found
		];
	}

	/**
	 * @group api_asset_delete
	 * @dataProvider api_asset_delete_data
	 * @param boolean $expected_value
	 * @param boolean $asset_exists
	 * @param boolean $is_file
	 * @param mixed   $name
	 * @return void
	 */
	public function test_api_asset_delete($expected_value, $asset_exists = true, $is_file = true, $name = 'file.png') {
		$this->mock_function_value('api_asset_checkidexists', $asset_exists);
		$this->mock_function_value('is_file', $is_file);
		$this->mock_function_value('api_asset_setting_getsingle', $name);
		$this->mock_function_value('api_keystore_purge', null);
		$this->mock_function_value('unlink', null);

		$this->assertSameEquals($expected_value, api_asset_delete(1));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_asset_fileupload_data() {
		return [
			// Errors
			[false, ['name' => 'file.jpg', 'tmp_name' => '/tmp/php45wqw']],
			[false, ['name' => 'file.jpg', 'tmp_name' => '/tmp/php45wqw', 'error' => 2]],
			[false, ['name' => 'file.jpg', 'tmp_name' => '/tmp/php45wqw', 'error' => 1]],
			[false, ['name' => 'file.jpg', 'tmp_name' => '/tmp/php45wqw', 'error' => 3]],

			// failures file extension
			[false, ['name' => 'file.txt', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[false, ['name' => 'file.php', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[false, ['name' => 'file.asp', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[false, ['name' => 'file.html', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[false, ['name' => 'file.js', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[false, ['name' => 'file.exe', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[false, ['name' => 'file.bat', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[false, ['name' => 'file.sh', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[false, ['name' => 'file', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],

			// failures sanitize filename
			[false, ['name' => 'file.jpg', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], true, true, false],
			[false, ['name' => 'file.jpeg', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], true, true, false],
			[false, ['name' => 'file.png', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], true, true, false],
			[false, ['name' => 'file.pdf', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], true, true, false],
			[false, ['name' => 'file.gif', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], true, true, false],

			// failures move uploaded file
			[false, ['name' => 'file.pdf', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], false],
			[false, ['name' => 'file.png', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], false],
			[false, ['name' => 'file.jpeg', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], false],
			[false, ['name' => 'file.jpg', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], false],
			[false, ['name' => 'file.gif', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], false],

			// success filename does exists
			[1, ['name' => 'filename-exists.pdf', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[2, ['name' => 'filename-exists.png', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[3, ['name' => 'filename-exists.jpeg', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[4, ['name' => 'filename-exists.jpg', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[5, ['name' => 'filename-exists.gif', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],

			// success filename does not exists
			[10, ['name' => 'filename-not-exists.pdf', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[10, ['name' => 'filename-not-exists.png', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[10, ['name' => 'filename-not-exists.jpeg', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[10, ['name' => 'filename-not-exists.jpg', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[10, ['name' => 'filename-not-exists.gif', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],

			// success no image info
			[10, ['name' => '/path/file.jpg', 'tmp_name' => 'tmp', 'error' => 0], true, false],

			// success
			[10, ['name' => '/tmp/image.jpg', 'tmp_name' => 'tmp', 'error' => 0]],
			[10, ['name' => '/tmp/image.jpeg', 'tmp_name' => 'tmp', 'error' => 0]],
			[10, ['name' => '/tmp/image.gif', 'tmp_name' => 'tmp', 'error' => 0]],
			[10, ['name' => '/tmp/image.png', 'tmp_name' => 'tmp', 'error' => 0]],
			[10, ['name' => '/tmp/image.pdf', 'tmp_name' => 'tmp', 'error' => 0]],

			// success filename specific chars
			[
				10,
				['name' => "/path/to/([{=+_*&^%$#@!}])? I'am\na \"file.jpg", 'tmp_name' => '/tmp/php45wqw', 'error' => 0]
			],
			[
				10,
				['name' => "/path/to/([{=+_*&^%$#@!}])? I'am\na \"file.jpeg", 'tmp_name' => '/tmp/php45wqw', 'error' => 0]
			],
			[
				10,
				['name' => "/path/to/([{=+_*&^%$#@!}])? I'am\na \"file.gif", 'tmp_name' => '/tmp/php45wqw', 'error' => 0]
			],
			[
				10,
				['name' => "/path/to/([{=+_*&^%$#@!}])? I'am\na \"file.png", 'tmp_name' => '/tmp/php45wqw', 'error' => 0]
			],
			[
				10,
				['name' => "/path/to/([{=+_*&^%$#@!}])? I'am\na \"file.pdf", 'tmp_name' => '/tmp/php45wqw', 'error' => 0]
			],
		];
	}

	/**
	* @group api_asset_fileupload
	* @dataProvider api_asset_fileupload_data
	* @param boolean $expected_value
	* @param array   $file
	* @param boolean $move_uploaded_file
	* @param boolean $imageinfo
	* @param boolean $sanitize_filename
	* @return void
	*/
	public function test_api_asset_fileupload($expected_value, array $file, $move_uploaded_file = true, $imageinfo = true, $sanitize_filename = true) {
		$this->mock_function_value('move_uploaded_file', $move_uploaded_file);
		$this->mock_function_param_value(
			'api_asset_checknameexists',
			[
				['params' => 'filename-exists.pdf', 'return' => 1],
				['params' => 'filename-exists.png', 'return' => 2],
				['params' => 'filename-exists.jpeg', 'return' => 3],
				['params' => 'filename-exists.jpg', 'return' => 4],
				['params' => 'filename-exists.gif', 'return' => 5]
			],
			false
		);

		$this->mock_function_value('api_misc_sanitize_upload_filename', $sanitize_filename ? $file['name'] : false);
		$this->mock_function_value('api_asset_add', 10);
		$this->mock_function_value('api_templates_notify', null);
		$this->mock_function_value('api_asset_setting_set', null);
		$this->mock_function_value('md5_file', 'df1555ec0c2d7fcad3a03770f9aa238a');
		$this->mock_function_value('filesize', 78943);
		$this->mock_function_value('getimagesize', $imageinfo);

		$this->assertSameEquals($expected_value, api_asset_fileupload($file));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_asset_stream_data() {
		return [
			// Failures assetid does not exists
			[false, false],

			// Failures is not file
			[false, true, false, false],

			// Failures handle
			[false, true, 'test_api_asset_stream_handle_error.png', false],

			// Success
			[null],
		];
	}

	/**
	 * @group api_asset_stream
	 * @dataProvider api_asset_stream_data
	 * @param boolean $expected_value
	 * @param boolean $asset_exists
	 * @param mixed   $filename
	 * @param boolean $handle
	 * @return void
	 */
	public function test_api_asset_stream($expected_value, $asset_exists = true, $filename = 'test_api_asset_stream.wav', $handle = true) {
		$file_path = false;
		if ($filename) {
			$file_path = READ_LOCATION . ASSET_LOCATION . '/' . $filename;
			if (is_file($file_path)) {
				$this->assertTrue(unlink($file_path)); // @codeCoverageIgnore
			} // @codeCoverageIgnore
			$this->assertEquals(0, file_put_contents($file_path, null));
			$this->assertTrue(file_exists($file_path));
		}

		if ($handle) {
			$this->mock_function_param('fopen', $file_path);
		} else {
			$this->mock_function_value('fopen', false);
		}
		$this->mock_function_value('api_asset_checkidexists', $asset_exists);
		$this->mock_function_value('api_asset_setting_getsingle', $filename);
		$this->mock_function_value('header', null);
		$this->mock_function_value('api_email_filetype', null);

		$this->assertSameEquals($expected_value, api_asset_stream(1));

		// force remove mocked functions for fopen
		$this->remove_mocked_functions();

		// remove tmp file
		if ($file_path) {
			$this->assertTrue(unlink($file_path));
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_asset_listall_data() {
		return [
			// Failures names
			[[], 0, false],
			[[], 0, null],
			[[], 0, ''],
			[[], 0, []],

			// Success
			[[3 => 'name3.png', 2 => 'name2.pdf']],
			[['sorted_array'], true]
		];
	}

	/**
	 * @group api_asset_listall
	 * @dataProvider api_asset_listall_data
	 * @param mixed   $expected_value
	 * @param integer $long
	 * @param mixed   $names
	 * @return void
	 */
	public function test_api_asset_listall($expected_value, $long = 0, $names = [3 => 'name3.png', 2 => 'name2.pdf']) {
		$this->mock_function_value('api_keystore_getids', $names);
		$this->mock_function_value('api_misc_sizeformat', 5);
		$this->mock_function_value('api_misc_natcasesortbykey', ['sorted_array']);

		$this->assertSameEquals($expected_value, api_asset_listall($long));
	}
}
