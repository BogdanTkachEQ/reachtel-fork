<?php
/**
 * AbstractDatabasePhpunitModuleTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use Exception;
use PHPUnit_Runner_BaseTestRunner;
use Services\ActivityLogger;
use testing\AbstractPhpunitTest;

/**
 * Abstract Database PHPUnit Module Test class
 */
abstract class AbstractDatabasePhpunitModuleTest extends AbstractPhpunitTest
{
	/*
	 * Flag if database has been checked
	 */
	private static $is_database_checked = false;

	/*
	 * Test statuses
	 */
	private static $current_test_statuses;

	/**
	 *  Called before the first test of the test case class run
	 *
	 *  @codeCoverageIgnore
	 *  @return void
	 *  @throws Exception If an error occured when reloading the database.
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		if (!self::isIsolationProcess() && !self::$is_database_checked) {
			self::log("\nChecking database %s.", [self::get_config('database.name')]);
			// reload_database start event
			$reload = self::get_config('database.reload_database_events');
			if ((is_array($reload) && in_array('start', $reload)) || !self::check_database()) {
				if (!self::reload_database('start')) {
					throw new Exception("There was an error when reloading the database.");
				}
			}
			self::$is_database_checked = true;
		}
	}

	/**
	 * {@inheritdoc}
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		self::$current_test_statuses[] = $this->getStatus();
		ActivityLogger::getInstance()->resetLogs();
	}

	/**
	 * {@inheritdoc}
	 * @return void
	 * @throws Exception If an error occured when reloading the database.
	 */
	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();

