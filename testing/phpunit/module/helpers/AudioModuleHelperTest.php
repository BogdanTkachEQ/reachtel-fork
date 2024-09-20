<?php
/**
 * AudioModuleHelperTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Audio Module Helper Test
 */
class AudioModuleHelperTest extends AbstractModuleHelperTest
{
	use AudioModuleHelper;

	const EXPECTED_TYPE = 'AUDIO';

	/**
	 * @group create_new_audio
	 * @return void
	 */
	public function test_create_new_audio() {
		$audio_filename  = uniqid('test_create_new_audio') . '.wav';

		$expected_id = $this->get_expected_next_audio_id();
		$audio = $this->create_new_audio($audio_filename);

		// return an array
		$this->assertInternalType('array', $audio);
		$this->assertCount(2, $audio);

		// check array keys
		$this->assertArrayHasKey('id', $audio);
		$this->assertArrayHasKey('filename', $audio);

		// check array content
		$this->assertEquals($expected_id, $audio['id']);
		if ($audio_filename) {
			$this->assertSameEquals($audio_filename, $audio['filename']);
		}

		$this->assertTrue(api_audio_delete($audio['id']));
	}

	/**
	 * @group get_audio_valid_extensions
	 * @return void
	 */
	public function test_get_audio_valid_extensions() {
		$this->assertSameEquals(['wav', 'mp3'], $this->get_audio_valid_extensions());
	}
}
