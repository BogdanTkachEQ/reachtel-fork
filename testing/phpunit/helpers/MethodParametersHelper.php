<?php
/**
 * MethodParametersHelper
 * Helper Trait  for method parameters
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\helpers;

use Exception;
use ReflectionFunction;
use ReflectionMethod;
use stdClass;

/**
 * Helper Trait  for method parameters
 */
trait MethodParametersHelper
{
	/**
	 * Get the default parameters values of the test methods
	 *
	 * @param array $values
	 * @return array
	 * @throws Exception If default parameter values should be set.
	 */
	protected function get_test_data_from_parameters_default_values(array $values = []) {
		$params = [];

		// get method name
		$regexp = '/^(.*)_data$/';
		$method_name = debug_backtrace()[1]['function'];
		if (!preg_match($regexp, $method_name)) {
			$method = __FUNCTION__;
			throw new Exception("Method {$method}() must be call only in a data provider");
		}
		$method_name = preg_replace($regexp, 'test_$1', $method_name);
		$method = new ReflectionMethod($this, $method_name);

		$test_data = $this->do_get_parameters_default_values($method->getParameters(), $values);

		$default_values = [];
		foreach ($test_data as $data) {
			$default_values[] = current($data->parameters);
		}

		return $default_values;
	}

	/**
	 * Get combinations of parameters of a function
	 *
	 * @param string $callable
	 * @param array  $parameters
	 * @return array
	 * @throws Exception If default parameter values should be set.
	 */
	protected function get_test_data_from_parameters_combinations($callable, array $parameters = []) {
		if (!$callable || !is_callable($callable)) {
			throw new Exception("Parameter is not callable");
		}

		if ((is_string($callable) && function_exists($callable)) || (is_object($callable) && ($callable instanceof \Closure))) {
			$ref = new ReflectionFunction($callable);
			$test_data = $this->do_get_parameters_default_values($ref->getParameters(), $parameters);
		} elseif (is_array($callable) && count($callable) == 2 && method_exists($callable[0], $callable[1])) {
			$ref = new ReflectionMethod($callable[0], $callable[1]);
			$test_data = $this->do_get_parameters_default_values($ref->getParameters(), $parameters);
		}

		$combinations = [];
		array_walk(
			$test_data,
			function($value, $param_name) use (&$combinations) {
				$_value = [$value];
				if ($this->is_parameter_possibilities($value)) {
					$_value = $value->parameters;
				}
				$combinations[$param_name] = $_value;
			}
		);

		return $this->generate_combinations($combinations);
	}

	/**
	 * set parameter possibilities for one parameter
	 *
	 * @param array $parameters
	 * @return stdClass
	 */
	protected function add_parameter_possibilities(array $parameters) {
		$object = new stdClass();
		$object->is_parameter_possibilities = true;
		$object->parameters = [];

		foreach ($parameters as $value) {
			if ($this->is_parameter_possibilities($value)) {
				$object->parameters = $object->parameters + $value->parameters;
			} else {
				$object->parameters[] = $value;
			}
		}

		return $object;
	}

	/**
	 * @param mixed $object
	 * @return boolean
	 */
	private function is_parameter_possibilities($object) {
		return ($object instanceof stdClass && $object->is_parameter_possibilities === true);
	}

	/**
	 * Generate all the possible combinations among a set of nested arrays.
	 *
	 * @param array   $data  The entrypoint array container.
	 * @param array   $all   The final container (used internally).
	 * @param array   $group The sub container (used internally).
	 * @param mixed   $value The value to append (used internally).
	 * @param integer $i     The key index (used internally).
	 * @return array
	 * @see https://gist.github.com/fabiocicerchia/4556892
	 */
	private function generate_combinations(array $data, array &$all = [], array $group = [], $value = null, $i = 0) {
		$keys = array_keys($data);
		if ($i > 0) {
			array_push($group, $value);
		}

		if ($i >= count($data)) {
			array_push($all, $group);
		} else {
			$currentKey = $keys[$i];
			$currentElement = $data[$currentKey];
			foreach ($currentElement as $val) {
				$this->generate_combinations($data, $all, $group, $val, $i + 1);
			}
		}

		return $all;
	}

	/**
	 * Get the default parameters values
	 *
	 * @param array   $parameters
	 * @param array   $values
	 * @param boolean $prepend_default_value
	 * @return array
	 * @throws Exception If default parameter values should be set.
	 */
	private function do_get_parameters_default_values(array $parameters, array $values, $prepend_default_value = false) {
		$params = [];

		foreach ($parameters as $i => $param) {
			$this->assertInstanceOf('ReflectionParameter', $param);
			$name = $param->getName();
			$has_default = $param->isDefaultValueAvailable();

			if (array_key_exists($name, $values)) {
				$param_value = $values[$name];
			} else {
				if (!$has_default) {
					$param = $i + 1;
					throw new Exception("Default parameter value for '{$name}' is not available and should be set");
				}
				$param_value = $param->getDefaultValue();
			}
			$param_values = [$param_value];

			if ($prepend_default_value && $has_default && $param->getDefaultValue() != $param_value) {
				$param_values[] = $param->getDefaultValue();
			}

			$param_value = $this->add_parameter_possibilities($param_values);
			$params[$name] = $param_value;
		}

		return array_values($params);
	}
}
