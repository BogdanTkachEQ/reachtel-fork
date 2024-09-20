<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Autoload;

use Services\ActivityLogger;
use Services\Autoload\AbstractAutoloadStrategy;
use Services\Autoload\AutoloadLogger;
use Services\Autoload\DncAutoloadStrategy;

/**
 * Class DncAutoloadStrategyUnitTest
 */
class DncAutoloadStrategyUnitTest extends AbstractLineItemProcessorStrategyUnitTest
{
	/** @var ActivityLogger | \Phake_IMock*/
	private $activityLogger;

	/** @var string */
	private $type;

	/** @var integer */
	private $groupId;

	/** @var integer */
	private $listId;

	/** @var string */
	private $region;

	/** @var boolean */
	private $isSubscription;

	/** @var AutoloadLogger | \Phake_IMock */
	private $logger;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->activityLogger = \Phake::mock(ActivityLogger::class);
		\Phake::when($this->activityLogger)->addLog(\Phake::anyParameters())->thenReturn(true);
		$this->type = 'phone';
		$this->groupId = 123;
		$this->listId = 345;
		$this->region = 'AU';
		$this->isSubscription = true;
		$this->logger = \Phake::mock(AutoloadLogger::class);
		\Phake::when($this->logger)->addLog(\Phake::anyParameters())->thenReturn(true);
		parent::setUp();
	}

	/**
	 * @return AbstractAutoloadStrategy
	 */
	protected function getStrategy() {
		$strategy = new DncAutoloadStrategy(
			$this->fileProcessor,
			$this->activityLogger,
			$this->type,
			$this->groupId,
			$this->listId,
			$this->isSubscription,
			$this->region
		);

		$strategy->setLogger($this->logger);
		return $strategy;
	}

	/**
	 * @return array
	 */
	protected function getRequiredColumns() {
		return ['destination'];
	}

	/**
	 * @return void
	 */
	public function testPreProcessHookReturnsFalseForInvalidType() {
		$this->mock_function_param_value(
			'api_restrictions_donotcontact_is_valid_type',
			[
				['params' => $this->type, 'return' => false]
			],
			true
		);

		\Phake::when($this->logger)->addLog(\Phake::capture($log))->thenReturn(true);

		$this->assertFalse($this->getStrategy()->processFile('file'));
		$this->assertSameEquals('Invalid type ' . $this->type . '.', $log);
		$this->remove_mocked_functions('api_restrictions_donotcontact_is_valid_type');
	}

	/**
	 * @return void
	 */
	public function testPreProcessHookReturnsFalseWhenListDoesNotBelongToGroup() {
		$this->mock_function_param_value(
			'api_restrictions_donotcontact_list_belongs_to_group',
			[
				['params' => [$this->groupId, $this->listId], 'return' => false]
			],
			true
		);

		\Phake::when($this->logger)->addLog(\Phake::capture($log))->thenReturn(true);

		$this->assertFalse($this->getStrategy()->processFile('file'));
		$this->assertSameEquals(
			sprintf(
				'DNC list id %d does not belong to group id %d and so the file can not be processed.',
				$this->listId,
				$this->groupId
			),
			$log
		);
		$this->remove_mocked_functions('api_restrictions_donotcontact_list_belongs_to_group');
	}

	/**
	 * @return array
	 */
	public function processLineDataProvider() {
		return [
			'for subscribe returning true' => [true, true],
			'for subscribe returning false' => [true, false],
			'for unsubscribe returning true' => [false, true],
			'for unsubscribe returning false' => [false, false],
		];
	}

	/**
	 * @dataProvider processLineDataProvider
	 * @param boolean $isSubscription
	 * @param boolean $return
	 * @return void
	 */
	public function testProcessLine($isSubscription, $return) {
		$this->isSubscription = $isSubscription;
		$strategy = $this->getStrategy();
		$this->mock_function_param_value(
			'api_restrictions_donotcontact_is_valid_type',
			[
				['params' => $this->type, 'return' => true]
			],
			true
		);

		$this->mock_function_param_value(
			'api_restrictions_donotcontact_list_belongs_to_group',
			[
				['params' => [$this->groupId, $this->listId], 'return' => true]
			],
			true
		);

		if ($isSubscription) {
			$this->listen_mocked_function('api_restrictions_donotcontact_remove_single');
			$this->mock_function_value('api_restrictions_donotcontact_remove_single', $return);
		} else {
			$this->listen_mocked_function('api_restrictions_donotcontact_add');
			$this->mock_function_value('api_restrictions_donotcontact_add', $return);
		}

		$destination = '61400123456';
		$data = [['destination' => $destination]];
		\Phake::when($this->fileProcessor)->convertFileToArray(\Phake::anyParameters())->thenReturn($data);

		\Phake::when($this->activityLogger)
			->addLog(
				\Phake::capture($logType),
				\Phake::capture($logAction),
				\Phake::capture($logValue),
				\Phake::capture($logObjectId)
			)
			->thenReturn(true);

		$processed = $strategy->processFile('filepath');

		$this->assertEquals(true, $processed);

		$badRecords = $strategy->getBadRecords();
		$expectedBadRecordCount = $return ? 0 : 1;
		$processedCount = $return ? 1 : 0;

		$this->assertSameEquals($expectedBadRecordCount, count($badRecords));

		$expectedLogValue = $processedCount .
			' destinations' .
			(!$this->isSubscription ? ' added to the ' : ' removed from the ') .
			'list';

		$this->assertSameEquals('DONOTCONTACT', $logType);
		$this->assertSameEquals('AUTOLOAD', $logAction);
		$this->assertSameEquals('DONOTCONTACT', $logType);
		$this->assertSameEquals($this->listId, $logObjectId);
		$this->assertSameEquals($expectedLogValue, $logValue);

		if ($isSubscription) {
			$calledParams = $this->fetchListenedMockFunctionParamValues('api_restrictions_donotcontact_remove_single');
			$this->assertSameEquals(
				[
					$this->type,
					$destination,
					$this->listId,
					$this->region
				],
				$calledParams[0]['args']
			);
		} else {
			$calledParams = $this->fetchListenedMockFunctionParamValues('api_restrictions_donotcontact_add');
			$this->assertSameEquals(
				[
					$this->type,
					$destination,
					$this->listId,
					$this->region
				],
				$calledParams[0]['args']
			);
		}

		$this->remove_mocked_functions('api_restrictions_donotcontact_remove_single');
		$this->remove_mocked_functions('api_restrictions_donotcontact_add');
		$this->remove_mocked_functions('api_restrictions_donotcontact_is_valid_type');
		$this->remove_mocked_functions('api_restrictions_donotcontact_list_belongs_to_group');
	}
}
