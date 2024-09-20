<?php
/**
 * RunkitMockingTest
 * Unit test for Runkit mocking functions
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

use Exception;
use PHPUnit_Framework_Error;

/**
 * Runkit Test
 */
class RunkitMockingTest extends AbstractPhpunitUnitTest
{
	private $function_name = 'str_split';
	private $function_param_value = 'string';
	private $function_return_original = ['s', 't', 'r', 'i', 'n', 'g'];
	private $function_return_expected = 'mocked_value';

	/**
	 * Test mock_function_value()
	 *
	 * @return void
	 */
	public function test_mocked_function_exists() {
		// test PHP function exists
		$this->assertTrue(function_exists($this->function_name), "The function '$this->function_name' does not exists."); // same parameter
	}

	/**
	 * Test mock_function_value()
	 *
	 * @return void
	 */
	public function test_mock_function_value() {
		$function_name = $this->function_name;

		// test normal behavior of the function
		$this->assertSameEquals($this->function_return_original, $function_name($this->function_param_value));

		// mock the function
		$this->mock_function_value($function_name, $this->function_return_expected);

		// test mocked behavior of the function
		$this->assertNotEquals($this->function_return_original, $function_name($this->function_param_value));
		$this->assertSameEquals($this->function_return_expected, $function_name($this->function_param_value)); // same parameter
		$this->assertSameEquals($this->function_return_expected, $function_name('whatever')); // random string parameter
		$this->assertSameEquals($this->function_return_expected, $function_name()); // no parameter
		$this->assertSameEquals($this->function_return_expected, $function_name(1, 2, 3, 4)); // random parameters

		// mock non exiting function
		$function_name = 'non_existing_function';
		$function_return_expected = uniqid($function_name);
		$this->mock_function_value($function_name, $function_return_expected);
		$this->assertSameEquals($function_return_expected, $function_name());
	}

	/**
	 * Test mock_function_value() function already mocked
	 *
	 * @return void
	 */
	public function test_mock_function_value_backup_already_exists_error() {
		$function_name = $this->function_name;
		$this->setExpectedException(Exception::class, "RunKit Mock backup function __mock_func_backup_{$function_name}() already exists");

		$value = 'mock 1st time ok';
		$this->mock_function_value($function_name, $value);
		$this->assertSameEquals($value, $function_name());

		// re-mock the same function should throw an exception
		$this->mock_function_value($function_name, 'mock 2nd time fail');
	} // @codeCoverageIgnore

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function mock_function_param_value_errors_data() {
		return [
			// Exceptions param is not an array
			[
				['Not An Array'],
				"Param #0 is not an array. Params suite must be an array like ['params' => ..., 'return' => ...]"
			],
			[
				[['return' => false], 'Not An Array'],
				"Param #1 is not an array. Params suite must be an array like ['params' => ..., 'return' => ...]"
			],

			// Exceptions param is empty
			[
				[['params' => null, 'return' => false]],
				'List of Params not an array or has empty values'
			],

			// Exceptions key return is missing
			[
				[['whatever' => false]],
				"Param #0 must have a 'return' key"
			],
			[
				[['return' => false], ['whatever' => false]],
				"Param #1 must have a 'return' key"
			],
		];
	}

	/**
	 * Test mock_function_param_value() errors
	 *
	 * @dataProvider mock_function_param_value_errors_data
	 * @param array  $params_suite
	 * @param string $exception_message
	 * @return void
	 */
	public function test_mock_function_param_value_errors(array $params_suite, $exception_message = false) {
		$this->setExpectedException(Exception::class, $exception_message);
		$this->mock_function_param_value($this->function_name, $params_suite);
	} // @codeCoverageIgnore

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function test_mock_function_param_data() {
		return [
			[$this->function_return_original, null], // no params
			[$this->function_return_original, []], // no params
			[['1', '2', '3'], [0 => '123']], // set 1st param
			[['st', 'ri', 'ng'], [1 => 2]], // set 2nd param
		];
	}

