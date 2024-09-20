<?php
/**
 * PCIRecorderModuleTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\PCI;

use Services\PCI\PCIRecorder;
use testing\module\AbstractDatabasePhpunitModuleTest;

/**
 * Module test for PCIRecorder class
 *
 * @runTestsInSeparateProcesses
 */
class PCIRecorderModuleTest extends AbstractDatabasePhpunitModuleTest
{
	/** @var PCIRecorder */
	private $pciRecorder;

	/**
	 * {@inheritDoc}
	 * @see \PHPUnit_Framework_TestCase::setUp()
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->pciRecorder = PCIRecorder::getInstance();
	}

	/**
	 * @return array
	 */
	public function getInstanceData() {
		return [
			'normal instance' => [],
			'auto start NOT match reporting script' => ['scripts/reporting/whatever.php'],
			'auto start match autoload script' => ['scripts/autoload/whatever.php'],
		];
	}

	/**
	 * @dataProvider getInstanceData
	 * @param string $argv
	 * @return void
	 */
	public function testGetInstance($argv = null) {
		if ($argv) {
			$argvBackup = $_SERVER['argv'][0];
			$_SERVER['argv'][0] = $argv;
		}

		PCIRecorder::destruct();
		$this->pciRecorder = PCIRecorder::getInstance();
		$this->assertInstanceOf(
			PCIRecorder::class,
			$this->pciRecorder
		);

		if ($argv) {
			$_SERVER['argv'][0] = $argvBackup;
		}
	}

	/**
	 * @return void
	 */
	public function testStartStopProcess() {
		$this->assertFalse($this->pciRecorder->isStarted());
		$this->pciRecorder->start();
		$this->assertTrue($this->pciRecorder->isStarted());
		$this->pciRecorder->stop();
		$this->assertFalse($this->pciRecorder->isStarted());
	}

	/**
	 * @return void
	 */
	public function testIsAutoStarted() {
		$this->assertFalse($this->pciRecorder->isAutoStarted());
		$this->pciRecorder->start();
		$this->assertFalse($this->pciRecorder->isAutoStarted());
		$this->pciRecorder->start(true);
		$this->assertTrue($this->pciRecorder->isAutoStarted());
	}

	/**
	 * @return void
	 */
	public function testAddTargetKeyRecorderTest() {
		// add a targetkey without start
		$this->pciRecorder->addTargetKey(1, 2);
		$records = $this->pciRecorder->getRecords();
		$this->assertInternalType('array', $records);
		$this->assertEmpty($records);
		$this->pciRecorder->resetRecords();

		// add a targetkey with start after
		$this->pciRecorder->addTargetKey(1, 2);
		$this->pciRecorder->start();
		$this->assertInternalType('array', $this->pciRecorder->getRecords());
		$this->assertEmpty($this->pciRecorder->getRecords());
		$this->pciRecorder->stop();
		$this->pciRecorder->resetRecords();

		// add a targetkey with start
		$this->pciRecorder->start();
		$this->pciRecorder->addTargetKey(1, 2);
		$this->assertInternalType('array', $this->pciRecorder->getRecords());
		$this->assertEquals(
			[1 => ['targetkey' => [2]]],
			$this->pciRecorder->getRecords()
		);
		$this->pciRecorder->stop();
		$this->pciRecorder->resetRecords();

		// add targetkey from multiple campaigns with start
		$this->pciRecorder->start();
		$this->pciRecorder->addTargetKey(1, 100);
		$this->pciRecorder->addTargetKey(1, 101);
		$this->pciRecorder->addTargetKey(2, 200);
		$this->assertInternalType('array', $this->pciRecorder->getRecords());
		$this->assertEquals(
			[
				1 => ['targetkey' => [100, 101]],
				2 => ['targetkey' => [200]],
			],
			$this->pciRecorder->getRecords()
		);
		$this->pciRecorder->stop();
		$this->pciRecorder->resetRecords();
	}

