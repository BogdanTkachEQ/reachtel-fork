<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Autoload;

use Phake;
use Services\Autoload\AutoloadContext;
use Services\Autoload\AutoloadLogger;
use Services\Autoload\Interfaces\AutoloadStrategyInterface;
use Services\Exceptions\File\CryptoException;
use Services\File\Interfaces\DecryptorInterface;
use Services\Validators\Interfaces\RunControllerInterface;
use testing\AbstractPhpunitTest;

/**
 * Class AutoloadContextUnitTest
 */
class AutoloadContextUnitTest extends AbstractPhpunitTest
{
	const SFTP_HOSTNAME = 'test-host';
	const SFTP_USERNAME = 'test-user';
	const SFTP_PASSWORD = 'test-pass';
	const SFTP_PATH = '/path/';

	/**
	 * @var AutoloadStrategyInterface | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $strategy;

	/**
	 * @var AutoloadContext
	 */
	private $context;

	/**
	 * @var RunControllerInterface
	 */
	private $runController;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->strategy = $this
			->getMockBuilder(AutoloadStrategyInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$this->runController = Phake::mock(RunControllerInterface::class);
		Phake::when($this->runController)->stopRun()->thenReturn(false);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Sftp data is missing required keys.
	 * @return void
	 */
	public function testConstructorThrowsException() {
		$context = new AutoloadContext(
			$this->strategy,
			[],
			$this->runController
		);
	}

	/**
	 * @return void
	 */
	public function testGetLoggerInstanceOfLogger() {
		$this->assertInstanceOf(AutoloadLogger::class, $this->getContext()->getLogger());
	}

	/**
	 * @return void
	 */
	public function testFlushLogs() {
		$this
			->getContext()
			->getLogger()
			->addLog('Test Log 1')
			->addLog('Test log 2');

		$expected = "Test Log 1\nTest log 2";

		$this->assertSameEquals($expected, $this->getContext()->flushLogs());
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Failure notification email requires to be set.
	 * @return void
	 */
	public function testProcessThrowsException() {
		$this->getContext()->setFailureNotificationEmail(null);
		$this->getContext()->process('filename');
	}

	/**
	 * @return void
	 */
	public function testProcessStopsExecutionCheckingOnRunController() {
		$stopReason = 'Stop Reason';
		Phake::when($this->runController)->stopRun()->thenReturn(true);
		Phake::when($this->runController)->getStopReason()->thenReturn($stopReason);
		self::assertTrue(
			$this
			->getContext()
			->process('test')
		);

		$this->assertSameEquals(
			'Stopping execution: ' . $stopReason,
			$this->getContext()->flushLogs()
		);
	}

	/**
	 * @return void
	 */
	public function testProcessFailsToFetchFile() {
		$this->getContext()->setFailureNotificationEmail('test@test.com');
		$filename = 'test';
		$this->mockFetchFile($filename, false);
		$this->assertFalse($this->getContext()->process($filename));
		$expectedLogs = "Downloading file...\nFailed to fetch file " . $filename;
		$this->assertSameEquals($expectedLogs, $this->getContext()->flushLogs());
	}

	/**
	 * @return void
	 */
	public function testProcess() {
		$filename = 'test';
		$decryptor = Phake::mock(DecryptorInterface::class);
		Phake::when($decryptor)->setFile(Phake::anyParameters())->thenReturnSelf();
		$content = 'Test file content';
		Phake::when($decryptor)->decrypt()->thenReturn($content);

		$this->listen_mocked_function('file_put_contents');
		$this->mock_function_param_value(
			'file_put_contents',
			[
				['params' => ["/tmp/" . $filename, $content], 'return' => true]
			],
			false
		);

		$context = new AutoloadContext(
			$this->strategy,
			[
				'hostname' => self::SFTP_HOSTNAME,
				'username' => self::SFTP_USERNAME,
				'password' => self::SFTP_PASSWORD,
				'path' => self::SFTP_PATH
			],
			$this->runController,
			$decryptor
		);
		$context->setFailureNotificationEmail('test@test.com');
		$this->mockFetchFile($filename, true);

		$this->mock_function_value('unlink', true);

		$this
			->strategy
			->expects($this->once())
			->method('processFile')
			->with('/tmp/' . $filename)
			->willReturn(true);

		$this->assertTrue($context->process($filename));

		$this->assertListenMockFunction(
			'file_put_contents',
			[
				['args' => ['/tmp/' . $filename, $content], 'return' => true]
			]
		);

		$this->remove_mocked_functions('file_put_contents');
		$expectedLogs = "Downloading file...\nOK\nDecrypting file...\nFile decrypted...\nRemoving file /tmp/" . $filename . "\nJob done!!!";
		$this->assertSameEquals($expectedLogs, $context->flushLogs());
	}

	/**
	 * @return void
	 */
	public function testProcessWithDecryptionThrowsException() {
		$decryptor = Phake::mock(DecryptorInterface::class);
		Phake::when($decryptor)->setFile(Phake::anyParameters())->thenReturnSelf();
		Phake::when($decryptor)->decrypt()->thenThrow(new CryptoException('Error happened during decryption'));

		$context = new AutoloadContext(
			$this->strategy,
			[
				'hostname' => self::SFTP_HOSTNAME,
				'username' => self::SFTP_USERNAME,
				'password' => self::SFTP_PASSWORD,
				'path' => self::SFTP_PATH
			],
			$this->runController,
			$decryptor
		);

		$context->setFailureNotificationEmail('test@test.com');
		$filename = 'test';
		$this->mockFetchFile($filename, true);

		$this->mock_function_value('unlink', true);
		$this->assertFalse($context->process($filename));
		$this->assertSameEquals(
			"Downloading file...\nOK\nDecrypting file...\nError happened during decryption\nRemoving file /tmp/" . $filename,
			$context->flushLogs()
		);
	}

	/**
	 * @return void
	 */
	public function testProcessCatchesExceptionFromStrategyAndAddToLogs() {
		$this->getContext()->setFailureNotificationEmail('test@test.com');
		$filename = 'test';
		$this->mockFetchFile($filename, true);
		$this->mock_function_value('unlink', true);

		$exceptionMessage = 'Test exception message';
		$this
			->strategy
			->expects($this->once())
			->method('processFile')
			->with('/tmp/' . $filename)
			->willThrowException(new \Exception($exceptionMessage));

		$this->assertFalse($this->getContext()->process($filename));

		$this->assertSameEquals(
			"Downloading file...\nOK\n" . $exceptionMessage . "\nRemoving file /tmp/" . $filename . "\nJob done!!!",
			$this->getContext()->flushLogs()
		);
	}

	/**
	 * @param string  $filename
	 * @param boolean $return
	 * @return void
	 */
	private function mockFetchFile($filename, $return) {
		$this->remove_mocked_functions('api_misc_sftp_get');

		$this->mock_function_param_value(
			'api_misc_sftp_get',
			[
				[
					'params' => [
						[
							'hostname' => self::SFTP_HOSTNAME,
							'username' => self::SFTP_USERNAME,
							'password' => self::SFTP_PASSWORD,
							'localfile' => '/tmp/' . $filename,
							'remotefile' => self::SFTP_PATH . 'test'
						]
					],
					'return' => $return
				]
			],
			false
		);
		$this->remove_mocked_functions('api_email_template');
		$this->mock_function_value('api_email_template', true);
	}

	/**
	 * @return AutoloadContext
	 */
	private function getContext() {
		if (!$this->context) {
			$this
				->strategy
				->expects($this->once())
				->method('setLogger')
				->with($this->isInstanceOf(AutoloadLogger::class));

			$this->context = new AutoloadContext(
				$this->strategy,
				[
					'hostname' => self::SFTP_HOSTNAME,
					'username' => self::SFTP_USERNAME,
					'password' => self::SFTP_PASSWORD,
					'path' => self::SFTP_PATH
				],
				$this->runController
			);
		}

		return $this->context;
	}
}
