<?php
/**
 * ApiAudioModuleTest
 * Module test for api_audio.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use testing\module\helpers\AudioModuleHelper;
use testing\module\helpers\MethodsCheckExistsModuleTrait;
use testing\module\helpers\MethodsSettingsModuleTrait;
use testing\module\helpers\MethodsTagsModuleTrait;
use testing\module\helpers\VoiceServerModuleHelper;

/**
 * Api Audio Module Test
 */
class ApiAudioModuleTest extends AbstractPhpunitModuleTest
{
	use AudioModuleHelper;
	use MethodsCheckExistsModuleTrait;
	use MethodsSettingsModuleTrait;
	use MethodsTagsModuleTrait;
	use VoiceServerModuleHelper;

	/**
	 * Type value
	 */
	private static $type;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		self::$type = self::get_audio_type();
	}

	/**
	 * @group api_audio_information
	 * @return void
	*/
	public function test_api_audio_information() {
		// audio file is not valid (PDF)
		$file = $this->create_audio_file('pdf');
		$this->assertInternalType('array', $file);
		$this->assertFalse(api_audio_information($file['tmp_name']));
		$this->assertEquals(
			"Sorry, that is not a valid audio file: soxi FAIL formats: can't determine type of file `{$file['tmp_name']}'",
			api_error_printiferror(['return' => true])
		);

		// audio file is valid
		$file = $this->create_audio_file('wav');
		$this->assertInternalType('array', $file);
		$infos = api_audio_information($file['tmp_name']);
		$this->assertInternalType('array', $infos);
		$this->assertSameEquals(
			[
				'channels' => 2,
				'samplerate' => 44100,
				'precision' => '16-bit',
				'duration' => '00:00:00.00 = 5 samples = 0.0085034 CDDA sectors',
				'filesize' => '64',
				'bitrate' => '4.52M',
				'sampleencoding' => '16-bit Signed Integer PCM',
			],
			$infos
		);
	}

	/**
	 * @group api_audio_add
	 * @return void
	 */
	public function test_api_audio_add() {
		$expected_id = $this->get_expected_next_audio_id();
		$audio_name = uniqid() . 'test_api_audio_add';

		// audio does not exists and is created
		$this->assertSameEquals($expected_id, api_audio_add($audio_name));

		// audio exists and is not created
		$this->assertFalse(api_audio_add($audio_name));

		// delete created audio
		$this->assertTrue(api_audio_setting_delete_single($expected_id, 'name'));
	}

	/**
	 * @group api_audio_delete
	 * @return void
	 */
	public function test_api_audio_delete() {
		$audio_name = uniqid() . '_test_api_audio_delete.wav';
		$need_new_voice_server = (0 == count(api_voice_servers_listall_active()));

		// create voice servers if needed
		if ($need_new_voice_server) {
			$new_voice_server_id = $this->create_new_voiceserver('apiaudiodelete', 'active'); // @codeCoverageIgnore
			$this->assertInternalType('integer', $new_voice_server_id); // @codeCoverageIgnore
		} // @codeCoverageIgnore

		// audio does not exists
		$this->assertFalse(api_audio_delete($audio_name));

		// audio exists
		$audio = $this->create_new_audio($audio_name);

		// check audio array
		$this->assertInternalType('array', $audio);
		$this->assertCount(2, $audio);
		$this->assertArrayHasKey('id', $audio);
		$this->assertArrayHasKey('filename', $audio);

		$this->assertTrue(api_audio_delete($audio['id']));

		if ($need_new_voice_server) {
			$this->assertTrue(api_voice_servers_delete($new_voice_server_id)); // @codeCoverageIgnore
		} // @codeCoverageIgnore
	}

	/**
	 * @group api_audio_fileupload
	 * @return void
	 */
	public function test_api_audio_fileupload() {
		$valid_extensions = self::get_audio_valid_extensions();
		$_file = $this->create_audio_file();
		$file = $_file;

		// failure error
		$file['error'] = 1;
		$this->assertFalse(api_audio_fileupload($file));
		$file['error'] = 2;
		$this->assertFalse(api_audio_fileupload($file));

		// failure wrong file type
		$file = $_file;
		$file['name'] = 'wrong-file.txt';
		$this->assertFalse(api_audio_fileupload($file));

		// failure wrong file type
		$file['name'] = 'file.txt';
		$this->assertFalse(api_audio_fileupload($file));

		// failure sanitize
		foreach ($valid_extensions as $extension) {
			$file['name'] = "$$$$$.$extension";
			$this->assertFalse(api_audio_fileupload($file));
		}

		// failure without mocking move_uploaded_file should fail
		$file = $_file;
		$this->assertFalse(api_audio_fileupload($file));

		// success all types files
		foreach ($valid_extensions as $extension) {
			$file = $this->create_audio_file($extension);
			$expected_id = $this->get_expected_next_audio_id();
			$this->mock_function_replace('move_uploaded_file', 'rename');
			$this->assertSameEquals($expected_id, (int) api_audio_fileupload($file));
			self::remove_mocked_functions('move_uploaded_file');
			$this->assertTrue(api_audio_delete($expected_id)); // assert that file exists too
		}

		// success test specific char in filename
		foreach ($valid_extensions as $extension) {
			$file = $this->create_audio_file($extension, "it's a test ({[<!@#$%^&*_+:\"?>]})");
			$expected_id = $this->get_expected_next_audio_id();
			$this->mock_function_replace('move_uploaded_file', 'rename');
			$this->assertSameEquals($expected_id, (int) api_audio_fileupload($file));
			self::remove_mocked_functions('move_uploaded_file');
			$this->assertTrue(api_audio_delete($expected_id)); // assert that file exists too
		}

		// content speechrecognition
		$file = $this->create_audio_file($extension);
		$file['content'] = '';
		$expected_id = $this->get_expected_next_audio_id();
		$this->mock_function_replace('move_uploaded_file', 'rename');
		$this->assertSameEquals($expected_id, (int) api_audio_fileupload($file));
	}

	/**
	 * @group api_audio_stream
	 * @return void
	 */
	public function test_api_audio_stream() {
		$valid_extensions = self::get_audio_valid_extensions();
		$expected_id = $this->get_expected_next_audio_id();
		$this->assertFalse(api_audio_stream($expected_id));

		// we need to remove mp3
		$id_mp3 = array_search('mp3', $valid_extensions);
		$this->assertInternalType('integer', $id_mp3);
		unset($valid_extensions[$id_mp3]);

		foreach ($valid_extensions as $extension) {
			$expected_id = $this->get_expected_next_id('audio');
			$audio_filename = uniqid() . "_test_api_audio_stream.$extension";
			$audio = $this->create_new_audio($audio_filename);

			// check audio array
			$this->assertInternalType('array', $audio);
			$this->assertCount(2, $audio);
			$this->assertArrayHasKey('id', $audio);
			$this->assertArrayHasKey('filename', $audio);
			$this->assertSameEquals(basename($audio_filename), $audio['filename']);
			$this->assertSameEquals($expected_id, $audio['id']);

			// mock headers to avoid ' headers already sent' PHP error
			$this->mock_function_value('header', true);
			$this->assertTrue(ob_start()); // disable output
			$this->assertNull(api_audio_stream($expected_id));
			$this->assertTrue(ob_end_clean()); // fliush output
			self::remove_mocked_functions('header');

			// handle fopen failure should return false
			$this->mock_function_value('fopen', false);
			$this->assertFalse(api_audio_stream($expected_id));
			self::remove_mocked_functions('fopen');

			// filename not found should return false
			$this->assertTrue(api_audio_setting_set($expected_id, 'name', "$audio_filename.not-exists"));
			$this->assertFalse(api_audio_stream($expected_id));
			$this->assertTrue(api_audio_setting_set($expected_id, 'name', $audio_filename));

			// delete created audio
			$this->assertTrue(api_audio_delete($expected_id));
		}
	}

	/**
	 * @group api_audio_listall
	 * @return void
	 */
	public function test_api_audio_listall() {
		$this->purge_all_audios();

		$all_audios = api_audio_listall();
		$this->assertInternalType('array', $all_audios);
		$this->assertEmpty($all_audios);
		$this->assertEquals(0, api_audio_listall(['countonly' => true]));

		$audio = $this->create_new_audio();
		$all_audios = api_audio_listall();
		$this->assertInternalType('array', $all_audios);
		$this->assertGreaterThanOrEqual(1, count($all_audios));
		$this->assertArrayHasKey($audio['id'], $all_audios);
		$this->assertEquals(1, api_audio_listall(['countonly' => true]));
		$this->assertEquals([$audio['id']], api_audio_listall(['short' => true]));

		$audio2 = $this->create_new_audio();
		$audio3 = $this->create_new_audio(uniqid('customtest') . '.wav');

		$this->assertEquals(3, api_audio_listall(['countonly' => true]));
		$this->assertEquals([$audio['id'], $audio2['id'], $audio3['id']], api_audio_listall(['short' => true]));
		$all_audios = api_audio_listall();
		$this->assertGreaterThanOrEqual(3, count($all_audios));
		$this->assertArrayHasKey($audio['id'], $all_audios);
		$this->assertArrayHasKey($audio2['id'], $all_audios);
		$this->assertArrayHasKey($audio3['id'], $all_audios);
		foreach ($all_audios as $audio_file) {
			$this->assertArrayHasKey('md5', $audio_file);
			$this->assertArrayHasKey('name', $audio_file);
			$this->assertArrayHasKey('size', $audio_file);
		}

		// search tests on any fields
		$searches = [
			'customtest' => 1,
			'whatever' => 0,
			'cust*' => 1,
			'test*' => 3,
			'*test' => 3,
			'test' => 3
		];
		foreach ($searches as $search => $expected_nb_results) {
			$all_audios = api_audio_listall(['search' => $search]);
			$this->assertEquals($expected_nb_results, count($all_audios), "Search '{$search}' failed");
		}

		// search tests on name field
		foreach ($searches as $search => $expected_nb_results) {
			$all_audios = api_audio_listall(['search' => $search, 'searchfields' => ['name']]);
			$this->assertEquals($expected_nb_results, count($all_audios));
		}

		// test orderby, limit and offset
		$all_audios = api_audio_listall(['orderby' => 'length']);
		$this->assertEquals(3, count($all_audios));
		reset($all_audios);
		$this->assertEquals($audio3['id'], key($all_audios));
		next($all_audios);
		$this->assertEquals($audio['id'], key($all_audios));
		next($all_audios);
		$this->assertEquals($audio2['id'], key($all_audios));

		$all_audios = api_audio_listall(['limit' => 1]);
		$this->assertArrayHasKey($audio['id'], $all_audios);
		$this->assertEquals(1, count($all_audios));

		$all_audios = api_audio_listall(['offset' => 0, 'limit' => 1]);
		$this->assertArrayHasKey($audio['id'], $all_audios);
		$this->assertEquals(1, count($all_audios));

		$all_audios = api_audio_listall(['offset' => 1, 'limit' => 1]);
		$this->assertArrayHasKey($audio2['id'], $all_audios);
		$this->assertEquals(1, count($all_audios));

		$all_audios = api_audio_listall(['offset' => 2, 'limit' => 1]);
		$this->assertArrayHasKey($audio3['id'], $all_audios);
		$this->assertEquals(1, count($all_audios));

		// test order
		$all_audios = api_audio_listall();
		$this->assertInternalType('array', $all_audios);
		$this->assertEquals([$audio['id'], $audio2['id'], $audio3['id']], array_keys($all_audios));
		$all_audios = api_audio_listall(['order' => 'DESC']);
		$this->assertInternalType('array', $all_audios);
		$this->assertEquals([$audio3['id'], $audio2['id'], $audio['id']], array_keys($all_audios));

		// test list empty so purge all before
		$this->purge_all_audios();
	}

	/**
	 * @param string $extension
	 * @param string $filename
	 * @return string
	 */
	private function create_audio_file($extension = 'wav', $filename = 'test_audio_file') {
		$path = $this->create_test_file($extension, sys_get_temp_dir(), 'php');
		return ['error' => 0, 'tmp_name' => $path, 'name' => uniqid() . "_{$extension}_{$filename}.{$extension}"];
	}
}
