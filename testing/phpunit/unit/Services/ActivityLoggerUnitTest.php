<?php
/**
 * ActivityLoggerUnitTest
 * Unit test for api_campaigns.php
 *
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

// @codingStandardsIgnoreStart
namespace {
// @codingStandardsIgnoreEnd
	$instance = null;
	$functionName = null;
	$sapiName = null;
}
// @codingStandardsIgnoreStart
namespace Services {
// @codingStandardsIgnoreEnd
	/**
	 * @param mixed $data
	 * @return void
	 */
	function register_shutdown_function($data) {
		global $instance;
		global $functionName;

		$instance = $data[0];
		$functionName = $data[1];
	}

	/**
	 * @return string
	 */
	function php_sapi_name() {
		global $sapiName;

		return $sapiName;
	}
// @codingStandardsIgnoreStart
}

namespace testing\unit\Services {
	use Services\ConfigReader;
	use testing\AbstractPhpunitTest;
	use Services\ActivityLogger;
// @codingStandardsIgnoreEnd
	/**
	 * Class ActivityLoggerUnitTest
	 */
	class ActivityLoggerUnitTest extends AbstractPhpunitTest
	{
		/**
		 * @return void
		 */
		public function setUp() {
			parent::setUp();

			$mockConfigReader = $this
				->getMockBuilder(ConfigReader::class)
				->disableOriginalConstructor()
				->getMock();

			$mockConfigReader
				->method('getConfig')
				->with(ConfigReader::ACTIVITY_LOGGER_CONFIG_TYPE)
				->willReturn(
					[
						'blacklisted_items' => [
							'blacklisted_type' => [
								'blacklisted_item1',
								'blacklisted_item2',
							]
						]
					]
				);

			ActivityLogger::getInstance($mockConfigReader, true);
		}

		/**
		 * @return void
		 */
		public function test_registers_shutdown_function_on_construct() {
			$this->markTestIncomplete('This test is broken but works when activity logger\'s instance is not created from api.php');
			global $instance;
			global $functionName;

			$this->assertInstanceof(ActivityLogger::class, $instance);
			$this->assertSameEquals('flush', $functionName);
		}

		/**
		 * @return void
		 */
		public function test_logs_are_reset_on_construct() {
			$logger = ActivityLogger::getInstance();
			$this->assertEmpty($logger->getLogs());
		}

		/**
		 * @return array
		 */
		public function log_data_provider() {
			return [
				'first log data' => [
					[['type1', 'action1', 'value1', 1, 10, 'item1']],
					[['type1', 'action1', 'value1', 1, 10, null, 'item1']]
				],
				'retains previous logs' => [
					[
						['type1', 'action1', 'value1', 1, 10, 'item1'],
						['type2', 'action2', 'value2', 2, 20, 'item2'],
					],
					[
						['type1', 'action1', 'value1', 1, 10, null, 'item1'],
						['type2', 'action2', 'value2', 2, 20, null, 'item2']
					]
				]
			];
		}

		/**
		 * @param array $logs
		 * @param array $expected
		 * @return void
		 * @dataProvider log_data_provider
		 */
		public function test_add_log(array $logs, array $expected) {
			$logger = ActivityLogger::getInstance();

			foreach ($logs as $log) {
				$return = $logger
					->addLog($log[0], $log[1], $log[2], $log[3], $log[4], $log[5]);

				$this->assertInstanceOf(ActivityLogger::class, $return);
			}

			$this->assertSameEquals($expected, $logger->getLogs());
		}

		/**
		 * @return void
		 */
		public function test_reset_logs() {
			$logger = ActivityLogger::getInstance();
			$logger->addLog('type1', 'action1', 'value1', 1, 10, 'item1');
			$this->assertNotEmpty($logger->getLogs());
			$return = $logger->resetLogs();
			$this->assertInstanceOf(ActivityLogger::class, $return);
			$this->assertEmpty($logger->getLogs());
		}

		/**
		 * @return void
		 */
		public function test_add_log_without_user_id_checks_session() {
			$_SESSION['userid'] = 100;
			$GLOBALS['userid'] = 120;

			$logger = ActivityLogger::getInstance();
			$logger->resetLogs();
			$logger->addLog('type1', 'action1', 'value1', 1);
			$this->assertSameEquals($_SESSION['userid'], $logger->getLogs()[0][4]);
		}

