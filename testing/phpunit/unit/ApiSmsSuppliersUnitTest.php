<?php
/**
 * ApiSmsSuppliersUnitTest
 * Unit test for api_sms_suppliers.php
 *
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Class ApiSmsSuppliersUnitTest
 */
class ApiSmsSuppliersUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	public function supplier_select_sorts_by_capability_data_provider() {
		return [
			'with no provider with capabilities it should respect priority' => [
				null,
				['cap1', 'cap2'],
				[
					3 => ['priority' => 3, 'capabilities' => serialize([])],
					1 => ['priority' => 2, 'capabilities' => serialize([])],
					2 => ['priority' => 5, 'capabilities' => serialize([])]
				],
				[2 ,3, 1]
			],
			'with providers with capabilities it should sort by checking if capabilities are satisfied and then respect priority' => [
				null,
				['cap1', 'cap2'],
				[
					3 => ['priority' => 3, 'capabilities' => serialize([])],
					1 => ['priority' => 2, 'capabilities' => serialize(['cap1', 'cap2'])],
					2 => ['priority' => 5, 'capabilities' => serialize(['cap1', 'cap2'])],
					4 => ['priority' => 4, 'capabilities' => serialize(['cap2'])]
				],
				[2, 1, 4, 3]
			],
			'select providers filtered by capabilities and not sort by capabilities' => [
				['cap2', 'cap3'],
				[],
				[
					3 => ['priority' => 3, 'capabilities' => serialize([])],
					1 => ['priority' => 2, 'capabilities' => serialize(['cap1', 'cap2', 'cap3'])],
					2 => ['priority' => 5, 'capabilities' => serialize(['cap1', 'cap3'])],
					4 => ['priority' => 4, 'capabilities' => serialize(['cap2', 'cap3'])],
					5 => ['priority' => 1, 'capabilities' => serialize(['cap2', 'cap3', 'cap1'])]
				],
				[4, 1, 5]
			],
			'select providers filtered by capabilities and sort by capabilities' => [
				['cap2', 'cap3'],
				['cap1'],
				[
					3 => ['priority' => 3, 'capabilities' => serialize([])],
					1 => ['priority' => 2, 'capabilities' => serialize(['cap1', 'cap2', 'cap3'])],
					2 => ['priority' => 5, 'capabilities' => serialize(['cap1', 'cap3'])],
					4 => ['priority' => 4, 'capabilities' => serialize(['cap2', 'cap3'])],
					5 => ['priority' => 1, 'capabilities' => serialize(['cap2', 'cap3', 'cap1'])]
				],
				[1, 5, 4]
			]

		];
	}

	/**
	 * @group api_sms_supplier_select
	 * @param array|null $capabilities
	 * @param array      $sort_by_capabilities
	 * @param array      $providers
	 * @param array      $expected
	 * @dataProvider supplier_select_sorts_by_capability_data_provider
	 * @return void
	 */
	public function test_api_sms_supplier_select_select_by_capability_and_should_sort_by_capability(
		$capabilities,
		array $sort_by_capabilities,
		array $providers,
		array $expected
	) {
		$this->mock_function_value('api_restrictions_caps_sms_provider', false);
		$this->mock_function_value('api_sms_supplier_listall', $providers);
		$options = [];

		if ($sort_by_capabilities) {
			$options['sort_by_capabilities'] = $sort_by_capabilities;
		}
		$this->assertSameEquals($expected, api_sms_supplier_select($capabilities, $options));
	}

	/**
	 * @group api_sms_supplier_get_all_capabilities
	 * @return void
	 */
	public function test_api_sms_supplier_get_all_capabilities() {
		$this->assertSameEquals(
			[
				"aumobile" => "Australia - Mobile",
				"nzmobile" => "New Zealand - Mobile",
				"sgmobile" => "Singapore - Mobile",
				"gbmobile" => "Great Britain - Mobile",
				"phmobile" => "Philippines - Mobile",
				"trafficonshore" => "On shore traffic only",
				"othermobile" => "All other countries - Mobile"
			],
			api_sms_supplier_get_all_capabilities()
		);
	}
}
