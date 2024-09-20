<?php
/**
 * ApiVoiceServersUnitTest
 * Unit test for api_voice_servers.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Api Voice Servers Unit Test class
 */
class ApiVoiceServersUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * Type value
	 */
	const TYPE = 'VOICESUPPLIER';

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_voice_servers_delete_data() {
		return [
			// Failures serverid
			[false, null],
			[false, false],
			[false, ''],
			[false, 'non-numeric'],

			// Failures voice server exists
			[false, 1, true],

			// Failures is not a dir
			[false, 2, false, false],

			// Failures dir is not empty
			[false, 3, false, true, ['some-files']],

			// Success
			[true]
		];
	}

	/**
	 * @dataProvider api_voice_servers_delete_data
	 * @param mixed   $expected_value
	 * @param mixed   $serverid
	 * @param boolean $checkkeyexists
	 * @param boolean $is_dir
	 * @param array   $glob
	 * @return void
	 */
	public function test_api_voice_servers_delete($expected_value, $serverid = 1, $checkkeyexists = false, $is_dir = true, array $glob = []) {
		$this->mock_function_value('api_keystore_checkkeyexists', $checkkeyexists);
		$this->mock_function_value('api_voice_servers_setting_getsingle', 'servername');
		$this->mock_function_value('is_dir', $is_dir);
		$this->mock_function_value('glob', $glob);
		$this->mock_function_value('rmdir', null);
		$this->mock_function_value('api_keystore_purge', null);

		$this->assertSameEquals($expected_value, api_voice_servers_delete($serverid));
	}
}
