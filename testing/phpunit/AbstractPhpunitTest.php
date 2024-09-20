<?php
/**
 * AbstractPhpunitTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing;

use Exception;
use PHPUnit_Framework_TestCase;
use stdClass;
use testing\mock\GearmanClientMock;

/**
 * Abstract PHPUnit Test class
 */
abstract class AbstractPhpunitTest extends PHPUnit_Framework_TestCase
{
	/*
	 * Yaml Config file name
	 */
	const YAML_CONFIG_FILE = 'config.yml';

	/*
	 * Mocked vars objects
	 */
	public static $mocked_objects = [];

	/*
	 * Mocked functions listener
	 */
	private static $mocked_function_listener = [];

	/*
	 * Yaml Config
	 */
	private static $config;

	/**
	 *  Called before the first test of the test case class run
	 *
	 *  @codeCoverageIgnore
	 *  @return void
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		if (!function_exists('runkit_function_redefine')) {
			trigger_error('Runkit is not installed.', E_USER_ERROR);
		}

		/* APC doesn't quite likerunkit.
		 * When they work together, it might result dead process */
		if (function_exists('apc_clear_cache')) {
			apc_clear_cache();
		}

		// Hack for PHPunit process isolation
		// @see adodb-mysqli.inc.php line 25
		if (defined('_ADODB_MYSQLI_LAYER')) {
			runkit_constant_remove('_ADODB_MYSQLI_LAYER');
			api_db_write_connect();
		}

		// Safely remove mocked functions on shutdown in case of PHP test exit unexpectedly
		register_shutdown_function([get_called_class(), 'remove_mocked_functions']);

