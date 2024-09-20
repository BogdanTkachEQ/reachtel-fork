<?php
/**
 * ApiMiscModuleTest
 * Module test for api_misc.php
 *
 * @author		christopher.colborne@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

/**
 * Api Misc Module Test class
 */
class ApiMiscModuleTest extends AbstractPhpunitModuleTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_misc_sftp_get_connection_failure_message_data() {
		return [
			// missing connection details
			['Sorry, that is not a valid host name', []],

			['Sorry, that is not a valid host name', ['port' => 22]],

			['Sorry, that is not a valid port', [
				'hostname' => 'example.com',
			]],

			['Sorry, that is not a valid username', [
				'hostname' => 'example.com',
				'port' => 22,
			]],

			// bad host with port
			["Sorry, we couldn't connect to that server: bob@example.com:22", [
				'hostname' => 'example.com',
				'port' => 22,
				'username' => 'bob',
			]],

			// bad host with port and username
			["Sorry, we couldn't connect to that server: reachtelautomation@example.com:22", [
				'hostname' => 'example.com',
				'port' => 22,
				'username' => 'reachtelautomation',
			]],
		];
	}

	/**
	 * @dataProvider api_misc_sftp_get_connection_failure_message_data
	 * @group _api_misc_sftp_get_connection
	 * @param mixed $expected_value
	 * @param array $options
	 * @return void
	 */
	public function test_api_misc_sftp_get_connection_failure_message($expected_value, array $options) {
		$hostname = isset($options['hostname']) ? $options['hostname'] : '';
		$port = isset($options['port']) ? $options['port'] : '';
		$username = isset($options['username']) ? $options['username'] : '';

		// mock ssh2_connect to fail
		$this->mock_function_value('ssh2_connect', false);

		_api_misc_sftp_get_connection($options);
		$this->assertSameEquals($expected_value, api_error_printiferror(['return' => true]));
	}
}