	/**
	 * @return void
	 */
	public function testAddMergeDataRecorderTest() {
		// add a targetkey without start
		$this->pciRecorder->addMergeData(1, 2, 3, 4);
		$records = $this->pciRecorder->getRecords();
		$this->assertInternalType('array', $records);
		$this->assertEmpty($records);
		$this->pciRecorder->resetRecords();

		// add a targetkey with start after
		$this->pciRecorder->addMergeData(1, 2, 3, 4);
		$this->pciRecorder->start();
		$this->assertInternalType('array', $this->pciRecorder->getRecords());
		$this->assertEmpty($this->pciRecorder->getRecords());
		$this->pciRecorder->stop();
		$this->pciRecorder->resetRecords();

		// add a targetkey with start
		$this->pciRecorder->start();
		$this->pciRecorder->addMergeData(1, 2, 3, 4);
		$this->assertInternalType('array', $this->pciRecorder->getRecords());
		$this->assertEquals(
			[1 => ['merge_data' => [2 => [3 => 4]]]],
			$this->pciRecorder->getRecords()
		);
		$this->pciRecorder->stop();
		$this->pciRecorder->resetRecords();

		// add targetkey from multiple campaigns with start
		$this->pciRecorder->start();
		$this->pciRecorder->addMergeData(1, 't1', 3, 4);
		$this->pciRecorder->addMergeData(1, 't2', 3, 4);
		$this->pciRecorder->addMergeData(2, 't1', 3, 4);
		$this->assertInternalType('array', $this->pciRecorder->getRecords());
		$this->assertEquals(
			[
				1 => ['merge_data' => ['t1' => [3 => 4], 't2' => [3 => 4]]],
				2 => ['merge_data' => ['t1' => [3 => 4]]],
			],
			$this->pciRecorder->getRecords()
		);
		$this->pciRecorder->stop();
		$this->pciRecorder->resetRecords();
	}

	/**
	 * @return void
	 */
	public function testSavedRecordsAfterStopTest() {
		$this->pciRecorder->start();
		$this->pciRecorder->addTargetKey(1, 2);
		$this->pciRecorder->addMergeData(1, 2, 3, 4);

		$this->assertInternalType('array', $this->pciRecorder->getRecords());
		$this->assertNotEmpty($this->pciRecorder->getRecords());

		$this->pciRecorder->stop();
		// records are still saved
		$this->assertNotEmpty($this->pciRecorder->getRecords());
	}

	/**
	 * @return void
	 */
	public function testRecordsHydrateTargetsFileUpload() {
		$this->pciRecorder->start();
		// row t1 only targeykey
		$this->pciRecorder->addTargetKey(1, 't1');
		// row t2 only one merge_data
		$this->pciRecorder->addMergeData(1, 't2', 'col1', 1);
		// row t3 only 2 merge_data
		$this->pciRecorder->addMergeData(1, 't3', 'col1', 2);
		$this->pciRecorder->addMergeData(1, 't3', 'col2', 3);
		// row t4 targeykey + merge_data
		$this->pciRecorder->addTargetKey(1, 't4');
		$this->pciRecorder->addMergeData(1, 't4', 'col1', 4);

		$records = $this->pciRecorder->getRecords('targets_fileupload');
		$this->assertInternalType('array', $records);
		// only one campaign id 1
		$this->assertCount(1, $records);
		$this->assertArrayHasKey(1, $records);
		// assert keys from hydratator
		$this->assertArrayHasKey('targetkey', $records[1]);
		$this->assertArrayHasKey('merge_data', $records[1]);
		// assert targetkey only t1 and t4
		$this->assertEquals(['t1', 't4'], $records[1]['targetkey']);
		// assert merge_data only t1 and t4
		$this->assertEquals(['t2', 't3', 't4'], $records[1]['merge_data']);
		$this->pciRecorder->stop();
		$this->pciRecorder->resetRecords();

		// test only targeykey
		$this->pciRecorder->start();
		// row t1 only targeykey
		$this->pciRecorder->addTargetKey(1, 't1');
		$records = $this->pciRecorder->getRecords('targets_fileupload');
		$this->assertInternalType('array', $records);
		$this->assertArrayHasKey('targetkey', $records[1]);
		$this->assertArrayHasKey('merge_data', $records[1]);
		// assert targetkey only t1
		$this->assertEquals(['t1'], $records[1]['targetkey']);
		// assert no merge_data
		$this->assertEquals([], $records[1]['merge_data']);
		$this->pciRecorder->stop();
		$this->pciRecorder->resetRecords();

		// test only merge data
		$this->pciRecorder->start();
		// row t1 only targeykey
		$this->pciRecorder->addMergeData(1, 't1', 'col1', 1);
		$records = $this->pciRecorder->getRecords('targets_fileupload');
		$this->assertInternalType('array', $records);
		$this->assertArrayHasKey('targetkey', $records[1]);
		$this->assertArrayHasKey('merge_data', $records[1]);
		// assert targetkey only t1
		$this->assertEquals([], $records[1]['targetkey']);
		// assert no merge_data
		$this->assertEquals(['t1'], $records[1]['merge_data']);
		$this->pciRecorder->stop();
		$this->pciRecorder->resetRecords();
	}

