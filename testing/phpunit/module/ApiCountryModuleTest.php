<?php
/**
 * ApiCountryModuleTest
 * Module test for api_campaigns.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

/**
 * Api Country Module Test
 */
class ApiCountryModuleTest extends AbstractPhpunitModuleTest
{
	/**
	 * @group  api_country_all
	 * @return void
	 */
	public function test_api_country_all() {
		$this->assertInternalType(
			'array',
			$all = api_country_all()
		);
		$this->assertCount(250, $all);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_country_get_name_data() {
		return [
			// Failures country does not exists
			[false, false],
			[false, null],
			[false, ''],
			[false, 'whatever'],

			// Success
			['Australia', 'au'],
			['Australia', 'AU'],
			['New Zealand', 'Nz'],
			['France', ' FR '],
			['Mexico', ' mx '],
		];
	}

	/**
	 * @group        api_country_get_name
	 * @dataProvider api_country_get_name_data
	 * @param mixed  $expected_value
	 * @param string $name
	 * @return void
	 */
	public function test_api_country_get_name($expected_value, $name) {
		$this->assertSameEquals(
			$expected_value,
			api_country_get_name($name)
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_country_get_code_data() {
		return [
			// Failures country does not exists
			[false, false],
			[false, null],
			[false, ''],
			[false, 'whatever'],

			// Success
			['DO', 'dominican republic'],
			['UA', 'Ukraine'],
			['GU', 'GUAM'],
			['ZA', 'SOUTH Africa   '],
			['MX', '  mExIcO  '],
		];
	}

	/**
	 * @group        api_country_get_code
	 * @dataProvider api_country_get_code_data
	 * @param mixed  $expected_value
	 * @param string $name
	 * @return void
	 */
	public function test_api_country_get_code($expected_value, $name) {
		$this->assertSameEquals(
			$expected_value,
			api_country_get_code($name)
		);
	}
}
