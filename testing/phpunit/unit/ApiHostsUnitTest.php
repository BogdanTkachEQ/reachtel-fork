<?php
/**
 * ApiHostsTest
 * Unit test for api_hosts.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Api Hosts Unit Test class
 */
class ApiHostsUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_hosts_gettrack_data() {
		return [
			// constant not defined
			['https://track.reachtel.com.au'],
			// constant defined
			['https://custom.track', 'custom.track'],
			// constant not defined without scheme
			['track.reachtel.com.au', false, false],
			// constant defined without scheme
			['custom.track.without.scheme', 'custom.track.without.scheme', false],
		];
	}

	/**
	 * @group api_hosts_gettrack
	 * @dataProvider api_hosts_gettrack_data
	 * @param string  $expected_value
	 * @param boolean $constant
	 * @param boolean $scheme
	 * @return void
	 */
	public function test_api_hosts_gettrack($expected_value, $constant = false, $scheme = true) {
		$this->mock_function_value('defined', $constant);
		$this->mock_function_value('constant', $constant);

		$this->assertSameEquals(
			$expected_value,
			api_hosts_gettrack($scheme)
		);

		$this->remove_mocked_functions(); // important
	}
}
