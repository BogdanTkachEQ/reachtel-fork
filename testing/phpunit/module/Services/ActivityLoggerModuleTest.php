<?php
/**
 * ActivityLoggerModuleTest
 * Module tests for Activity Logger
 *
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services;

use Services\ActivityLogger;
use testing\module\AbstractDatabasePhpunitModuleTest;

/**
 * Class ActivityLoggerModuleTest
 */
class ActivityLoggerModuleTest extends AbstractDatabasePhpunitModuleTest
{
	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		ActivityLogger::getInstance(null, true)->toggleLoggerActivation(true);
	}

	/**
	 * @return array
	 */
	public function log_data_provider() {
		return [
			'single log' => [
				[['AUDIO', 'action1', 'value1', 1, 10, 'item1']],
				[
					[
						'type' => 'AUDIO',
						'userid' => '10',
						'action' => 'action1',
						'value' => 'value1',
						'objectid' => '1',
						'item' => 'item1'
					]
				]
			],
			'multiple logs' => [
				[
					['CAMPAIGNS', 'action2', 'value2', 2, 20, null],
					['USERS', 'action3', 'value3', 3, 30, 'item'],
				],
				[
					[
						'type' => 'CAMPAIGNS',
						'userid' => '20',
						'action' => 'action2',
						'value' => 'value2',
						'objectid' => '2',
						'item' => null
					],
					[
						'type' => 'USERS',
						'userid' => '30',
						'action' => 'action3',
						'value' => 'value3',
						'objectid' => '3',
						'item' => 'item'
					]
				]
			]
		];
	}

	/**
	 * @dataProvider log_data_provider
	 * @param array $logs
	 * @param array $expected
	 * @return void
	 * @throws \Exception If sql is malformed.
	 */
	public function test_if_logs_are_written_to_activity_logs_table(array $logs, array $expected) {
		$this->purge_all_activity_logs();

		$logger = ActivityLogger::getInstance();

		foreach ($logs as $log) {
			$logger->addLog($log[0], $log[1], $log[2], $log[3], $log[4], $log[5]);
		}

		$this->assertEmpty($this->fetch_all_activity_logs());
		$return = $logger->flush();
		$this->assertTrue($return);
		$results = $this->fetch_all_activity_logs();

		$this->assertSameEquals($expected, $results);
		$this->assertEmpty($logger->getLogs());
	}

	/**
	 * @return void
	 */
	public function test_if_activity_logging_is_disabled() {
		$this->purge_all_activity_logs();
		$logger = ActivityLogger::getInstance();
		$logger->resetLogs();
		$logger->toggleLoggerActivation(false);

		$logger->addLog('CAMPAIGNS', 'action2', 'value2', 2, 20, null);
		$logger->flush();

		$this->assertEmpty($this->fetch_all_activity_logs());
		$logger->toggleLoggerActivation(true);
	}

	/**
	 * @return void
	 */
	public function test_if_logs_are_flushed_when_treshold_is_reached() {
		$this->purge_all_activity_logs();

		define('LOG_FLUSH_THRESHOLD', 2);
		$logger = ActivityLogger::getInstance();
		$logger->resetLogs();

		$logger->addLog('CAMPAIGNS', 'action2', 'value2', 2, 20, null);
		$this->assertEmpty($this->fetch_all_activity_logs());

		$logger->addLog('USERS', 'action3', 'value3', 3, 30, 'item');
		$this->assertEmpty($logger->getLogs());
		$this->assertSameEquals(
			[
				[
					'type' => 'CAMPAIGNS',
					'userid' => '20',
					'action' => 'action2',
					'value' => 'value2',
					'objectid' => '2',
					'item' => null
				],
				[
					'type' => 'USERS',
					'userid' => '30',
					'action' => 'action3',
					'value' => 'value3',
					'objectid' => '3',
					'item' => 'item'
				]
			],
			$this->fetch_all_activity_logs()
		);

		runkit_constant_remove('LOG_FLUSH_THRESHOLD');
	}

	/**
	 * @return void
	 */
	public function test_logs_with_blacklisted_items_are_neglected() {
		$this->purge_all_activity_logs();
		$logger = ActivityLogger::getInstance();
		$logger->resetLogs();
		$logger
			->addLog('CAMPAIGNS', 'action2', 'value2', 2, 20, 'heartbeattimestamp')
			->addLog('CAMPAIGNS', 'action2', 'value2', 2, 20, 'valid_item');
		$logger->flush();
		$this->assertSameEquals(
			[
				[
					'type' => 'CAMPAIGNS',
					'userid' => '20',
					'action' => 'action2',
					'value' => 'value2',
					'objectid' => '2',
					'item' => 'valid_item'
				]
			],
			$this->fetch_all_activity_logs()
		);
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		$this->purge_all_activity_logs();
		ActivityLogger::getInstance()->toggleLoggerActivation(false);
		parent::tearDown();
	}

	/**
	 * @return array
	 * @codeCoverageIgnore
	 * @throws \Exception If sql is malformed.
	 */
	private function fetch_all_activity_logs() {
		$sql = sprintf(
			'SELECT `type`, `userid`, `action`, `value`, `objectid`, `item` FROM %s',
			ActivityLogger::TABLE_NAME
		);

		$rs = api_db_query_read($sql);

		return $rs->RecordCount() ? $rs->GetArray() : [];
	}

	/**
	 * @codeCoverageIgnore
	 * @return boolean
	 */
	private function purge_all_activity_logs() {
		$sql = sprintf('DELETE FROM %s', ActivityLogger::TABLE_NAME);
		return api_db_query_write($sql) !== false;
	}
}
