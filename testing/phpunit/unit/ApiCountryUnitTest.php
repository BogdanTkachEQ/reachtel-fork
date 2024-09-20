<?php
/**
 * ApiCountryTest
 * Unit test for api_country.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Api Country Unit Test class
 */
class ApiCountryUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @group  api_country_supported_rating
	 * @return void
	 */
	public function test_api_country_supported_rating() {
		$this->assertInternalType(
			'array',
			$all = api_country_supported_rating()
		);

		$this->assertCount(5, $all);

		$this->assertArrayHasKey('AU', $all);
		$this->assertArrayHasKey('NZ', $all);
		$this->assertArrayHasKey('SG', $all);
		$this->assertArrayHasKey('GB', $all);
		$this->assertArrayHasKey('PH', $all);
	}

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
			['Country Name', 'CODE'],
			['Country Name', 'code'],
			['Country Name', ' code '],
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
		$this->mock_function_value('api_country_all', ['CODE' => 'Country Name']);
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
			['CODE', 'Country Name'],
			['CODE', 'country name'],
			['CODE', '  country name  '],
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
		$this->mock_function_value('api_country_all', ['CODE' => 'Country Name']);
		$this->assertSameEquals(
			$expected_value,
			api_country_get_code($name)
		);
	}
}