		$events = self::get_config('database.reload_database_events');
		if (is_array($events)) {
			$statuses = array_unique((array) self::$current_test_statuses);
			foreach ($events as $event) {
				$constant = "PHPUnit_Runner_BaseTestRunner::STATUS_" . strtoupper($event);
				// check constant PHPUnit_Runner_BaseTestRunner::STATUS_XXXXX exists
				if (defined($constant) && in_array(constant($constant), $statuses, true)) {
					if (!self::reload_database($event)) {
						throw new Exception("There was an error when reloading the database.");
					}
				}
			}
		}
	}

	/**
	 *  Logic to determine is the test run with @runTestsInSeparateProcesses.
	 *  NOTE: This logic can change depending on PHPUnit version.
	 *
	 *  @return boolean
	 */
	private static function isIsolationProcess() {
		return !isset($GLOBALS['__PHPUNIT_BOOTSTRAP']);
	}

	/**
	 *  Check if database is existsm is not empty and valid hash
	 *
	 *  @codeCoverageIgnore
	 *  @return boolean
	 *  @throws Exception If an error occured when connecting to the database.
	 */
	private static function check_database() {
		$database_name = self::get_config('database.name');
		$sql = 'SELECT DATABASE() as `database`;';

		$rs = api_db_query_read($sql);

		if (!$rs) {
			api_error_printiferror();
			global $DB_WRITE;
			throw new Exception($DB_WRITE->ErrorMsg());
		}

		// check database and user is right as expected
		if ($rs->Fields("database") != $database_name
			|| $rs->connection->user != $database_name ) {
			api_error_printiferror();
			throw new Exception('Wrong connection: database ' . $rs->Fields("database") . ', user: ' . $rs->connection->user);
		}

		return true;
	}

	/**
	 *  Reload the testing database
	 *
	 *  @codeCoverageIgnore
	 *  @param string  $event
	 *  @param boolean $check
	 *  @return boolean
	 *  @throws Exception MySQL errors.
	 */
	private static function reload_database($event, $check = true) {
		self::log(
			"\n[%s] Reloading database %s.",
			[strtoupper($event), self::get_config('database.name')]
		);

		self::drop_all_tables();

		// reload database
		$sql_dump_file = self::get_config('database.dump_path');
		if (!is_readable($sql_dump_file)) {
			throw new Exception("SQL dump file does not exists or is not readable in '$sql_dump_file'.");
		}
		self::log(' > loading %s.', [$sql_dump_file]);

		$sql_dump_file = APP_ROOT_PATH . '/' . $sql_dump_file;
		$sql = file_get_contents($sql_dump_file);
		// sperate by statements
		$sql_parts = array_filter(explode(";\n", $sql), 'trim');
		foreach ($sql_parts as $sql_part) {
			if (! ($rs = api_db_query_write($sql_part . ' ;', false))) {
				global $DB_WRITE;
				if (!($error_message = $DB_WRITE->ErrorMsg())) {
					if (strlen($sql_part) > 200) {
						$sql_part = substr($sql_part, 0, 90) . '   ...   '  . substr($sql_part, -90);
					}
					$error_message = "There is an error in the SQL syntax when loading $sql_dump_file in: '$sql_part'";
				}
				throw new Exception($error_message);
			}
		}

		self::log(' > run migration scripts.');
		$helperSet = new \Symfony\Component\Console\Helper\HelperSet();
		$helperSet->set(new \Symfony\Component\Console\Helper\QuestionHelper(), 'question');
		$migration = \Doctrine\DBAL\Migrations\Tools\Console\ConsoleRunner::createApplication($helperSet);
		$output = new \Symfony\Component\Console\Output\BufferedOutput();
		$migration->setAutoExit(false);
		$code = $migration->run(
			new \Symfony\Component\Console\Input\ArgvInput(['phpunit', 'migrations:migrate', '-n']),
			$output
		);
		if ($code > 0) {
			throw new Exception("Migration error:" . $output->fetch());
		}

		return ($check ? self::check_database() : true);
	}

	/**
	 *  Get all the tables in a flatten array
	 *
	 *  @codeCoverageIgnore
	 *  @return array
	 *  @throws Exception MySQL errors.
	 */
	private static function get_all_tables() {
		$sql = sprintf('SHOW TABLES;');
		$rs = api_db_query_read($sql);

		if (!$rs) {
			global $DB_WRITE;
			api_error_printiferror();
			throw new Exception($DB_WRITE->ErrorMsg());
		}

		return array_map('current', (array) $rs->GetAll());
	}

	/**
	 *  Drop all the tables. Returned an array of deleted table names
	 *
	 *  @codeCoverageIgnore
	 *  @return array
	 *  @throws Exception MySQL errors.
	 */
	private static function drop_all_tables() {
		self::log(' > drop all tables.', [self::get_config('database.name')]);
		$all_tables = self::get_all_tables();

		// drop all tables if needed
		if ($all_tables) {
			api_db_query_write('SET FOREIGN_KEY_CHECKS=0;');
			$sql = sprintf('DROP TABLE IF EXISTS %s;', implode(', ', $all_tables));
			if (! ($rs = api_db_query_write($sql))) {
				global $DB_WRITE;
				api_error_printiferror();
				throw new Exception($DB_WRITE->ErrorMsg());
			}
			api_db_query_write('SET FOREIGN_KEY_CHECKS=1;');
		}

		return $all_tables;
	}

	/**
	 * @param string $table
	 * @return integer
	 * @throws Exception If table does not exists.
	 */
	protected function get_table_number_rows($table) {
		if (!in_array($table, $this->get_all_tables())) {
			throw new Exception("Table '{$table}' does not exists");
		}

		$sql = "SELECT COUNT(*) AS nb FROM `{$table}`;";
		$rs = api_db_query_read($sql);
		$this->assertInstanceOf('ADORecordSet_mysqli', $rs);

		return (int) $rs->Fields('nb');
	}

	/**
	 *  Get the expected next id value from key_store table
	 *
	 *  @param string $type
	 *  @return integer
	 *  @throws Exception If a database error occured.
	 */
	protected function get_expected_next_id($type) {
		if (!$type) {
			throw new Exception("Invalid type");
		}

		$sql = "SELECT `value` FROM `key_store`
				WHERE `type` = ? AND `id` = ? AND `item` = ?;";
		$rs = api_db_query_read($sql, array($type, 0, 'nextid'));
		if (!$rs) {
			global $DB_WRITE;
			$error = $DB_WRITE->ErrorMsg();
			throw new Exception($error ? : 'You have an error in your SQL syntax');
		}

		if (($count = $rs->RecordCount()) != 1) {
			throw new Exception("$count values found for $type next id, expected 1");
		}

		return (int) $rs->Fields('value') + 1;
	}

	/**
	 *  Get table rows
	 *
	 *  @param string $table
	 *  @param string $fields_filter
	 *  @return array
	 *  @throws Exception If a database error occured.
	 */
	protected function get_table_rows($table, $fields_filter = []) {
		$params = [];
		$sql = "SELECT * FROM `$table` WHERE 1 ";

		foreach ($fields_filter as $field => $value) {
			$params[] = $value;
			$sql .= "AND `$field` = ? ";
		}

		$rs = api_db_query_read($sql, $params);
		if (!$rs) {
			global $DB_WRITE;
			throw new Exception($DB_WRITE->ErrorMsg());
		}

		return $rs->GetAll();
	}
}