		/**
		 * @return void
		 */
		public function test_add_log_without_user_id_checks_global_if_session_not_set() {
			$logger = ActivityLogger::getInstance();
			$logger->resetLogs();

			unset($_SESSION['userid']);
			$GLOBALS['userid'] = 120;

			$logger->addLog('type1', 'action1', 'value1', 1);
			$this->assertSameEquals($GLOBALS['userid'], $logger->getLogs()[0][4]);
			unset($GLOBALS['userid']);
		}

		/**
		 * @return void
		 */
		public function test_add_log_gets_script_name_if_cli() {
			global $sapiName;

			$sapiName = 'cli';

			$logger = ActivityLogger::getInstance();
			$logger->resetLogs();
			$logger->addLog('type1', 'action1', 'value1', 1);
			$this->assertSameEquals($_SERVER['SCRIPT_NAME'], $logger->getLogs()[0][5]);
			$sapiName = null;
		}

		/**
		 * @return void
		 */
		public function test_add_log_gets_http_host() {
			$_SERVER['HTTP_HOST'] = 'rest.reachtel.com';

			$logger = ActivityLogger::getInstance();
			$logger->resetLogs();
			$logger->addLog('type1', 'action1', 'value1', 1);
			$this->assertSameEquals($_SERVER['HTTP_HOST'], $logger->getLogs()[0][5]);
			unset($_SERVER['HTTP_HOST']);
		}

		/**
		 * @return void
		 */
		public function test_add_log_will_not_add_log_if_logging_is_disabled() {
			$logger = ActivityLogger::getInstance();
			$logger->resetLogs();
			$logger->toggleLoggerActivation(false);
			$this->assertEmpty($logger->getLogs());
			$logger->addLog('type1', 'action1', 'value1', 1);
			$this->assertEmpty($logger->getLogs());
			$logger->toggleLoggerActivation(true);
		}

		/**
		 * @return void
		 */
		public function test_add_log_will_not_add_log_if_it_is_not_a_valid_log_item() {
			$logger = ActivityLogger::getInstance();
			$logger->resetLogs();
			$this->assertEmpty($logger->getLogs());
			$logger
				->addLog('type1', 'action1', 'value1', 1, 2, 'blacklisted_item2')
				->addLog('blacklisted_type', 'action1', 'value1', 1, 2, 'blacklisted_item1')
				->addLog('blacklisted_type', 'action1', 'value1', 1, 2, 'item1');

			$this->assertSameEquals(
				[
					['type1', 'action1', 'value1', 1, 2, null, 'blacklisted_item2'],
					['blacklisted_type', 'action1', 'value1', 1, 2, null, 'item1']
				],
				$logger->getLogs()
			);
		}

		/**
		 * @return void
		 */
		public function test_add_log_will_flush_logs_when_threshold_is_reached() {
			define('LOG_FLUSH_THRESHOLD', 2);
			$params = [
				['type1', 'action1', 'value1', 1, 2, 'item1'],
				['type2', 'action2', 'value2', 2, 2, 'item2']
			];

			$this->assert_flush_log_with_threshold($params);
			runkit_constant_remove('LOG_FLUSH_THRESHOLD');
		}

		/**
		 * @return void
		 */
		public function test_log_flush_threshold_default_is_used_when_not_defined() {
			$params = [
				['type1', 'action1', 'value1', 1, 2, 'item1'],
				['type2', 'action2', 'value2', 2, 2, 'item2'],
				['type3', 'action3', 'value3', 3, 3, 'item3'],
			];

			$actualValue = ActivityLogger::LOG_FLUSH_THRESHOLD_DEFAULT;
			runkit_constant_redefine(ActivityLogger::class . '::LOG_FLUSH_THRESHOLD_DEFAULT', 3);
			$this->assert_flush_log_with_threshold($params);
			runkit_constant_redefine(ActivityLogger::class . '::LOG_FLUSH_THRESHOLD_DEFAULT', $actualValue);
		}

		/**
		 * @param array $params
		 * @return void
		 */
		private function assert_flush_log_with_threshold(array $params) {
			$i = 1;
			$threshold = count($params);
			$logger = ActivityLogger::getInstance();
			$logger->resetLogs();
			foreach ($params as $param) {
				$logger->addLog($param[0], $param[1], $param[2], $param[3], $param[4], $param[5]);
				if ($threshold !== $i) {
					$this->assertNotEmpty($logger->getLogs());
				}
				$i++;
			}

			$this->assertEmpty($logger->getLogs());
		}