		// Create a GearmanClient Mock class
		global $gearmanClient;
		$gearmanClient = new GearmanClientMock();
	}

	/**
	 * Removed all the defined mocked functions
	 *
	 * @param string $function_name
	 * @return void
	 */
	public static function remove_mocked_functions($function_name = null) {
		$all_functions = get_defined_functions();
		$all_functions = array_merge($all_functions['internal'], $all_functions['user']);
		$mocked_functions = preg_grep(sprintf('/^%s/', self::get_config('runkit.backup_prefix')), $all_functions);

		foreach ($mocked_functions as $backup_function_name) {
			$original_function_name = self::get_original_function_name($backup_function_name);

			// filter function_name
			if ($function_name && $original_function_name !== $function_name) {
				continue;
			}

			if (function_exists($original_function_name)) {
				runkit_function_remove($original_function_name);
			}
			if (function_exists($backup_function_name)) {
				runkit_function_copy($backup_function_name, $original_function_name);
				runkit_function_remove($backup_function_name);
			}
		}

		self::resetListenMockFunction($function_name);
	}

	/**
	 * Listen some mocked function params and return values
	 *
	 * @param string $function_name
	 * @param mixed  $args_passed
	 * @param mixed  $return_value
	 * @return void
	 */
	public static function listen_return_mock_function($function_name, $args_passed, $return_value) {
		self::$mocked_function_listener[$function_name][] = [
			'args' => $args_passed,
			'return' => $return_value
		];
	}

	/**
	 * Validation log message
	 *
	 * @param mixed $flag
	 * @return void
	 */
	protected static function log_validate($flag) {
		self::log(
			$flag ? ' OK ' : 'FAIL',
			[],
			$flag ? 'success' : 'error'
		);
	}

	/**
	 * Log message
	 *
	 * @param string  $message
	 * @param array   $values
	 * @param string  $type
	 * @param boolean $carriage_return
	 * @return void
	 */
	protected static function log($message, array $values = [], $type = null, $carriage_return = true) {
		$message = $values ? vsprintf($message, $values) : $message;
		$log_colors = self::get_config('log.colors');

		// color the message
		if ($type && in_array(PHP_OS, ['Linux']) && isset($log_colors[$type])) {
			$message = sprintf("\e[%sm%s\e[0m", $log_colors[$type], $message);
		}

		// carriage return
		if ($carriage_return) {
			$message = $message . "\n";
		}

		print $message;
	}

	/**
	 *  Get config variables
	 *
	 *  @param string  $key
	 *  @param boolean $reload
	 *  @return mixed
	 *  @throws Exception If key config not found.
	 */
	protected static function get_config($key = null, $reload = false) {
		if (!self::$config || $reload === true) {
			$path = APP_PHPUNIT_PATH . "/" . self::YAML_CONFIG_FILE;
			if (!file_exists($path)) {
				throw new Exception("Yaml config file not found in '" . self::YAML_CONFIG_FILE . "'");
			}

			$config = \sfYaml::load($path);
			if (!$config) {
				throw new Exception("Yaml config load error in '" . self::YAML_CONFIG_FILE . "'");
			}
			self::$config = $config;
		}

		$value = self::$config;
		if ($key) {
			$keys = array_filter(explode('.', $key));
			foreach ($keys as $i => $key_item) {
				if (!is_array($value) || !array_key_exists($key_item, $value)) {
					throw new Exception("Yaml config key '{$key_item}' not found");
				}
				$value = $value[$key_item];
			}
		}

		return $value;
	}

	/**
	 * Get phpunit option
	 *
	 * @codeCoverageIgnore
	 * @param string $option
	 * @return mixed
	 */
	protected static function get_phpunit_option($option) {
		global $argv;
		$options = preg_grep("/^--{$option}=?/", $argv);
		if (count($options) == 1) {
			$parts = explode('=', current($options));
			return isset($parts[1]) ? $parts[1] : true;
		}

		return false;
	}

	/**
	 * Get the backup function name from original
	 *
	 * @param string $original_function_name
	 * @return string
	 */
	private static function get_backup_function_name($original_function_name) {
		return self::get_config('runkit.backup_prefix') . $original_function_name;
	}

	/**
	 * Get the original function name from backup
	 *
	 * @param string $backup_function_name
	 * @return string
	 */
	private static function get_original_function_name($backup_function_name) {
		return str_replace(self::get_config('runkit.backup_prefix'), '', $backup_function_name);
	}

	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		// purge errors for each tests
		api_error_purge();
	}

	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();

		// Check if some listened mocked function have not been asserted
		foreach (self::$mocked_function_listener as $function_name => $asserted_flag) {
			/* if $asserted_flag types:
			 *  - false: Fiunction not called during the test so no assertions needed
			 *  - true: Fiunction been called and all assertions made succesfully
			 *  - array: Fiunction been called abut no assertions */
			$this->assertInternalType('boolean', $asserted_flag, "Listened mocked function '$function_name' have not been asserted completely.");
		}
		// clear all mocked functions listener
		self::$mocked_function_listener = [];

		self::remove_mocked_functions();
	}

	/**
	 * Creates runkit function NOT to used for mocking
	 *
	 * @param string $function_name
	 * @return void
	 */
	protected function mock_function_prevent($function_name) {
		$callback_exception = 'echo "\n\e[1;41mThe function ' . $function_name . '() should not be called directly.\e[0m\n"; debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);';
		$this->mock_function($function_name, $callback_exception);
	}

	/**
	 * Creates runkit function to be used for mocking and replace behavior by another function
	 *
	 * @param string $function_name
	 * @param string $new_function_name
	 * @return void
	 * @throws Exception If new function name does not exists.
	 */
	protected function mock_function_replace($function_name, $new_function_name) {
		if (!function_exists($new_function_name)) {
			throw new Exception("Function $new_function_name() not found.");
		}

		$callback_string = "\$args = func_get_args();\n";
		$callback_string .= "\$return = call_user_func_array('$new_function_name', \$args);";

		// listener
		if (array_key_exists($function_name, self::$mocked_function_listener)) {
			$classname = get_class($this);
			$callback_string .= "call_user_func_array(['{$classname}', 'listen_return_mock_function'], ['{$function_name}', \$args, \$return]);\n";
		}

		$callback_string .= "return \$return;\n";

		$this->mock_function($function_name, $callback_string);
	}

	/**
	 * Creates runkit function to be used for mocking and return a specific value
	 *
	 * @param string  $function_name
	 * @param mixed   $value
	 * @param boolean $raw
	 * @return void
	 */
	protected function mock_function_value($function_name, $value, $raw = false) {
		$callback_string = $value;
		if (!$raw) {
			$callback_string = "\$return = " . $this->var_export($value, $function_name) . ";\n";
		}

		// listener
		if (array_key_exists($function_name, self::$mocked_function_listener)) {
			$classname = get_class($this);
			$callback_string = <<<PHP
{$callback_string}
\$return = isset(\$return) ? \$return : null;
call_user_func_array(['{$classname}', 'listen_return_mock_function'], ['{$function_name}', func_get_args(), \$return]);
PHP;
		}

		if (!$raw) {
			$callback_string .= "\nreturn \$return;\n";
		}

		$this->mock_function($function_name, $callback_string);
	}

	/**
	 * Creates runkit function to be used for mocking and change parameters
	 *
	 * @param string $function_name
	 * @param mixed  $params_value
	 * @return void
	 * @throws Exception Check if parameters does not match.
	 */
	protected function mock_function_param($function_name, $params_value) {
		$ref_function = new \ReflectionFunction($function_name);
		$params = $ref_function->getParameters();
		$params_value = (array) $params_value;

		array_walk(
			$params_value,
			function($value, $key) use ($function_name, $params) {
				if (!isset($params[$key])) {
					$nb_params = count($params);
					throw new Exception("Can not set parameter with key #$key. Function $function_name() accepts only $nb_params parameters");
				}
			}
		);

		$backup_function_name = self::get_backup_function_name($function_name);
		$callback_string = "\$params = func_get_args();\n";
		$params_value = $this->var_export((array) $params_value, $function_name);
		$callback_string .= "\$params = {$params_value} + \$params;\n";
		$callback_string .= "ksort(\$params);\n";
		$callback_string .= "return call_user_func_array('$backup_function_name', \$params);";

		$this->mock_function($function_name, $callback_string);
	}

	/**
	 * Creates runkit function to be used for mocking, return value depending on parameters values
	 *
	 * @param string  $function_name
	 * @param array   $params_value_suite
	 * @param mixed   $default_value
	 * @param boolean $halt_on_default    Prevent execution and print passed parameters if default return would have been used.
	 * @return void
	 * @throws Exception Check if $params_value_suite array is well formatted.
	 */
	protected function mock_function_param_value($function_name, array $params_value_suite, $default_value = null, $halt_on_default = false) {
		$args = func_get_args();
		$callback_string = $start_callback_string = '';

		$check_param_callback_string = function($key) {
			$string = "if (!array_key_exists({$key}, \$params)) {\n";
			$string .= "	throw new \Exception('Parameter key #" . ($key + 1) . " does not exists.');\n";
			$string .= "}\n";
			return $string;
		};

		if ($params_value_suite) {
			foreach ($params_value_suite as $i => $params) {
				if (!is_array($params)) {
					throw new Exception("Param #$i is not an array. Params suite must be an array like ['params' => ..., 'return' => ...]");
				}
				if (!array_key_exists('return', $params)) {
					throw new Exception("Param #$i must have a 'return' key");
				}

				$callback_string .= ($i > 0 ? 'else ' : '') . 'if (';
				if (array_key_exists('params', $params)) {
					$params['params'] = (array) $params['params'];
					if (!is_array($params['params']) || !$params['params']) {
						throw new Exception("List of Params not an array or has empty values");
					}
					$cpt = 1;
					foreach ($params['params'] as $index => $value) {
						$is_regexp = $value instanceof stdClass && isset($value->is_mock_regexp_param) && $value->is_mock_regexp_param === true;
						$value_return = $params['return'];
						if ($value_return instanceof stdClass
							&& isset($value_return->is_mock_function_get_param)
							&& $value_return->is_mock_function_get_param === true) {
							$start_callback_string .= $check_param_callback_string($value_return->parameter_key);
							$value_return = "\$params[{$value_return->parameter_key}]";
						} else {
							if (!isset($params['raw']) || $params['raw'] !== true) {
								if ($is_regexp) {
									$start_callback_string .= "if (is_array(\$params[$index]) || is_object(\$params[$index])) throw new Exception('RegExp param issue. Parameter key #{$index} expect to be string');";
								} else {
									$value_return = $this->var_export($value_return, uniqid($function_name));
								}
							}
						}

						$callback_string .= "array_key_exists($index, \$params) && ";
						if ($is_regexp) {
							$callback_string .= "preg_match('{$value->regexp}', \$params[$index])";
						} else {
							$callback_string .= "\$params[$index] === " . $this->var_export($value, $function_name);
						}
						$callback_string .= count($params['params']) == $cpt ? ") {\n \$return = {$value_return};\n}\n" : " && ";
						$cpt++;
						unset($value_return);
					}
				}
			}
		}

		// default return value if $return not set
		$backup_function_name = self::get_backup_function_name($function_name);
		$callback_string .= "if (!array_key_exists('return', get_defined_vars())) {\n";
		$return = "call_user_func_array('$backup_function_name', \$params)";
		if (array_key_exists(2, $args) && $args[2] === $default_value) {
			if ($default_value instanceof stdClass
				&& isset($default_value->is_mock_function_get_param)
				&& $default_value->is_mock_function_get_param === true) {
				$callback_string .= $check_param_callback_string($default_value->parameter_key);
				$return = "\$params[{$default_value->parameter_key}]";
			} else {
				$return = $this->var_export($default_value, $function_name);
			}
		}
		$callback_string .= "  \$return = $return;\n";
		if ($halt_on_default) {
			$possible_params = $this->var_export(
				array_map(
					function($elem) {
						return $elem['params'];
					},
					$params_value_suite
				),
				$function_name
			);
			$callback_string .= "  echo \"\\n \e[1;37m==UNEXPECTED MOCKED FUNCTION PARAMS==\e[0m\\n\e[1;31mMocked \e[1;37m\e[1;41m$function_name\e[0m\e[1;31m attempted call with the following parameters:\e[1;37m\\n\"; var_dump(\$params); echo \"\\n\\n\e[1;31mWhich is not in the mocked parameters:\e[1;37m\\n\"; var_dump(" . $possible_params . "); echo \"\e[1;37m==/UNEXPECTED MOCKED FUNCTION PARAMS==\e[0m\\n\";";
		}
		$callback_string .= "}\n";

		// listener
		if (array_key_exists($function_name, self::$mocked_function_listener)) {
			$callback_string .= "call_user_func_array(['" . get_class($this) . "', 'listen_return_mock_function'], ['$function_name', \$params, \$return]);\n";
		}

		$callback_string .= "return \$return;\n";

		// generate callback string
		$callback_string = "\$params = func_get_args();\n{$start_callback_string}\n{$callback_string}";

		$this->mock_function($function_name, $callback_string);
	}

	/**
	 * Creates a executable code to get a parameter value
	 * NOTE: Parameter key starts at 1, not 0
	 *
	 * @param string $parameter_key
	 * @return stdClass
	 * @throws Exception If parameter key value is invbalid or an not integer greater than 0.
	 */
	protected function mock_function_get_param($parameter_key) {
		$parameter_key = (int) $parameter_key;
		if (!$parameter_key) {
			throw new Exception("Parameter key value must be an integer greater than 0.");
		}

		$object = $this->create_std_class(
			[
				'parameter_key' => $parameter_key - 1,
				'is_mock_function_get_param' => true
			]
		);

		return $object;
	}

	/**
	 * Creates a executable code to check if param match
	 *
	 * @param string $regexp
	 * @return stdClass
	 * @throws Exception If parameter key value is invbalid or an not integer greater than 0.
	 */
	protected function mock_function_regexp_param($regexp) {
		$object = $this->create_std_class(
			[
				'regexp' => $regexp,
				'is_mock_regexp_param' => true
			]
		);

		return $object;
	}

	/**
	 * Creates a std class
	 *
	 * @param array $properties
	 * @return stdClass
	 */
	protected function create_std_class(array $properties = []) {
		$object = new stdClass();
		foreach ($properties as $key => $value) {
			$object->{$key} = $value;
		}

		return $object;
	}

	/**
	 * Listen a mocked function
	 *
	 * @param string $function_name
	 * @return void
	 */
	protected function listen_mocked_function($function_name) {
		self::$mocked_function_listener[$function_name] = false;
	}

	/**
	 * Assert value is same and equal
	 *
	 * @param mixed $expected_value
	 * @param mixed $assert_value
	 * @param mixed $message
	 * @return void
	 */
	protected function assertSameEquals($expected_value, $assert_value, $message = null) {
		$this->assertEquals($expected_value, $assert_value, $message);
		$this->assertSame($expected_value, $assert_value, $message);
	}

	/**
	 * Assert mocked function has been called
	 *
	 * @param string  $function_name
	 * @param boolean $called
	 * @param integer $count
	 * @return void
	 */
	protected function assertListenMockFunctionHasBeenCalled($function_name, $called = true, $count = null) {
		$listeners = self::$mocked_function_listener;
		$this->assertInternalType('array', $listeners);
		$this->assertArrayHasKey($function_name, $listeners, "No listener mock function '{$function_name}' is set up.");

		$this->assertEquals(
			$called,
			is_array($listeners[$function_name]),
			"Assert mocked function '{$function_name}' has been called"
		);

		if (!is_null($count) && is_array($listeners[$function_name])) {
			$actual_count = count($listeners[$function_name]);
			$this->assertEquals(
				$count,
				count($listeners[$function_name]),
				"Assert mocked function '{$function_name}' has been called " . $actual_count . " times"
			);
		}
		// function has been asserted correctly so we put it back to true
		self::$mocked_function_listener[$function_name] = true;
	}

	/**
	 * Assert mocked function listener
	 *
	 * @param string $function_name
	 * @param array  $called_param_values
	 * @return void
	 */
	protected function assertListenMockFunction($function_name, array $called_param_values) {
		$functions = $this->fetchListenedMockFunctionParamValues($function_name);
		$this->assertInternalType('array', $functions);
		// called
		$called = count($functions);
		$this->assertCount(count($functions), $called_param_values, "Listened mock function '{$function_name}' has been called $called time(s), expected " . count($called_param_values));

		foreach ($called_param_values as $i => $called_param_value) {
			$this->assertInternalType('array', $called_param_value, "Expected params for listened mock function must be an array of arrays.");
			$this->assertArrayHasKey($i, $functions, "No expected params found for listened mock function '{$function_name}' call #" . ($i + 1));
			$function = $functions[$i];
			$this->assertArrayHasKey('args', $called_param_value);
			$this->assertArrayHasKey('return', $called_param_value);
			$this->assertSameEquals($called_param_value, $function);
		}
	}

	/**
	 * @param string $function_name
	 * @return mixed
	 */
	protected function fetchListenedMockFunctionParamValues($function_name) {
		$listeners = self::$mocked_function_listener;
		$this->assertInternalType('array', $listeners);
		$this->assertArrayHasKey($function_name, $listeners, "No listener mock function '{$function_name}' is set up.");
		// function has been asserted correctly so we put it back to true
		self::$mocked_function_listener[$function_name] = true;
		return $listeners[$function_name];
	}

	/**
	 * Reset mocked function listener
	 *
	 * @param string $function_name
	 * @return void
	 */
	protected static function resetListenMockFunction($function_name) {
		unset(self::$mocked_function_listener[$function_name]);
	}

	/**
	 * Include fake gearman class
	 *
	 * @return void
	 */
	protected function includeFakeGearman() {
		if (!class_exists('GearmanClient')) {
			require_once(APP_PHPUNIT_PATH . '/mock/GearmanClient.php');
		}
	}

	/**
	 * Var export
	 *
	 * @param string $var
	 * @param string $function_name
	 * @return string
	 */
	private function var_export($var, $function_name) {
		if (is_object($var)) {
			$class = get_class($this);
			$id = uniqid('', true);
			self::$mocked_objects[$function_name . $id] = $var;
			$export = "{$class}::\$mocked_objects['" . $function_name . $id . "']";
		} else {
			$export = var_export($var, true);
		}

		return $export;
	}

	/**
	 * Creates runkit function to be used for mocking, taking care of callback to this object.
	 *
	 * @param string $function_name
	 * @param string $callback_string
	 * @return void
	 * @throws Exception If RunKit Mock backup function aleady exists.
	 */
	private function mock_function($function_name, $callback_string) {
		if (function_exists($function_name)) {
			$backup_function_name = self::get_backup_function_name($function_name);
			if (function_exists($backup_function_name)) {
				/* NOTE: we make sure we remove all the mocked functions
				   even though we know its in the shutdown ... */
				self::remove_mocked_functions();
				throw new Exception("RunKit Mock backup function {$backup_function_name}() already exists");
			}
			runkit_function_copy($function_name, $backup_function_name);
			runkit_function_redefine($function_name, '', $callback_string);
		} else {
			runkit_function_add($function_name, '', $callback_string);
		}
	}

	/**
	 * Diff 2 arrays by var types
	 *
	 * @codeCoverageIgnore
	 * @param mixed $array1
	 * @param mixed $array2
	 * @return array
	 */
	private function array_diff_recursive($array1, $array2) {
		$diffs = array();

		if (is_array($array1)) {
			foreach ($array1 as $key => $value) {
				if (array_key_exists($key, $array2)) {
					if (is_array($value)) {
						$array_diff = $this->array_diff_recursive($value, $array2[$key]);
						if (count($array_diff)) {
							$diffs[$key] = $array_diff;
						}
					} else {
						if ($value != $array2[$key] || gettype($value) != gettype($array2[$key])) {
							$diffs[$key] = $value . ' (' . gettype($value) . ')';
						}
					}
				} else {
					$diffs[$key] = $value;
				}
			}
		}
		return $diffs;
	}
}
