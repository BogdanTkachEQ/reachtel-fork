<?php
/**
 * ApiAudioTest
 * Unit test for api_audios.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

use testing\unit\helpers\MethodsCheckExistsUnitTrait;
use testing\unit\helpers\MethodsSettingsUnitTrait;
use testing\unit\helpers\MethodsTagsUnitTrait;

/**
 * Api Audio Unit Test class
 */
class ApiAudioUnitTest extends AbstractPhpunitUnitTest
{
	use MethodsCheckExistsUnitTrait;
	use MethodsSettingsUnitTrait;
	use MethodsTagsUnitTrait;

	/**
	 * Type value
	 */
	const TYPE = 'AUDIO';

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_audio_add_data() {
		return [
			// Failures name_exists
			[false, 'audio name', true],

			// Success
			[123456, 'audio name']
		];
	}

	/**
	 * @group api_audio_add
	 * @dataProvider api_audio_add_data
	 * @param mixed   $expected_value
	 * @param string  $name
	 * @param boolean $name_exists
	 * @return void
	 */
	public function test_api_audio_add($expected_value, $name, $name_exists = false) {
		$this->mock_function_value('api_audio_checknameexists', $name_exists);
		$this->mock_function_value('api_keystore_increment', 123456);
		$this->mock_function_value('api_audio_setting_set', null);

		$this->assertSameEquals($expected_value, api_audio_add($name));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_audio_delete_data() {
		return [
			// Failures audioid does not exists
			[false, false],

			// Success
			[true],
			[true, true, false], // file not found
			[true, true, true, false], // name not found
		];
	}

	/**
	 * @group api_audio_delete
	 * @dataProvider api_audio_delete_data
	 * @param boolean $expected_value
	 * @param boolean $audio_exists
	 * @param boolean $is_file
	 * @param mixed   $name
	 * @return void
	 */
	public function test_api_audio_delete($expected_value, $audio_exists = true, $is_file = true, $name = 'file.png') {
		$this->mock_function_value('api_audio_checkidexists', $audio_exists);
		$this->mock_function_value('is_file', $is_file);
		$this->mock_function_value('api_audio_setting_getsingle', $name);
		$this->mock_function_value('api_keystore_purge', null);
		$this->mock_function_value('unlink', null);
		$this->mock_function_value('api_voice_servers_listall_active', ['server']);
		$this->mock_function_value('api_queue_add', null);

		$this->assertSameEquals($expected_value, api_audio_delete(1));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_audio_fileupload_data() {
		$expected_sox = function($extension, $end = null) {
			$end = $end ? : "wav '/tmp/php45wqw-PROCESSED.wav'";
			return [['args' => ["sox -V1 -t $extension '/tmp/php45wqw' -b 16 -c 1 -r 8000 -t {$end}"], 'return' => null]];
		};

		return [
			// Errors
			[false, ['name' => 'audio.wav', 'tmp_name' => '/tmp/php45wqw']],
			[false, ['name' => 'audio.wav', 'tmp_name' => '/tmp/php45wqw', 'error' => 2]],
			[false, ['name' => 'audio.wav', 'tmp_name' => '/tmp/php45wqw', 'error' => 1]],
			[false, ['name' => 'audio.mp3', 'tmp_name' => '/tmp/php45wqw', 'error' => 3]],

			// failures file extension
			[false, ['name' => 'file.jpg', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[false, ['name' => 'file.jpeg', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[false, ['name' => 'file.png', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[false, ['name' => 'file.pdf', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
			[false, ['name' => 'file.ogg', 'tmp_name' => '/tmp/php45wqw', 'error' => 0]],
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
			[false, ['name' => 'file.wav', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], false, true, false],
			[false, ['name' => 'file.mp3', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], false, true, false],

			// failures move uploaded file
			[false, ['name' => 'file.wav', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], $expected_sox('wav'), false],
			[false, ['name' => 'file.mp3', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], $expected_sox('mp3'), false],

			// success filename does exists
			[1, ['name' => 'filename-exists.wav', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], $expected_sox('wav')],
			[1, ['name' => 'filename-exists.mp3', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], $expected_sox('mp3')],

			// success filename does exists and content
			[1, ['name' => 'filename-exists.wav', 'content' => 'this is a test', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], $expected_sox('wav')],
			[1, ['name' => 'filename-exists.mp3', 'content' => 'this is a test', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], $expected_sox('mp3')],

			// success filename does not exists
			[2, ['name' => 'filename-not-exists.wav', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], $expected_sox('wav')],
			[2, ['name' => 'filename-not-exists.mp3', 'tmp_name' => '/tmp/php45wqw', 'error' => 0], $expected_sox('mp3')],

			// success filename specific chars
			[
				2,
				['name' => "/path/to/([{=+_*&^%$#@!}])? I'am\na \"file.wav", 'tmp_name' => '/tmp/php45wqw', 'error' => 0],
				$expected_sox('wav'),
			],
			[
				2,
				['name' => "/path/to/([{=+_*&^%$#@!}])? I'am\na \"file.mp3", 'tmp_name' => '/tmp/php45wqw', 'error' => 0],
				$expected_sox('mp3'),
			],

			// success filename does exists and empty content
			[
				1,
				['name' => 'filename-exists.wav', 'content' => '', 'tmp_name' => '/tmp/php45wqw', 'error' => 0],
				array_merge($expected_sox('wav', "flac '/tmp/php45wqw-TRIMMED.flac' trim 0 0:15"), $expected_sox('wav'))
			],
		];
	}

	/**
	 * @group api_audio_fileupload
	 * @dataProvider api_audio_fileupload_data
	 * @param boolean $expected_value
	 * @param array   $file
	 * @param mixed   $expected_passthru
	 * @param boolean $move_uploaded_file
	 * @param boolean $sanitize_filename
	 * @return void
	 */
	public function test_api_audio_fileupload($expected_value, array $file, $expected_passthru = false, $move_uploaded_file = true, $sanitize_filename = true) {
		$this->mock_function_value('move_uploaded_file', $move_uploaded_file);
		$this->mock_function_value('rename', true);
		$this->mock_function_param_value(
			'api_audio_checknameexists',
			[
				['params' => 'filename-exists.wav', 'return' => 1],
				['params' => 'filename-exists.mp3', 'return' => 2],
			],
			false
		);

		$this->mock_function_value('api_misc_sanitize_upload_filename', $sanitize_filename ? $file['name'] : false);
		$this->mock_function_value('api_audio_add', 2);
		$this->mock_function_value('api_templates_notify', null);
		$this->mock_function_value('api_audio_setting_set', null);
		$this->mock_function_value('chmod', null);
		$this->mock_function_value('md5_file', null);
		$this->mock_function_value('filesize', null);
		$this->mock_function_value('unlink', null);
		$this->mock_function_value('api_misc_speechrecognition', ['utterance' => '1']);

		$this->assertSameEquals($expected_value, api_audio_fileupload($file));

		if ($expected_passthru) {
			$this->assertListenMockFunction('passthru', $expected_passthru);
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_audio_stream_data() {
		return [
			// Failures audioid does not exists
			[false, false],

			// Failures is not file
			[false, true, false, false],

			// Failures handle
			[false, true, 'test_api_audio_stream_handle_error.wav', false],

			// Success
			[null],
		];
	}

	/**
	 * @group api_audio_stream
	 * @dataProvider api_audio_stream_data
	 * @param boolean $expected_value
	 * @param boolean $audio_exists
	 * @param mixed   $filename
	 * @param boolean $handle
	 * @return void
	 */
	public function test_api_audio_stream($expected_value, $audio_exists = true, $filename = 'test_api_audio_stream.wav', $handle = true) {

		$file_path = false;
		if ($filename) {
			$file_path = READ_LOCATION . AUDIO_LOCATION . '/' . $filename;
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
		$this->mock_function_value('api_audio_checkidexists', $audio_exists);
		$this->mock_function_value('api_audio_setting_getsingle', $filename);
		$this->mock_function_value('header', null);
		$this->mock_function_value('api_email_filetype', null);

		$this->assertSameEquals($expected_value, api_audio_stream(1));

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
	public function api_audio_listall_data() {
		return [
			// Success no records
			[[], [], []],
			[[], ['countonly' => false], []],
			[0, ['countonly' => true], ['count' => 0]],

			// Success with records
			[['name1', 'name2', 'name3']],
			[[0, 1, 2], ['short' => true]],
			[['name1'], ['search' => 'test*'], [1]],
			[['name1'], ['orderby' => 'length'], [1]],
			[['name1'], ['search' => 'test'], [1]],
			[['name1'], ['search' => 'test*', 'searchfields' => ['filename']], [1]],
			[['name1'], ['search' => 'test', 'limit' => 1], [1]],
			[['name1'], ['search' => 'test', 'limit' => 1, 'offset' => 2], [1]],
			[['name1'], ['search' => 'test', 'limit' => 1, 'offset' => 2, 'orderby' => 'length'], [1]],
		];
	}

	/**
	 * @group api_audio_listall
	 * @dataProvider api_audio_listall_data
	 * @param mixed $expected_value
	 * @param array $options
	 * @param array $ado_records
	 * @return void
	 */
	public function test_api_audio_listall($expected_value, array $options = [], array $ado_records = [1, 2, 3]) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			(is_array($ado_records) ? $this->mock_ado_records($ado_records) : $ado_records)
		);

		$this->mock_function_param_value(
			'api_audio_setting_getall',
			[
				['params' => 1, 'return' => 'name2'],
				['params' => 2, 'return' => 'name3'],
			],
			'name1'
		);

		$this->assertSameEquals($expected_value, api_audio_listall($options));
	}
}