		/**
		 * @return void
		 */
		public function test_flush_returns_true_if_logs_are_empty() {
			$logger = ActivityLogger::getInstance();
			$logger->resetLogs();

			$return = $logger->flush();
			$this->assertTrue($return);
		}

		/**
		 * @return array
		 */
		public function flush_data_provider() {
			return [
				'with single log' => [
					[['type1', 'action1', 'value1', 1, 10, null]],
					'INSERT INTO activity_logs (`type`, `action`, `value`, `objectid`, `userid`, `from`, `item`) VALUES (?, ?, ?, ?, ?, ?, ?)',
					'rest.reachtel.com',
					['type1', 'action1', 'value1', 1, 10, 'rest.reachtel.com', null]
				],
				'with multiple logs' => [
					[['type2', 'action2', 'value2', 2, 20, null], ['type3', 'action3', 'value3', 3, 30, 'item1']],
					'INSERT INTO activity_logs (`type`, `action`, `value`, `objectid`, `userid`, `from`, `item`) VALUES (?, ?, ?, ?, ?, ?, ?),(?, ?, ?, ?, ?, ?, ?)',
					null,
					['type2', 'action2', 'value2', 2, 20, null, null, 'type3', 'action3', 'value3', 3, 30, null, 'item1']
				]
			];
		}

		/**
		 * @dataProvider flush_data_provider
		 * @param array  $logs
		 * @param string $sql
		 * @param string $invocation
		 * @param array  $params
		 * @return void
		 * @throws \Exception From mock_function_param_value function.
		 */
		public function test_flush_executes_sql_and_resets_logs(array $logs, $sql, $invocation, array $params) {
			$logger = ActivityLogger::getInstance();
			$logger->resetLogs();

			if ($invocation) {
				$_SERVER['HTTP_HOST'] = $invocation;
			}

			foreach ($logs as $log) {
				$logger->addLog($log[0], $log[1], $log[2], $log[3], $log[4], $log[5]);
			}

			$this->mock_db_write_function($sql, $params);
			$return = $logger->flush();

			$this->assertTrue($return);
			$this->assertEmpty($logger->getLogs());
			unset($_SERVER['HTTP_HOST']);
		}

		/**
		 * @throws \Exception From mock_function_param_value function.
		 * @return void
		 */
		public function test_flush_returns_false() {
			$logger = ActivityLogger::getInstance();
			$logger->addLog(1, 2, 3, 4, 5);
			$this->mock_db_write_function('Wrong sql', []);

			$this->assertFalse($logger->flush());
			$this->assertEmpty($logger->getLogs());
		}

		/**
		 * @return array
		 */
		public function toggle_activity_logger_data_provider() {
			return [
				'when turned off' => [
					false,
					['type1', 'action1', 'value1', 1, 10, null],
					[]
				],
				'when turned on' => [
					true,
					['type1', 'action1', 'value1', 1, 10, null],
					[['type1', 'action1', 'value1', 1, 10, null, null]]
				]
			];
		}

		/**
		 * @param boolean $isActive
		 * @param array   $logs
		 * @param array   $expected
		 * @dataProvider toggle_activity_logger_data_provider
		 * @return void
		 */
		public function test_toggle_activity_logger($isActive, array $logs, array $expected) {
			$logger = ActivityLogger::getInstance();
			$logger->resetLogs();
			$logger->toggleLoggerActivation($isActive);
			$logger->addLog($logs[0], $logs[1], $logs[2], $logs[3], $logs[4], $logs[5]);

			$this->assertSameEquals($expected, $logger->getLogs());
		}

		/**
		 * @param string $sql
		 * @param array  $params
		 * @throws \Exception From mock_function_param_value function.
		 * @return void
		 */
		private function mock_db_write_function($sql, array $params) {
			$this->remove_mocked_functions('api_db_query_write');
			$this->mock_function_param_value(
				'api_db_query_write',
				[
					['params' => [$sql, $params], 'return' => true]
				],
				false
			);
		}

		/**
		 * @return void
		 */
		public function tearDown() {
			parent::tearDown();
			ActivityLogger::getInstance()->toggleLoggerActivation(false);
		}
	}
}
