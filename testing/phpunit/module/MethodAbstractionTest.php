<?php
/**
 * MethodAbstractionTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use Exception;

/**
 * Method Abstraction Module Test
 */
class MethodAbstractionTest extends AbstractPhpunitModuleTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function get_config_data() {
		return [
			// Failure key not found
			[null, 'log.whatever', false, Exception::class, "Yaml config key 'whatever' not found"],

			// Failure config file does not exists
			[null, 'log', true, Exception::class, "Yaml config file not found in 'config.yml'", false],

			// Failure key not found and reload
			[null, 'whatever', true, Exception::class, "Yaml config key 'whatever' not found"],

			// Success
			['1;37', 'log.colors.bold'],
			[['backup_prefix' => '__mock_func_backup_'], 'runkit'],
		];
	}

	/**
	 * @dataProvider get_config_data
	 * @param mixed   $expected_value
	 * @param mixed   $key
	 * @param boolean $reload
	 * @param mixed   $expected_exception
	 * @param mixed   $expected_message
	 * @param mixed   $file_exists
	 * @return void
	 */
	public function test_get_config($expected_value, $key, $reload = false, $expected_exception = null, $expected_message = null, $file_exists = null) {
		if ($file_exists !== null) {
			$this->mock_function_param('file_exists', $file_exists);
		}
		$this->setExpectedException($expected_exception, $expected_message);
		$this->assertSameEquals($expected_value, self::get_config($key, $reload));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function type_plural_data() {
		return [
			['USER', 'USERS', false],
			['USER', 'USER', false],
			['USERS', 'USERS', true],
			['USERS', 'USER', true],
			['ASSET', 'ASSETS', false],
			['ASSET', 'ASSET', false],
			['ASSETS', 'ASSETS', true],
			['ASSETS', 'ASSET', true],
		];
	}

	/**
	 * @dataProvider type_plural_data
	 * @param string  $expected_value
	 * @param string  $type
	 * @param boolean $plural
	 * @return void
	 */
	public function test_type_plural($expected_value, $type, $plural) {
		$this->assertSameEquals($expected_value, $this->type_plural($type, $plural));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function get_expected_next_id_data() {
		return [
			// Failures Invalid type
			[false, Exception::class, 'Invalid type'],
			[null, Exception::class, 'Invalid type'],
			[0, Exception::class, 'Invalid type'],
			['', Exception::class, 'Invalid type'],

			// Failure wrong type
			['WHATEVER', Exception::class, '0 values found for WHATEVER next id, expected 1'],

			// Failure SQL
			['WHATEVER', Exception::class, 'You have an error in your SQL syntax', true],

			// Success
			['USERS'],
			['ASSET'],
		];
	}

	/**
	 * @dataProvider get_expected_next_id_data
	 * @param mixed   $type
	 * @param mixed   $expected_exception
	 * @param mixed   $expected_message
	 * @param boolean $mock_api_db_query_read
	 * @return void
	 */
	public function test_get_expected_next_id($type, $expected_exception = null, $expected_message = null, $mock_api_db_query_read = false) {
		if ($mock_api_db_query_read) {
			$this->mock_function_param('api_db_query_read', 'WRONG SQL SYNTAX');
		}
		$this->setExpectedException($expected_exception, $expected_message);
		$expected_next_id = $this->get_expected_next_id($type);
		$this->assertInternalType('integer', $expected_next_id);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function get_table_rows_data() {
		return [
			// Failures table
			['whatever', [], Exception::class, "Table 'morpheus_phpunit.whatever' doesn't exist"],
			['whatever', ['somefields'], Exception::class, "Table 'morpheus_phpunit.whatever' doesn't exist"],

			// Failures field
			['targets', ['whatever' => 'whatever value'], Exception::class, "Unknown column 'whatever' in 'where clause'"],

			// Success table
			['targets'],
			['targets', ['status' => 'READY']],
		];
	}

	/**
	 * @dataProvider get_table_rows_data
	 * @param string $table
	 * @param array  $fields_filter
	 * @param mixed  $expected_exception
	 * @param mixed  $expected_message
	 * @return void
	 */
	public function test_get_table_rows($table, array $fields_filter = [], $expected_exception = null, $expected_message = null) {
		$this->setExpectedException($expected_exception, $expected_message);
		$values = $this->get_table_rows($table, $fields_filter);
		$this->assertInternalType('array', $values);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function log_validate_data() {
		return [
			["\e[1;32m OK \e[0m\n", true],
			["\e[1;31mFAIL\e[0m\n", false],
		];
	}

	/**
	 * @dataProvider log_validate_data
	 * @param string  $expected_output
	 * @param boolean $flag
	 * @return void
	 */
	public function test_log_validate($expected_output, $flag) {
		// catch output
		ob_start();
		$this->log_validate($flag);
		$output = (string) ob_get_contents();
		ob_end_clean();
		$this->assertSameEquals($expected_output, $output);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function log_data() {
		return [
			["text1\n", 'text1'],
			["text2\n", 'text%s', [2]],
			["\e[1;37mtext3\e[0m\n", 'text%s', [3], 'bold'],
			["\e[1;41mtext3\e[0m", 'text%s', [3], 'error_bar', false],
		];
	}

	/**
	 * @dataProvider log_data
	 * @param string  $expected_output
	 * @param string  $message
	 * @param array   $values
	 * @param string  $type
	 * @param boolean $carriage_return
	 * @return void
	 */
	public function test_log($expected_output, $message, array $values = [], $type = null, $carriage_return = true) {
		// catch output
		ob_start();
		$this->log($message, $values, $type, $carriage_return);
		$output = (string) ob_get_contents();
		ob_end_clean();
		$this->assertSameEquals($expected_output, $output);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function get_table_number_rows_data() {
		return [
			// Failure table does not exists
			['table_name_does_not_exists', Exception::class, "Table 'table_name_does_not_exists' does not exists"],

			// Failure table case sensitvie
			['Key_store', Exception::class, "Table 'Key_store' does not exists"],
			['key store', Exception::class, "Table 'key store' does not exists"],

			// Success
			['key_store'],
		];
	}

	/**
	 * @dataProvider get_table_number_rows_data
	 * @param string $table
	 * @param mixed  $expected_exception
	 * @param mixed  $expected_message
	 * @return void
	 */
	public function test_get_table_number_rows($table, $expected_exception = null, $expected_message = null) {
		$this->setExpectedException($expected_exception, $expected_message);
		$nb_rows = $this->get_table_number_rows($table);
		$this->assertInternalType('integer', $nb_rows);
		$this->assertGreaterThanOrEqual(0, $nb_rows);
	}
}
