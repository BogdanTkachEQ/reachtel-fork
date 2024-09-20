<?php
/**
 * AudioModuleHelper
 * Helper to create audios
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait Helper for audios
 */
trait AudioModuleHelper
{
	/**
	 * @return string
	 */
	protected static function get_audio_type() {
		return 'AUDIO';
	}

	/**
	 * List of valid file extentions
	 *
	 * @return array
	 */
	protected static function get_audio_valid_extensions() {
		return self::get_config('helpers.audio.valid_extensions');
	}

	/**
	 * @return string
	 */
	protected function get_expected_next_audio_id() {
		return $this->get_expected_next_id(self::get_audio_type());
	}

	/**
	 * @param string $audio_filename
	 * @return integer
	 */
	protected function create_new_audio($audio_filename = null) {
		$expected_id = $this->get_expected_next_id(self::get_audio_type());

		$audio_filename = $this->create_test_file('wav', sys_get_temp_dir(), $audio_filename);
		$this->assertInternalType('string', $audio_filename);
		$this->assertTrue(file_exists($audio_filename));

		$file = ['error' => 0, 'tmp_name' => $audio_filename, 'name' => basename($audio_filename)];

		// mock move_uploaded_file to just copy file
		$this->mock_function_replace('move_uploaded_file', 'rename');
		$this->assertSameEquals($expected_id, (int) api_audio_fileupload($file));
		self::remove_mocked_functions('move_uploaded_file');

		return ['id' => $expected_id, 'filename' => basename($audio_filename)];
	}

	/**
	 * @return void
	 */
	protected function purge_all_audios() {
		$all_audio = api_audio_listall();
		$this->assertInternalType('array', $all_audio);
		foreach ($all_audio as $audio_id => $audio) {
			$this->assertTrue(api_audio_delete($audio_id));
		}
		$this->assertEmpty(api_audio_listall());
	}
}
