<?php
/**
 * ApiMiscUnitTest
 * Unit test for api_misc.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Api Misc Unit Test class
 */
class ApiMiscUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_misc_natcasesortbykey_data() {
		$array = [
			3 => [1 => 3, 2 => 20, 'a' => 'val-a-2'],
			2 => [1 => 2, 2 => 10, 'a' => 'val-a-3'],
			'a' => [1 => 4, 2 => 30, 'a' => 'val-a-20'],
			1 => [1 => 1, 2 => 40]
		];

		$bad_array = [
			2 => ['a' => 1],
			1 => 'not-an-array'
		];

		return [
			// failure array
			[false, false, 'key'],
			[false, null, 'key'],
			[false, '', 'key'],

			// failure key
			[false, [], false],
			[false, [], null],
			[false, [], ''],

			// success but bad format array
			[
				[
					1 => 'not-an-array',
					2 => ['a' => 1]
				],
				$bad_array,
				'a'
			],

			// success but bad format array and key does not exists
			[
				$bad_array,
				$bad_array,
				'whatever'
			],

			// success
			[$array, $array, 'whatever'],
			[
				[
					1 => [1 => 1, 2 => 40],
					2 => [1 => 2, 2 => 10, 'a' => 'val-a-3'],
					3 => [1 => 3, 2 => 20, 'a' => 'val-a-2'],
					'a' => [1 => 4, 2 => 30, 'a' => 'val-a-20'],
				],
				$array,
				1
			],
			[
				[
					2 => [1 => 2, 2 => 10, 'a' => 'val-a-3'],
					3 => [1 => 3, 2 => 20, 'a' => 'val-a-2'],
					'a' => [1 => 4, 2 => 30, 'a' => 'val-a-20'],
					1 => [1 => 1, 2 => 40]
				],
				$array,
				2
			],
			[
				[
					1 => [1 => 1, 2 => 40],
					3 => [1 => 3, 2 => 20, 'a' => 'val-a-2'],
					2 => [1 => 2, 2 => 10, 'a' => 'val-a-3'],
					'a' => [1 => 4, 2 => 30, 'a' => 'val-a-20'],
				],
				$array,
				'a'
			],
		];
	}

	/**
	 * @dataProvider api_misc_natcasesortbykey_data
	 * @param mixed $expected_value
	 * @param mixed $array
	 * @param mixed $key
	 * @return void
	 */
	public function test_api_misc_natcasesortbykey($expected_value, $array, $key) {
		$this->assertSameEquals($expected_value, api_misc_natcasesortbykey($array, $key));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_misc_sanitize_upload_filename_data() {
		return [
			// failtures
			[false, false],
			[false, null],
			[false, ''],
			[false, 'no-extension'],
			[false, '/path/file/noextension'],
			[false, '({[<!@#$%^&*\'+:"?>]}).pdf'],
			[false, '***.***'],

			// success file path
			["/path/to/file/canyousanitizeme.pdf", "/path/to/file/can you sanitize me?.pdf"],
			["./canyousanitizeme.pdf", "./can you sanitize me?.pdf"],
			["canyousanitizeme.pdf", "can you sanitize me?.pdf"],
			["canyousanitizeme.pdf", "can.you.sanitize.me?.pdf"],

			// success specific chars
			["tabnewlinespace.mp3", "	tab	\nnewline\n space .mp3"],
			["test-test_test.pdf", "test-test_test.pdf"],
			["itsatest.pdf", "it's a test.pdf"],
			["Case-Insensitive.wav", "Case-Insensitive.wav"],
			["Underscore_are_allowed.txt", "Underscore_are_allowed.txt"],
			["test.pdf", "test ({<!@#$%^\&*+.?,:}><php>).pdf"],
			["testphp.wav", "test.php.wav"],
		];
	}

	/**
	 * @dataProvider api_misc_sanitize_upload_filename_data
	 * @param mixed $expected_value
	 * @param mixed $filename
	 * @return void
	 */
	public function test_api_misc_sanitize_upload_filename($expected_value, $filename) {
		$this->assertSameEquals($expected_value, api_misc_sanitize_upload_filename($filename));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_misc_sftp_config_validate_data() {
		return [
			// failures
			[false, []],
			[false, [
				'remotefile' => '/out/bob.txt',
				'localfile' => 'bob.txt'
			]],
			[false, [
				'hostname' => 'example.com',
				'localfile' => 'bob.txt'
			]],
			[false, [
				'hostname' => 'example.com',
				'remotefile' => '/out/bob.txt',
			]],

			// passthrough and add port
			[[
				'hostname' => 'example.com',
				'remotefile' => '/out/bob.txt',
				'localfile' => 'bob.txt',
				'port' => 22,
			], [
				'hostname' => 'example.com',
				'remotefile' => '/out/bob.txt',
				'localfile' => 'bob.txt',
			]],

			// passthrough with port
			[[
				'hostname' => 'example.com',
				'remotefile' => '/out/bob.txt',
				'localfile' => 'bob.txt',
				'port' => 222,
			], [
				'hostname' => 'example.com',
				'remotefile' => '/out/bob.txt',
				'localfile' => 'bob.txt',
				'port' => 222,
			]],

			// sftp.reachtel.com.au with reachtelautomation username
			[[
				'hostname' => 'sftp.reachtel.com.au',
				'remotefile' => '/out/bob.txt',
				'localfile' => 'bob.txt',
				'username' => 'reachtelautomation',
				'port' => 22,
				'pubkeyfile' => BASE_LOCATION . '/pgp/reachtel-sftp.pub',
				'privkeyfile' => BASE_LOCATION . '/pgp/reachtel-sftp.pem',
				'passphrase' => 'fakesftppassphrase',
			], [
				'hostname' => 'sftp.reachtel.com.au',
				'remotefile' => '/out/bob.txt',
				'localfile' => 'bob.txt',
				'username' => 'reachtelautomation',
				'password' => 'oldpassword',
			]],
			// sftp.reachtel.com.au without reachtelautomation username
			[[
				'hostname' => 'sftp.reachtel.com.au',
				'remotefile' => '/out/bob.txt',
				'localfile' => 'bob.txt',
				'username' => 'anormaluser',
				'password' => 'anormalpassword',
				'port' => 22,
			], [
				'hostname' => 'sftp.reachtel.com.au',
				'remotefile' => '/out/bob.txt',
				'localfile' => 'bob.txt',
				'username' => 'anormaluser',
				'password' => 'anormalpassword',
			]],

			// xfer.veda.com.au with reachtel username
			[[
				'hostname' => 'xfer.veda.com.au',
				'remotefile' => '/out/bob.txt',
				'localfile' => 'bob.txt',
				'username' => 'reachtel',
				'port' => 22,
				'pubkeyfile' => BASE_LOCATION . '/pgp/reachtel-sftp.pub',
				'privkeyfile' => BASE_LOCATION . '/pgp/reachtel-sftp.pem',
				'passphrase' => 'fakesftppassphrase',
			], [
				'hostname' => 'xfer.veda.com.au',
				'remotefile' => '/out/bob.txt',
				'localfile' => 'bob.txt',
				'username' => 'reachtel',
				'password' => 'oldpassword',
			]],
			// xfer.veda.com.au without reachtel username
			[[
				'hostname' => 'xfer.veda.com.au',
				'remotefile' => '/out/bob.txt',
				'localfile' => 'bob.txt',
				'username' => 'anormaluser',
				'password' => 'anormalpassword',
				'port' => 22,
			], [
				'hostname' => 'xfer.veda.com.au',
				'remotefile' => '/out/bob.txt',
				'localfile' => 'bob.txt',
				'username' => 'anormaluser',
				'password' => 'anormalpassword',
			]],
		];
	}

	/**
	 * @dataProvider api_misc_sftp_config_validate_data
	 * @group _api_misc_sftp_config_validate
	 * @param mixed $expected_value
	 * @param array $options
	 * @return void
	 */
	public function test_api_misc_sftp_config_validate($expected_value, array $options) {
		$this->assertSameEquals($expected_value, _api_misc_sftp_config_validate($options));
	}

	/**
	 * @return array
	 */
	public function api_misc_is_cli_data_provider() {
		return [
			'is cli' => ['cli', true],
			'is not cli' => ['apache', false]
		];
	}

	/**
	 * @dataProvider api_misc_is_cli_data_provider
	 * @group api_misc_is_cli
	 * @param string  $sapi_name
	 * @param boolean $expected
	 * @return void
	 */
	public function test_api_misc_is_cli($sapi_name, $expected) {
		$this->mock_function_value('php_sapi_name', $sapi_name);
		$this->assertSameEquals($expected, api_misc_is_cli());
	}
}
