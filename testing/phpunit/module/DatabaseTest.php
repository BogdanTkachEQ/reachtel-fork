<?php
/**
 * DatabaseTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

/**
 * Database Test
 */
class DatabaseTest extends AbstractPhpunitModuleTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function test_constants_data() {
		return [
			['DB_SINGLE_CONNECTION', 1],
			['DB_CONNECTION_NO_SSL', 1],
			['DB_MYSQL_DATABASE', 'morpheus_phpunit'],
			['DB_MYSQL_WRITE_USERNAME', 'morpheus_phpunit'],
			['DB_MYSQL_WRITE_PASSWORD', 'morpheus_phpunit']
		];
	}

	/**
	 * @dataProvider test_constants_data
	 * @param string $constant_name
	 * @param mixed  $expected_value
	 * @return void
	 */
	public function test_constants($constant_name, $expected_value = null) {
		$this->assertTrue(
			defined($constant_name),
			"Failed asserting that constant $constant_name is defined."
		);

		if ($expected_value !== null) {
			$value = constant($constant_name);
			$this->assertSameEquals(
				$expected_value,
				$value,
				"Failed asserting that constant $constant_name = $expected_value (found $value)"
			);
		}
	}

	/**
	 * @return void
	 */
	public function test_connection() {
		global $DB_WRITE;
		$this->assertTrue(api_db_read_connect());
		$this->assertNull(api_db_write_connect());

		$this->assertTrue(isset($DB_WRITE), 'Failed asserting that ADODB is set.');
		$this->assertInstanceOf('ADODB_mysqli', $DB_WRITE);
		$this->assertEmpty($DB_WRITE->ErrorMsg(), 'Database connection error: ' . $DB_WRITE->ErrorMsg());
		$this->assertTrue($DB_WRITE->IsConnected(), 'Failed asserting that database is connected properly.');
	}

	/**
	 * @return void
	 */
	public function test_sql_dump_file_exists() {
		$sql_dump_file = APP_ROOT_PATH . '/' . self::get_config('database.dump_path');

		$this->assertTrue(
			is_readable($sql_dump_file),
			"Failed asserting that SQL dump file exists and is readable in '$sql_dump_file'."
		);
	}
}
