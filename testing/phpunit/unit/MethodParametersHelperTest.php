<?php
/**
 * MethodParametersHelperTest
 * Test for MethodParametersHelper Class
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

use Exception;
use testing\helpers\MethodParametersHelper;

/**
 * Test for MethodParametersHelper Class
 */
class MethodParametersHelperTest extends AbstractPhpunitUnitTest
{
	use MethodParametersHelper;

	/**
	 * @return void
	 */
	public function test_get_test_data_from_parameters_default_values_exception() {
		$this->setExpectedException(Exception::class, 'Method get_test_data_from_parameters_default_values() must be call only in a data provider');
		$this->get_test_data_from_parameters_default_values();
	} // @codeCoverageIgnore

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function get_test_data_from_parameters_default_values_data_provider() {
		return [[0], [1], [2], [3], [4], [5]];
	}

	/**
	 * @dataProvider get_test_data_from_parameters_default_values_data_provider
	 * @param integer $key
	 * @param mixed   $param2
	 * @param mixed   $param3
	 * @param array   $param4
	 * @return void
	 */
	public function test_get_test_data_from_parameters_default_values($key, $param2 = true, $param3 = 2, array $param4 = []) {
		$this->get_test_data_from_parameters_default_values_data($key);
	}

	/**
	 * @param integer $key
	 * @return void
	 */
	private function get_test_data_from_parameters_default_values_data($key) {
		$expected_values = [
			[['key' => $key], [$key, true, 2, []]],
			[['key' => $key, 'param2' => false], [$key, false, 2, []]],
			[['key' => $key, 'param3' => 99], [$key, true, 99, []]],
			[['key' => $key, 'param4' => [1, 2, 3]], [$key, true, 2, [1, 2, 3]]],
			[['key' => $key, 'param2' => 1, 'param3' => 2, 'param4' => [3]], [$key, 1, 2, [3]]],
			[[], [], Exception::class, "Default parameter value for 'key' is not available and should be set"],
		];

		// assert $expected_values array
		$this->assertArrayHasKey($key, $expected_values, "Wrong key {$key}");
		$expected_value = $expected_values[$key];

		// assert $expected_value array
		$this->assertInternalType('array', $expected_value);
		$this->assertArrayHasKey(0, $expected_value);
		$this->assertArrayHasKey(1, $expected_value);

		if (isset($expected_value[2]) && isset($expected_value[3])) {
			$this->setExpectedException($expected_value[2], $expected_value[3]);
		}
		$parameters_default_values = $this->get_test_data_from_parameters_default_values($expected_value[0]);
		$this->assertInternalType('array', $parameters_default_values);
		$this->assertSameEquals($expected_value[1], $parameters_default_values);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function get_test_data_from_parameters_combinations_data() {
		$closure = function($mandatory, $optional = true) {
			return 1;
		};
		$closure_optional = function($opt1 = null, $opt2 = null) {
			return 2;
		};
		$dateTime = new \DateTime();

		return [
			// Failure invalid callback
			[[], false, [], Exception::class, 'Parameter is not callable'],
			[[], null, [], Exception::class, 'Parameter is not callable'],
			[[], '', [], Exception::class, 'Parameter is not callable'],
			[[], 'whatever', [], Exception::class, 'Parameter is not callable'],
			[[], [MethodParametersHelperTest::class, 'whatever'], [], Exception::class, 'Parameter is not callable'],
			[[], [$this, 'whatever'], [], Exception::class, 'Parameter is not callable'],

			// Failure function mandatory param missing
			[[], 'str_split', [], Exception::class, "Default parameter value for 'str' is not available and should be set"],
			[[], 'str_split', ['str'], Exception::class, "Default parameter value for 'str' is not available and should be set"],
			[[], 'api_misc_namesearch', [], Exception::class, "Default parameter value for 'names' is not available and should be set"],
			[[], 'api_misc_namesearch', ['names' => 'names'], Exception::class, "Default parameter value for 'needle' is not available and should be set"],
			[[], 'api_misc_namesearch', ['needle' => 'needle'], Exception::class, "Default parameter value for 'names' is not available and should be set"],

			// Failure closure mandatory param missing
			[[], $closure, [], Exception::class, "Default parameter value for 'mandatory' is not available and should be set"],
			[[], $closure, ['mandatory'], Exception::class, "Default parameter value for 'mandatory' is not available and should be set"],

			// Failure class method mandatory param missing
			[[], [\DateTime::class, 'createFromFormat'], [], Exception::class, "Default parameter value for 'format' is not available and should be set"],
			[[], [\DateTime::class, 'createFromFormat'], ['whatever'], Exception::class, "Default parameter value for 'format' is not available and should be set"],

			// api_misc_namesearch($names, $needle, $startonly = false, $searchkey = false)
			// Success
			[
				[
					[['n1'], '-', false, false],
				],
				'api_misc_namesearch',
				['names' => ['n1'], 'needle' => '-']
			],
			[
				[
					[['n2'], '*', true, false],
				],
				'api_misc_namesearch',
				['names' => ['n2'], 'needle' => '*', 'startonly' => true]
			],
			[
				[
					[['n3'], '+', false, true],
				],
				'api_misc_namesearch',
				['names' => ['n3'], 'needle' => '+', 'searchkey' => true]
			],
			[
				[
					[['n1'], '#', false, false],
					[['n2'], '#', false, false]
				],
				'api_misc_namesearch',
				['names' => $this->add_parameter_possibilities([['n1'], ['n2']]), 'needle' => '#']
			],
			[
				[
					[['n5'], '@', false, true],
				],
				'api_misc_namesearch',
				['names' => ['n5'], 'needle' => '@', 'searchkey' => $this->add_parameter_possibilities([true])]
			],
			[
				[
					[['n1'], '<', false, true],
					[['n1'], '<', true, true],
					[['n1'], '>', false, true],
					[['n1'], '>', true, true],
					[['n2'], '<', false, true],
					[['n2'], '<', true, true],
					[['n2'], '>', false, true],
					[['n2'], '>', true, true],
				],
				'api_misc_namesearch',
				[
					'names' => $this->add_parameter_possibilities([['n1'], ['n2']]),
					'needle' => $this->add_parameter_possibilities(['<', '>']),
					'startonly' => $this->add_parameter_possibilities([false, true]),
					'searchkey' => $this->add_parameter_possibilities([true])
				]
			],
			// $dateTime::setDate( int $year , int $month , int $day )
			// Success
			[
				[
					[2010, 10, 1],
					[2010, 10, 20],
					[2011, 10, 1],
					[2011, 10, 20],
				],
				[$dateTime, 'setDate'],
				[
					'year' => $this->add_parameter_possibilities([2010, 2011]),
					'month' => 10,
					'day' => $this->add_parameter_possibilities([1, 20])
				]
			],
			// Success $closure
			[
				[
					[1, true],
					[1, false],
					[2, true],
					[2, false],
				],
				$closure,
				[
					'mandatory' => $this->add_parameter_possibilities([1, 2]),
					'optional' => $this->add_parameter_possibilities([true, false]),
				]
			],
			// Success $closure_optional function($opt1 = null, $opt2 = null)
			[
				[
					[null, null],
					[null, true],
					[null, false],
					[1, null],
					[1, true],
					[1, false],
				],
				$closure_optional,
				[
					'opt1' => $this->add_parameter_possibilities([null, 1]),
					'opt2' => $this->add_parameter_possibilities([null, true, false]),
				]
			],
		];
	}

	/**
	 * @dataProvider get_test_data_from_parameters_combinations_data
	 * @param mixed  $expected_value
	 * @param string $function
	 * @param array  $parameters
	 * @param mixed  $expected_exception
	 * @param mixed  $expected_message
	 * @return void
	 */
	public function test_get_test_data_from_parameters_combinations($expected_value, $function, array $parameters = [], $expected_exception = null, $expected_message = null) {
		$this->setExpectedException($expected_exception, $expected_message);
		$parameters_combinations = $this->get_test_data_from_parameters_combinations($function, $parameters);
		$this->assertSameEquals($expected_value, $parameters_combinations);
	}
}