	/**
	 * @return void
	 * @group aaaa
	 */
	public function testNotify() {
		$this->listen_mocked_function('api_email_template');
		$this->mock_function_value(
			'api_email_template',
			'$return = func_get_arg(0);', // return the $email parameter
			true
		);

		// recorder has not started, no email
		$this->pciRecorder->notify();
		$this->assertListenMockFunctionHasBeenCalled(
			'api_email_template',
			false
		);

		$this->remove_mocked_functions('api_email_template');
		$this->listen_mocked_function('api_email_template');
		$this->mock_function_value(
			'api_email_template',
			'$return = func_get_arg(0);', // return the $email parameter
			true
		);
		// recorder started but no records
		$this->pciRecorder->start();
		$this->pciRecorder->notify();
		$this->assertListenMockFunctionHasBeenCalled(
			'api_email_template',
			false
		);

		$this->remove_mocked_functions('api_email_template');
		$this->listen_mocked_function('api_email_template');
		$this->mock_function_value(
			'api_email_template',
			'$return = func_get_arg(0);', // return the $email parameter
			true
		);
		// recorder started and records for unknown campaign id
		$this->pciRecorder->addTargetKey(9999, 'key');
		$this->pciRecorder->addMergeData(9999, 'key', 'col', 'val');
		$this->pciRecorder->notify();
		$this->assertListenMockFunctionHasBeenCalled(
			'api_email_template',
			false
		);

		// recorder started and records for existing campaign but no sftp email notification is set, so default email
		$campaignName = uniqid('pci-');
		$email = [
			'to' => 'ReachTEL Support <support@ReachTEL.com.au>',
			'from' => 'ReachTEL Support <support@ReachTEL.com.au>',
			'subject' => '[ReachTEL] PCI data detected',
			'content' => <<<EMAIL
Hi,

On 2012-12-12 at 12:12 Australia/Brisbane time, you sent us a file for processing.

As per ReachTEL's data retention policy, we have assessed the file's contents.
Upon this assessment, we believe that the file contains personal credit card information and we have masked this data as per the data retention policy.

If these details are not required for us to provide you with our service, then we recommend that you modify your file structure and content in future to not send us such information.

Please take the time to review the following campaign(s) data:
 * {$campaignName}:
	- 1 targetkey(s)
	- 1 merge data record in column 'col'

If you believe you have been sent this email in error or have any questions please contact us at 1800 42 77 06 or support@reachtel.com.au

Regards,

The ReachTEL Team

EMAIL
		];
		$campaignId = api_campaigns_add($campaignName, 'phone');
		$groupId = api_groups_add(uniqid('pci-'));
		$this->assertTrue(
			api_campaigns_setting_set($campaignId, 'groupowner', $groupId)
		);

		$this->remove_mocked_functions('api_email_template');
		$this->listen_mocked_function('api_email_template');
		$this->mock_function_value(
			'api_email_template',
			'$return = func_get_arg(0);', // return the $email parameter
			true
		);
		$this->pciRecorder->addTargetKey($campaignId, 'key');
		$this->pciRecorder->addMergeData($campaignId, 'key', 'col', 'val');
		$this->mock_function_value(
			'date',
			'2012-12-12 at 12:12'
		);
		$this->pciRecorder->notify();
		self::remove_mocked_functions('date');
		$this->assertListenMockFunction(
			'api_email_template',
			[['args' => [$email], 'return' => $email]]
		);
		// reset Listener for next tests
		$this->resetListenMockFunction('api_email_template');

		// now sftp email notification is set and notification should be sent
		$this->assertTrue(
			api_groups_setting_set($groupId, 'sftpemailnotificationto', 'email@phpunit.test')
		);
		$email['to'] = 'email@phpunit.test';
		$email['cc'] = 'ReachTEL Support <support@ReachTEL.com.au>';

		// mock date
		$this->mock_function_value(
			'date',
			'2012-12-12 at 12:12'
		);
		$this->pciRecorder->notify();
		self::remove_mocked_functions('date');

		$this->assertListenMockFunction(
			'api_email_template',
			[['args' => [$email], 'return' => $email]]
		);

		self::remove_mocked_functions();
	}
}