	/**
	 * Test mock_function_param()
	 *
	 * @dataProvider test_mock_function_param_data
	 * @param array $expected_value
	 * @param mixed $params_value
	 * @return void
	 */
	public function test_mock_function_param(array $expected_value, $params_value) {
		$function_name = $this->function_name;

		$this->mock_function_param($this->function_name, $params_value);
		$this->assertSameEquals($expected_value, $function_name($this->function_param_value));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function mock_function_param_errors_data() {
		return [
			// Param #5 does not exists
			[[5 => 'wrong_param'], Exception::class, 'Can not set parameter with key #5. Function str_split() accepts only 2 parameters'],
			// Wrong param 1 type
			[[0 => ['wrong_type']], PHPUnit_Framework_Error::class, 'str_split() expects parameter 1 to be string, array given'],
			// Wrong param 2 type
			[[1 => 'wrong_type'], PHPUnit_Framework_Error::class, 'str_split() expects parameter 2 to be long, string given'],
		];
	}

	/**
	 * Test mock_function_param() errors
	 *
	 * @dataProvider mock_function_param_errors_data
	 * @param array  $params_value
	 * @param string $exception_class
	 * @param string $exception_message
	 * @return void
	 */
	public function test_mock_function_param_errors(array $params_value, $exception_class, $exception_message) {
		$function_name = $this->function_name;

		$this->setExpectedException($exception_class, $exception_message);
		$this->mock_function_param($this->function_name, $params_value);
		$function_name($this->function_param_value);
	} // @codeCoverageIgnore

	/**
	 * Test mock_function_param_value()
	 *
	 * @return void
	 */
	public function test_mock_function_param_value() {
		$function_name = $this->function_name;

		// mock the function from params
		$this->mock_function_param_value(
			$function_name,
			[
				['params' => ['mockedParam#0'], 'return' => $this->function_return_expected],
				['params' => ['mockedParam#1'], 'return' => 'return_1'],
				['params' => ['abc', 3], 'return' => ['return_2']],
				['params' => [1 => 'only 2nd param'], 'return' => 'return_3'],
			]
		);

		// test suite param #0
		$this->assertSameEquals($this->function_return_expected, $function_name('mockedParam#0'));

		// test suite param #1
		$this->assertSameEquals('return_1', $function_name('mockedParam#1'));

		// test suite param #2
		$this->assertSameEquals(['a', 'b', 'c'], $function_name('abc'));
		$this->assertSameEquals(['ab', 'c'], $function_name('abc', 2));
		$this->assertSameEquals(['abc'], $function_name('abc', '3')); // test cast 3 and '3'
		$this->assertSameEquals(['return_2'], $function_name('abc', 3));

		// test only 2nd param
		$this->assertSameEquals('return_3', $function_name('whatever', 'only 2nd param'));
		$this->assertSameEquals('return_3', $function_name(false, 'only 2nd param'));
		$this->assertSameEquals('return_3', $function_name('abc', 'only 2nd param'));
		// test suites are ordered
		$this->assertSameEquals($this->function_return_expected, $function_name('mockedParam#0', 'only 2nd param'));
		$this->assertSameEquals('return_1', $function_name('mockedParam#1', 'only 2nd param'));

		// test default value
		$this->assertSameEquals($this->function_return_original, $function_name($this->function_param_value));
		// test default value with 2nd param
		$this->assertSameEquals(['str', 'ing'], $function_name($this->function_param_value, 3));

		// test listener
		$function_return_expected = 'has been called?';
		self::remove_mocked_functions();
		$this->listen_mocked_function($function_name);
		$this->mock_function_param_value(
			$function_name,
			[
				['params' => ['mockedParam#0'], 'return' => $function_return_expected],
			]
		);
		$this->assertSameEquals($function_return_expected, $function_name('mockedParam#0'));
		self::remove_mocked_functions();
		$this->assertListenMockFunction(
			$function_name,
			[['args' => ['mockedParam#0'], 'return' => $function_return_expected]]
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function mock_function_get_param_data() {
		return [
			// Param key wrong value
			[false, Exception::class, 'Parameter key value must be an integer greater than 0.'],
			[null, Exception::class, 'Parameter key value must be an integer greater than 0.'],
			['', Exception::class, 'Parameter key value must be an integer greater than 0.'],
			[0, Exception::class, 'Parameter key value must be an integer greater than 0.'],

			// Success
			[1],
			[rand(1, 5)],
		];
	}

	/**
	 * Test mock_function_get_param()
	 *
	 * @dataProvider mock_function_get_param_data
	 * @param mixed $param_key
	 * @param mixed $exception_class
	 * @param mixed $exception_message
	 * @return void
	 */
	public function test_mock_function_get_param($param_key, $exception_class = null, $exception_message = null) {
		$this->setExpectedException($exception_class, $exception_message);
		$object = $this->mock_function_get_param($param_key);
		$this->assertInstanceOf('stdClass', $object);
		$this->assertObjectHasAttribute('is_mock_function_get_param', $object);
		$this->assertTrue($object->is_mock_function_get_param);
		$this->assertObjectHasAttribute('parameter_key', $object);
		$this->assertSameEquals(($param_key - 1), $object->parameter_key);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function mock_function_regexp_param_data() {
		return [
			['/whatever/'],
		];
	}

	/**
	 * Test mock_function_get_param()
	 *
	 * @dataProvider mock_function_regexp_param_data
	 * @param string $regexp
	 * @return void
	 */
	public function test_mock_function_regexp_param($regexp) {
		$object = $this->mock_function_regexp_param($regexp);
		$this->assertInstanceOf('stdClass', $object);
		$this->assertObjectHasAttribute('is_mock_regexp_param', $object);
		$this->assertTrue($object->is_mock_regexp_param);
		$this->assertObjectHasAttribute('regexp', $object);
		$this->assertSameEquals($regexp, $object->regexp);
	}

	/**
	 * Test mock_function_param_value() using mock_function_get_param() for return value
	 *
	 * @return void
	 */
	public function test_mock_function_param_value_with_mock_function_get_param() {
		$function_name = $this->function_name;

		// mock the function from params
		$this->mock_function_param_value(
			$function_name,
			[
				['params' => ['Param_value#1'], 'return' => $this->mock_function_get_param(2)],
				['params' => ['Param_value#2'], 'return' => $this->mock_function_get_param(1)],
			],
			$this->mock_function_get_param(1)
		);

		// test mockedParam#1 returns param value #2
		$this->assertSameEquals('Param_value#2', $function_name('Param_value#1', 'Param_value#2'));

		// test mockedParam#2 returns param value #1
		$this->assertSameEquals('whatever', $function_name('whatever', 'Param_value#2'));

		// test default returns param value #1
		$this->assertSameEquals(['whatever#1'], $function_name(['whatever#1'], ['whatever#2']));
	}

	/**
	 * Test mock_function_param_value() using mock_function_get_param() error key for param
	 *
	 * @return void
	 */
	public function test_mock_function_param_value_with_mock_function_get_param_error_param() {
		$function_name = $this->function_name;

		$this->setExpectedException(Exception::class, 'Parameter key #3 does not exists.');
		// param #3 does not exists
		$this->mock_function_param_value(
			$function_name,
			[
				['params' => ['whatever'], 'return' => $this->mock_function_get_param(3)],
			]
		);

		$function_name('whatever');
	} // @codeCoverageIgnore

	/**
	 * Test mock_function_param_value() using mock_function_get_param() error key for default return
	 *
	 * @return void
	 */
	public function test_mock_function_param_value_with_mock_function_get_param_error_default_return() {
		$function_name = $this->function_name;
		$exception_class = Exception::class;

		$this->setExpectedException($exception_class, 'Parameter key #3 does not exists.');
		// param #3 does not exists
		$this->mock_function_param_value(
			$function_name,
			[
				['params' => ['param#1'], 'return' => true],
			],
			$this->mock_function_get_param(3)
		);

		$function_name('whatever');
	} // @codeCoverageIgnore

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function mock_function_param_value_with_default_value_data() {
		return [
			[''],
			[null],
			[false],
			[true],
			['whatever'],
			[[1, 2]]
		];
	}

	/**
	 * Test mock_function_param_value() with default value
	 *
	 * @dataProvider mock_function_param_value_with_default_value_data
	 * @param mixed $default_value
	 * @return void
	 */
	public function test_mock_function_param_value_with_default_value($default_value) {
		$function_name = $this->function_name;

		// mock the function from params
		$this->mock_function_param_value(
			$function_name,
			[
				['params' => ['mockedParam'], 'return' => $this->function_return_expected],
			],
			$default_value
		);

		$this->assertSameEquals($this->function_return_expected, $function_name('mockedParam'));
		$this->assertSameEquals($default_value, $function_name('whatever'));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function remove_mocked_functions_data() {
		return [
			[false],
			[true],
		];
	}

	/**
	 * Test remove_mocked_functions()
	 *
	 * @dataProvider remove_mocked_functions_data
	 * @param boolean $filter
	 * @return void
	 */
	public function test_remove_mocked_functions($filter = false) {
		$function_name = $this->function_name;

		$this->mock_function_value($function_name, $this->function_return_expected);

		// function back to original behavior
		self::remove_mocked_functions($filter ? $function_name : null);

		// test the orignal behavior of the function
		$this->assertNotEquals($this->function_return_expected, $function_name($this->function_param_value));
		$this->assertSameEquals($this->function_return_original, $function_name($this->function_param_value));
		$this->assertNotEquals($this->function_return_original, $function_name('whatever'));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function mock_function_replace_errors_data() {
		return [
			[
				'function_does_not_exists',
				'function_does_not_exists',
				'Function function_does_not_exists() not found.'
			],
			[
				'trim',
				'function_does_not_exists',
				'Function function_does_not_exists() not found.'
			],
		];
	}

	/**
	 * Test mock_function_replace() errors
	 *
	 * @dataProvider mock_function_replace_errors_data
	 * @param string $function
	 * @param string $new_function
	 * @param mixed  $exception_message
	 * @return void
	 */
	public function test_mock_function_replace_errors($function, $new_function, $exception_message = false) {
		$this->setExpectedException(Exception::class, $exception_message);
		$this->mock_function_replace($function, $new_function);
	} // @codeCoverageIgnore

	/**
	 * Test mock_function_replace()
	 *
	 * @return void
	 */
	public function test_mock_function_replace() {
		$this->assertSameEquals('value', trim(' value '));
		$this->assertSameEquals(' value ', strtolower(' VALUE '));

		$this->mock_function_replace('trim', 'strtolower');

		$this->assertSameEquals(' value ', trim(' value '));
		$this->assertSameEquals(' value ', trim(' VALUE '));
		$this->assertSameEquals(' value ', strtolower(' VALUE '));

		// use listener
		self::remove_mocked_functions();
		$this->listen_mocked_function('trim');
		$this->mock_function_replace('trim', 'strtolower');
		$this->assertSameEquals('has been called?', trim('HAS BEEN CALLED?'));
		self::remove_mocked_functions();
		$this->assertListenMockFunction(
			'trim',
			[['args' => ['HAS BEEN CALLED?'], 'return' => 'has been called?']]
		);
	}
}
