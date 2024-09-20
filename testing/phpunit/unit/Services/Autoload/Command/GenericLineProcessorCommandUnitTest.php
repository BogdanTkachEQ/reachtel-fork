<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Autoload\Command;

use Models\Autoload\AutoloadDTO;
use Phake;
use Services\Autoload\AutoloadLogger;
use Services\Autoload\Command\GenericLineProcessorCommand;
use Services\Autoload\LineExclusionEvaluator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class GenericLineProcessorCommandUnitTest
 */
class GenericLineProcessorCommandUnitTest extends AbstractPhpunitUnitTest
{
	/** @var AutoloadDTO | \Phake_IMock */
	private $dto;

	/** @var \DateTimeZone */
	private $timezone;

	/** @var GenericLineProcessorCommand */
	private $command;

	/** @var AutoloadLogger | \Phake_IMock */
	private $logger;

	/** @var LineExclusionEvaluator | \Phake_IMock */
	private $lineExclusionEvaluator;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->dto = Phake::mock(AutoloadDTO::class);
		$this->timezone = new \DateTimeZone('Australia/Sydney');
		$this->lineExclusionEvaluator = Phake::mock(LineExclusionEvaluator::class);
		Phake::when($this->lineExclusionEvaluator)->evaluate(Phake::anyParameters())->thenReturn(false);
		$this->command = new GenericLineProcessorCommand($this->dto, $this->timezone, $this->lineExclusionEvaluator);
		$this->logger = Phake::mock(AutoloadLogger::class);
		$this->command->setLogger($this->logger);
	}

	/**
	 * @return void
	 * @expectedException Services\Autoload\Exceptions\AutoloadLineProcessorCommandException
	 * @expectedExceptionMessage Campaign id not set while processing line
	 */
	public function testExecuteThrowsExceptionWhenCampaignIdIsNotPassed() {
		$this->command->execute([]);
	}

	/**
	 * @return array
	 */
	public function executeDataProvider() {
		return [
			'when add target is succesfull' => [true, true],
			'when add target fails' => [false, false]
		];
	}

	/**
	 * @param boolean $addTargetResult
	 * @param boolean $expected
	 * @return void
	 * @dataProvider executeDataProvider
	 */
	public function testExecute($addTargetResult, $expected) {
		$campaignid = 12345;
		$this->command->setCampaignId($campaignid);
		$destinationColumn = 'Destination';
		$destination = '0412345678';
		$callDate = '2020-04-17';
		$nextAttemptTime = '11:00:00';
		$callDateColumn = 'call_date';
		$targetKeyColumn = 'targetkey';
		$targetKey = 'test-target';
		Phake::when($this->dto)->getDestinationColumnName()->thenReturn($destinationColumn);
		Phake::when($this->dto)->getCallDateColumnName()->thenReturn($callDateColumn);
		Phake::when($this->dto)->getNextAttemptTime()->thenReturn($nextAttemptTime);
		Phake::when($this->dto)->getTargetKeyColumnName()->thenReturn($targetKeyColumn);

		$line = [
			$destinationColumn => $destination,
			$callDateColumn => $callDate,
			$targetKeyColumn => $targetKey
		];

		$nextAttempt = '17-04-2020 11:00:00';

		$this->mock_function_param_value(
			'api_targets_add_single',
			[
				['params' => [$campaignid, $destination, $targetKey, null, $line, $nextAttempt], 'return' => $addTargetResult],
			],
			false
		);

		$this->assertSameEquals($expected, $this->command->execute($line));
		Phake::verify($this->logger, Phake::times(1))->addLog(Phake::anyParameters());
	}

	/**
	 * @return void
	 */
	public function testExecuteWhenExclusionEvaluatesTrue() {
		$campaignid = 12345;
		$this->command->setCampaignId($campaignid);
		$destinationColumn = 'Destination';
		$destination = '0412345678';
		$callDate = '2020-04-17';
		$nextAttemptTime = '11:00:00';
		$callDateColumn = 'call_date';
		$targetKeyColumn = 'targetkey';
		$targetKey = 'test-target';
		Phake::when($this->dto)->getDestinationColumnName()->thenReturn($destinationColumn);
		Phake::when($this->dto)->getCallDateColumnName()->thenReturn($callDateColumn);
		Phake::when($this->dto)->getNextAttemptTime()->thenReturn($nextAttemptTime);
		Phake::when($this->dto)->getTargetKeyColumnName()->thenReturn($targetKeyColumn);

		$line = [
			$destinationColumn => $destination,
			$callDateColumn => $callDate,
			$targetKeyColumn => $targetKey
		];

		$targetId = 324324;

		Phake::when($this->lineExclusionEvaluator)->evaluate(Phake::anyParameters())->thenReturn(true);

		$this->listen_mocked_function('api_targets_add_single');
		$this->mock_function_value('api_targets_add_single', $targetId);

		$this->listen_mocked_function('api_targets_abandontarget');
		$this->mock_function_value('api_targets_abandontarget', true);

		$this->assertSameEquals(true, $this->command->execute($line));

		$called_params = $this->fetchListenedMockFunctionParamValues('api_targets_add_single');
		$args = $called_params[0]['args'];
		$this->assertSameEquals(
			[$campaignid, $destination, $targetKey, null, $line, null],
			$args
		);

		$called_params = $this->fetchListenedMockFunctionParamValues('api_targets_abandontarget');
		$args = $called_params[0]['args'];
		$this->assertSameEquals(
			[$targetId, 'Excluded'],
			$args
		);
	}
}
