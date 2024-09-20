<?php
/**
 * ApiTargetsModuleTest
 * Module test for api_targets.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use DateTime;
use Models\Entities\QueueFile;
use Services\ActivityLogger;
use Services\Campaign\Archiver\ArchiverEnum;
use Services\Campaign\Archiver\BulkTargetArchiver;
use Services\File\QueuedFile;
use Services\PCI\PCIRecorder;
use Services\Utils\CsvFunctions;
use Services\Utils\XlsFunctions;
use Symfony\Component\Filesystem\Filesystem;
use testing\module\traits\CampaignDataArchiveTest;

/**
 * Api Targets Module Test
 */
class ApiTargetsModuleTest extends AbstractPhpunitModuleTest
{
	use CampaignDataArchiveTest;
	use helpers\UserModuleHelper;

	/**
	 * @group api_targets_add_single
	 * @return void
	 */
	public function test_api_targets_add_single() {
		// campaign is not numeric
		$this->assertFalse(api_targets_add_single(null, '0451123456'));
		$this->assertFalse(api_targets_add_single(false, '0451123456'));
		$this->assertFalse(api_targets_add_single(true, '0451123456'));

		// campaign does not exists
		$campaign_id = $this->get_expected_next_campaign_id();
		$this->assertFalse(api_targets_add_single($campaign_id, '0451123456'));

		// success
		$campaign_id = $this->create_new_campaign(null, 'phone');
		$this->assertGreaterThan(
			0,
			api_targets_add_single($campaign_id, '0451123456')
		);

		// PCI tests using PCIRecorder
		$pci_recorder = PCIRecorder::getInstance();

		// PCI data in targetkey
		$pci_recorder->start();
		$this->assertGreaterThan(
			0,
			api_targets_add_single(
				$campaign_id,
				'0410111001',
				'5555555555554444'
			)
		);
		$this->assertNotEmpty($pci_recorder->getRecords());
		$pci_recorder->stop(); // reset for next tests

		// PCI data in merge data
		$pci_recorder->start();
		$this->assertGreaterThan(
			0,
			api_targets_add_single(
				$campaign_id,
				'0410111001',
				'targetkey-1',
				null,
				['pci', '5555555555554444']
			)
		);
		$this->assertNotEmpty($pci_recorder->getRecords());
		$pci_recorder->stop(); // reset for next tests

		// PCI data as text in merge data
		$pci_recorder->start();
		$this->assertGreaterThan(
			0,
			api_targets_add_single(
				$campaign_id,
				'0410111001',
				'targetkey-1',
				null,
				['pci', 'I love to share my credit card details: 5555555555554444 !!!']
			)
		);
		$this->assertNotEmpty($pci_recorder->getRecords());
		$pci_recorder->stop(); // reset for next tests
	}

	/**
	 * @group api_targets_add_extradata_multiple
	 * @return void
	 */
	public function test_api_targets_add_extradata_multiple() {
		$campaign_id = $this->get_expected_next_campaign_id();
		$all_types = $this->get_campaign_types();
		$type = $all_types[rand(0, count($all_types) - 1)];
		api_campaigns_setting_set($campaign_id, CAMPAIGN_SETTING_TYPE, $type);

		$elements = [
			'data1' => 'data1',
			'data2' => 'data2',
			// specific MOR-1269 test
			'bugfix/MOR-1269' => 'grant foo on bar',
		];

		$targetkey = rand(1000, 10000);

		// campaign does not exists
		$this->assertSameEquals(
			false,
			api_targets_add_extradata_multiple('whatever', $targetkey, $elements)
		);

		// elements not an array
		$this->assertSameEquals(
			false,
			api_targets_add_extradata_multiple($campaign_id, $targetkey, 'not an array')
		);

		// elements empty array
		$this->assertSameEquals(
			true,
			api_targets_add_extradata_multiple($campaign_id, $targetkey, [])
		);

		// add merge data for the first time
		$this->assertSameEquals(
			true,
			api_targets_add_extradata_multiple($campaign_id, $targetkey, $elements)
		);

		$this->assertSameEquals(
			count($elements),
			api_data_merge_get_count($campaign_id, $targetkey)
		);

		// second call to trigger DUPLICATE
		$this->assertSameEquals(
			true,
			api_targets_add_extradata_multiple($campaign_id, $targetkey, $elements)
		);

		$this->assertSameEquals(
			count($elements),
			api_data_merge_get_count($campaign_id, $targetkey)
		);

		// PCI tests using PCIRecorder
		PCIRecorder::getInstance()->destruct();
		$pci_recorder = PCIRecorder::getInstance();

		// PCI data in targetkey but recorder not started
		$this->assertSameEquals(
			true,
			api_targets_add_extradata_multiple(
				$campaign_id,
				'5555555555554444',
				$elements,
				false
			)
		);
		$this->assertEmpty($pci_recorder->getRecords());
		$pci_recorder->resetRecords(); // reset for next tests

		// PCI data in targetkey
		$pci_recorder->start();
		$this->assertSameEquals(
			true,
			api_targets_add_extradata_multiple(
				$campaign_id,
				'5555555555554444',
				$elements
			)
		);
		$this->assertNotEmpty($pci_recorder->getRecords());
		$pci_recorder->resetRecords(); // reset for next tests

		// PCI data in merge data
		$pci_recorder->start();
		$this->assertSameEquals(
			true,
			api_targets_add_extradata_multiple(
				$campaign_id,
				rand(1000, 10000),
				['pci', '5555555555554444']
			)
		);

		if ($type === 'wash') {
			$this->assertEmpty($pci_recorder->getRecords());
		} else {
			$this->assertNotEmpty($pci_recorder->getRecords());
		}
		$pci_recorder->resetRecords(); // reset for next tests

		// PCI data as text in merge data
		$pci_recorder->start();
		$this->assertSameEquals(
			true,
			api_targets_add_extradata_multiple(
				$campaign_id,
				rand(1000, 10000),
				['pci', 'Please use my credit card : 5555555555554444']
			)
		);

		if ($type === 'wash') {
			$this->assertEmpty($pci_recorder->getRecords());
		} else {
			$this->assertNotEmpty($pci_recorder->getRecords());
		}
		$pci_recorder->resetRecords(); // reset for next tests
	}

	/**
	 * @group api_targets_add_callme
	 * @return void
	 */
	public function test_api_targets_add_callme() {
		// creating new callme campaign
		$callme_destination = '0731031234';
		$customer_number = '61400123456';
		$callme_campaign_name = 'CALLME-' . date('FY');

		$source_campaign_id = $this->create_new_campaign('source', 'phone');
		$callme_duplicate_campaign_id = $this->create_new_campaign('callme', 'phone', null, ['callmedestination' => $callme_destination]);

		$expected_campaign_id = $this->get_expected_next_campaign_id();

		$target_id = api_targets_add_callme($customer_number, [], $source_campaign_id, $callme_campaign_name, $callme_duplicate_campaign_id);

		// Check campaign was created
		$created_campaign_id = api_campaigns_nametoid($callme_campaign_name);
		$this->assertEquals($expected_campaign_id, $created_campaign_id);

		// Check target id looks right
		$this->assertInternalType('integer', $target_id);

		// Check callme destination
		$target = api_targets_getinfo($target_id);
		$this->assertEquals($target['destination'], $callme_destination);

		// Check mergedata has customer number
		$mergedata = api_data_merge_get_all($created_campaign_id, $target['targetkey']);
		$this->assertEquals($mergedata['customernumber'], $customer_number);

		// cleanup
		api_campaigns_delete($callme_duplicate_campaign_id);
		api_campaigns_delete($source_campaign_id);
		api_campaigns_delete($created_campaign_id);
	}

	/**
	 * @group api_targets_add_callme
	 * @return void
	 */
	public function test_api_targets_add_callme_twice() {
		// creating new callme campaign
		$callme_destination = '0731031234';
		$customer_number = '61400123456';
		$customer_number_2 = '61400123457';
		$callme_campaign_name = 'CALLME-' . date('FY');

		$source_campaign_id = $this->create_new_campaign('source', 'phone');
		$callme_duplicate_campaign_id = $this->create_new_campaign('callme', 'phone', null, ['callmedestination' => $callme_destination]);

		$expected_campaign_id = $this->get_expected_next_campaign_id();

		$target_id = api_targets_add_callme($customer_number, [], $source_campaign_id, $callme_campaign_name, $callme_duplicate_campaign_id);

		$created_campaign_id = api_campaigns_nametoid($callme_campaign_name);

		// Next, try when campaign already exists
		$next_target_id = api_targets_add_callme($customer_number_2, [], $source_campaign_id, $callme_campaign_name, $callme_duplicate_campaign_id);

		// Check target id looks right
		$this->assertInternalType('integer', $next_target_id);

		// Check callme destination
		$next_target = api_targets_getinfo($next_target_id);
		$this->assertEquals($next_target['destination'], $callme_destination);

		// Check mergedata has customer number
		$next_mergedata = api_data_merge_get_all($created_campaign_id, $next_target['targetkey']);
		$this->assertEquals($next_mergedata['customernumber'], $customer_number_2);

		// cleanup
		api_campaigns_delete($callme_duplicate_campaign_id);
		api_campaigns_delete($source_campaign_id);
		api_campaigns_delete($created_campaign_id);
	}

	/**
	 * @return array
	 */
	public function api_campaigns_archive_data_data_provider() {
		return $this->build_data_archive_data_provider(['targets']);
	}

	/**
	 * @return array
	 */
	public function api_targets_search_provider() {
		return [
			// failures
			'target does not exists in phone campaign' => [
				0, '0400000000', 'phone'
			],
			'target does not exists in wash campaign' => [
				0, '0400000001', 'wash'
			],
			'target does not exists in email campaign' => [
				0, 'does_not_exists@r.com', 'email'
			],
			'target not found in phone campaign' => [
				0, '0400000002', 'phone', ['0400000001']
			],
			'target not found in wash campaign' => [
				0, '0400000003', 'wash', ['0400000001']
			],
			'target not found in email campaign' => [
				0, 'does_not_exists@r.com', 'email', ['exists@r.com']
			],
			'target not trimmed not found in phone campaign' => [
				0, '   0400000004     ', 'phone', ['0400000001']
			],
			'target not trimmed not found in wash campaign' => [
				0, '   0400000005     ', 'wash', ['0400000001']
			],
			'target not trimmed not found in email campaign' => [
				0, '   does_not_exists@r.com     ', 'email', ['exists@r.com']
			],
			'REACHTEL-143 wash campaign targets not formatted' => [
				// @see REACHTEL-143: wash campaigns targets are not ffn formatted in DB
				0, '61400000143', 'wash', ['(+61) 400000143']
			],

			// success phone campaigns
			'target exists in phone campaign' => [
				1, '0400000006', 'phone', ['0400000006']
			],
			'formatted target exists in phone campaign' => [
				1, '61400000007', 'phone', ['0400000007']
			],
			'target not trimmed exists in phone campaign' => [
				1, '  0400000008   ', 'phone', ['0400000008']
			],
			'formatted target not trimmed exists in phone campaign' => [
				1, '  61400000009    ', 'phone', ['0400000009']
			],
			'target with + sign in phone campaign' => [
				1, '+61400002209', 'phone', ['0400002209']
			],
			'target with spaces in phone campaign' => [
				1, '61 400 002 210', 'phone', ['0400002210']
			],
			'target with some non-digits in phone campaign' => [
				1, '(+61) 400 002 220', 'phone', ['0400002220']
			],

			// success wash campaigns
			'target exists in wash campaign' => [
				1, '0400000010', 'wash', ['0400000010']
			],
			'formatted target exists in wash campaign' => [
				1, '61400000011', 'wash', ['0400000011']
			],
			'target not trimmed exists in wash campaign' => [
				1, ' 0400000012  ', 'wash', ['0400000012']
			],
			'formatted target not trimmed exists in wash campaign' => [
				1, '    61400000013', 'wash', ['0400000013']
			],
			'target with + sign in wash campaign' => [
				1, '+61400088209', 'wash', ['0400088209']
			],
			'target with spaces in wash campaign' => [
				1, '61 400 088 210', 'wash', ['0400088210']
			],
			'target with some non-digits in wash campaign' => [
				1, '(+61) 400 088 220', 'wash', ['0400088220']
			],

			// success email campaigns
			'target exists in wash campaign' => [
				1, 'blabla@r.com', 'email', ['blabla@r.com']
			],
			'target not trimmed exists in wash campaign' => [
				1, '   blabla@r.com    ', 'email', ['blabla@r.com']
			],
		];
	}

	/**
	 * @dataProvider api_targets_search_provider
	 * @group api_targets_search
	 * @param integer $expected
	 * @param string  $target
	 * @param string  $campaign_type
	 * @param array   $campaign_targets
	 * @return void
	 */
	public function test_api_targets_search($expected, $target, $campaign_type, array $campaign_targets = []) {
		$this->purge_all_campaigns();
		   // check/create campaign
		   $campaign_name = "TargetsSearch-{$campaign_type}-" . date("MY");
		   $campaign_id = api_campaigns_checknameexists($campaign_name);
		if (!$campaign_id) {
			$campaign_id = $this->create_new_campaign($campaign_name, $campaign_type);
		}
		   $this->assertTrue(api_targets_delete_all($campaign_id));
		if ($campaign_targets) {
			$this->assertTrue(
				$this->add_campaign_targets($campaign_id, $campaign_targets)
			);
		}

		// faking SESSION with no user security groupaccess
		$_SESSION['userid'] = $this->get_default_admin_id();
		$res = api_targets_search($target);
		unset($_SESSION);

		$this->assertInternalType('array', $res);
		$this->assertCount(
			$expected,
			$res,
			"Target search '{$target}' array count"
		);
	}

	/**
	 * @return array
	 */
	public function api_targets_search_archive_provider() {
		return [// failures
				[2, '0400000000', 'phone'],
				[2, '0400000001', 'wash'],
				[2, 'does_not_exists@r.com', 'email']
			];
	}

	/**
	 * @dataProvider api_targets_search_archive_provider
	 * @param integer $expected
	 * @param string  $target
	 * @param string  $campaign_type
	 * @return void
	 */
	public function test_api_targets_search_archived($expected, $target, $campaign_type) {
		$this->purge_all_campaigns();

		$campaign_id = $this->create_new_campaign(null, $campaign_type);
		$campaign_id2 = $this->create_new_campaign(null, $campaign_type);

		$this->assertTrue(
			$this->add_campaign_targets($campaign_id, [$target])
		);
		$archiver = new BulkTargetArchiver(10, \Phake::mock(ActivityLogger::class));
		$archiver->archiveCampaign($campaign_id, true);

		$this->assertTrue(
			$this->add_campaign_targets($campaign_id2, [$target])
		);

		// faking SESSION with no user security groupaccess
		$_SESSION['userid'] = $this->get_default_admin_id();
		$res = api_targets_search($target);
		unset($_SESSION);

		$this->assertInternalType('array', $res);
		$keys = array_keys($res);
		$this->assertCount(
			$expected,
			$res[$keys[0]],
			"Target search '{$target}' array count"
		);
	}

	/**
	 * @group api_targets_dedupe
	 * @return void
	 */
	public function test_api_targets_dedupe() {
		$source = $this->create_new_campaign(null, 'phone');

		// no depud to do
		$this->assertTrue(api_targets_dedupe($source));

		// test dedupe
		// add dedupe targets (same destinations but different targetkeys)
		$this->add_campaign_targets(
			$source,
			$targets = [
				'0401111111',
				'0402222222',
				'0401111111',
				'0402222222',
				'0401111111',
				'0402222222',
			]
		);

		// make sure duplicate targets are set as READY
		$statuses = api_data_target_status($source);
		$this->assertInternalType('array', $statuses);
		$this->assertEquals(
			$statuses,
			[
				'READY' => 6,
				'INPROGRESS' => 0,
				'REATTEMPT' => 0,
				'ABANDONED' => 0,
				'COMPLETE' => 0,
				'TOTAL' => 6,
			]
		);

		// dedupe
		$this->assertTrue(api_targets_dedupe($source));

		// check dedupe worked
		$statuses = api_data_target_status($source);
		$this->assertInternalType('array', $statuses);
		$this->assertEquals(
			$statuses,
			[
				'READY' => 2,
				'INPROGRESS' => 0,
				'REATTEMPT' => 0,
				'ABANDONED' => 4,
				'COMPLETE' => 0,
				'TOTAL' => 6,
			]
		);
		// check response data duplicates
		$this->assertCount(4, api_data_responses_getall_bycampaignid($source));

		// test dedupe catch Exception and log it
		$source = $this->create_new_campaign(null, 'phone');
		$this->add_campaign_targets($source, $targets);

		// mock api_targets_updatestatus to throw Exception
		$this->mock_function_value(
			'api_targets_updatestatus',
			'throw new \Exception("PHPUNIT TEST ONLY");',
			true
		);

		$this->assertFalse(api_targets_dedupe($source));

		// check error message
		$this->assertEquals(
			"CAMPAIGN_DEDUPE Exception 'PHPUNIT TEST ONLY' for source={$source};comparison=0",
			api_error_printiferror(['return' => true])
		);

		// safely remove mocked functions
		$this->remove_mocked_functions();
	}

	/**
	 * @return void
	 */
	public function test_api_targets_find_last_sent_event() {
		$source = $this->create_new_campaign(null, 'sms');
		$target = api_targets_add_single($source, "0411111111");

		$eventid = api_sms_send_log(5000, rand(1000, 2000), 99, "0411111111", "test 1");
		api_data_callresult_add($source, $eventid, $target, "SENT");

		$eventid1 = api_sms_send_log(5000, rand(3000, 4000), 99, "0411111111", "test");
		$sql = "INSERT INTO `sms_api_mapping` (`userid`, `billingtype`, `rid`, `uid`, `messageunits`, `billing_products_region_id`) VALUES (?, ?, ?, ?, ?, ?)";
		api_db_query_write($sql, array(1, "sms", $eventid1, 5000, 3, 2));
		$events = api_targets_find_last_sent_event("0411111111", 99);
		$this->assertEquals($eventid1, $events['eventid']);
	}

	/**
	 * @return void
	 */
	public function test_api_targets_find_last_sent_event_inverse() {
		$source = $this->create_new_campaign(null, 'sms');
		$target = api_targets_add_single($source, "0411111112");

		$eventid1 = api_sms_send_log(5000, rand(3000, 4000), 99, "0411111112", "test 1");
		api_data_callresult_add($source, $eventid1, $target, "SENT");

		$eventid = api_sms_send_log(5000, rand(1000, 2000), 99, "0411111112", "test");
		$sql = "INSERT INTO `sms_api_mapping` (`userid`, `billingtype`, `rid`, `uid`, `messageunits`, `billing_products_region_id`) VALUES (?, ?, ?, ?, ?, ?)";
		api_db_query_write($sql, array(1, "sms", $eventid, 5000, 3, 2));
		$events = api_targets_find_last_sent_event("0411111112", 99);
		$this->assertEquals($eventid1, $events['eventid']);
	}

	/**
	 * @return array
	 */
	public function fileupload_provider() {
		return [
			[uniqid("api-targets", true),[
				['targetkey', 'destination', 'merge_data1', 'merge_data2', 'rt-sendat'],
				['12346', '0412345679', 'merge_data1-2', 'merge_data2-2', ''],
				['12345', '0412345678', 'merge_data1-1', 'merge_data2-1', '2025-10-01 10:00:00'],
				['', '0412345679', 'merge_data1-2', 'merge_data2-2', ''],
			]],
			[uniqid("api-targets", true),[
				['targetkey', 'destination', 'merge_data1', 'merge_data2', 'rt-sendat'],
			]]
		];
	}

	/**
	 * @dataProvider fileupload_provider
	 * @param string $filename
	 * @param array  $data
	 * @return void
	 */
	public function test_api_targets_queued_fileupload_csv($filename, array $data) {
		//$this->purge_all_campaigns();
		$campaignid = $this->create_new_campaign(null, CAMPAIGN_TYPE_VOICE);
		$this->mock_function_value('api_queue_gearman_add', true);
		$filename = $filename . ".csv";
		$filepath = "/tmp/$filename";
		CsvFunctions::arrayToCsv($data, $filepath);
		$testCSVData = CsvFunctions::csvToArray($filepath);
		$queueItem = api_targets_queued_fileupload($campaignid, $filepath, $filename, 1);

		$this->assertCount(1, $queueItem->getQueueFiles());
		$this->assertTrue($queueItem->isCanRun());
		/**
		 * @var $queueFile QueueFile
		 */
		$queueFile = $queueItem->getQueueFiles()->first();
		$arrayData = CsvFunctions::csvStringToArray($queueFile->getData());

		if (file_exists($filepath)) {
			unlink($filepath);
		}
		$this->assertEquals($testCSVData, $arrayData);
	}

	/**
	 * @dataProvider fileupload_provider
	 * @param string $filename
	 * @param array  $data
	 * @return void
	 */
	public function test_api_targets_queued_fileupload_xls($filename, array $data) {
		//$this->purge_all_campaigns();
		$campaignid = $this->create_new_campaign(null, CAMPAIGN_TYPE_VOICE);
		$this->mock_function_value('api_queue_gearman_add', true);
		$filename = $filename . ".xls";
		$filepath = "/tmp/$filename";
		XlsFunctions::arrayToXls($data, $filepath);
		$testData = XlsFunctions::xlsToArray($filepath);
		$queueItem = api_targets_queued_fileupload($campaignid, $filepath, $filename, 1);

		$this->assertCount(1, $queueItem->getQueueFiles());
		$this->assertTrue($queueItem->isCanRun());
		/**
		 * @var $queueFile QueueFile
		 */
		$queueFile = $queueItem->getQueueFiles()->first();
		$tmpDataPath = "/tmp/" . uniqid() . ".xls";
		file_put_contents($tmpDataPath, $queueFile->getData());
		$storedData = XlsFunctions::xlsToArray($tmpDataPath);

		if (file_exists($filepath)) {
			unlink($filepath);
		}
		if (file_exists($tmpDataPath)) {
			unlink($tmpDataPath);
		}

		$this->assertEquals($testData, $storedData);
	}

	/**
	 * @return array
	 */
	public function fileupload_data_provider() {
		return [
			'with csv' => [
				'csv',
				function($file, $data) {
					return CsvFunctions::arrayToCsv($data, $file);
				}
			],
			'with xls' => [
				'xls',
				function($file, $data) {
					return XlsFunctions::arrayToXls($data, $file);
				}
			]
		];
	}

	/**
	 * @dataProvider fileupload_data_provider
	 * @param string   $type
	 * @param callable $file_processor
	 * @return void
	 * @throws \Exception Add target insert failed.
	 */
	public function test_api_targets_fileupload($type, callable $file_processor) {
		$this->purge_all_campaigns();
		$campaign_id = $this->create_new_campaign(null, CAMPAIGN_TYPE_VOICE);
		$data = [
			['targetkey', 'destination', 'merge_data1', 'merge_data2', 'rt-sendat'],
			['12346', '0412345679', 'merge_data1-2', 'merge_data2-2', ''],
			['12345', '0412345678', 'merge_data1-1', 'merge_data2-1', '2025-10-01 10:00:00'],
			['', '0412345679', 'merge_data1-2', 'merge_data2-2', ''],
		];

		$name = 'test_upload.' . $type;
		$file = tempnam('/tmp', $name);

		try {
			$file_processor($file, $data);
			$return = api_targets_fileupload($campaign_id, $file, $name);
		} catch (\Exception $exception) {
			unlink($file);
			throw $exception;
		}

		unlink($file);

		$expected = ["rows" => 4, "good" => 3, "bad" => 0, "pci" => 0, "defaulttargetkey" => true];
		$this->assertSameEquals($expected, $return);
		$targets = api_targets_listall($campaign_id);

		$i = 0;

		foreach ($targets as $targetid => $destination) {
			$i++;
			$this->assertSameEquals($data[$i][1], $destination);
			$expected_target_key = $data[$i][0] ?: $data[$i][1]; // destination will be target key if empty
			$target_data = api_targets_getinfo($targetid);
			$this->assertSameEquals($expected_target_key, $target_data['targetkey']);

			$expected_status = $data[$i][4] ? 'REATTEMPT' : 'READY';
			$this->assertSameEquals($expected_status, $target_data['status']);
			if ($data[$i][4] !== '') {
				$this->assertSameEquals($data[$i][4], $target_data['nextattempt']);
			}

			$merge_fields = api_targets_get_merge_data($targetid);
			$j = 1;
			foreach ($merge_fields as $merge_field) {
				$j++;
				$this->assertSameEquals($expected_target_key, $merge_field['targetkey']);
				$this->assertSameEquals($campaign_id, (int)$merge_field['campaignid']);

				if ($data[$i][$j] !== '') {
					$this->assertSameEquals($data[0][$j], $merge_field['element']);
					$this->assertSameEquals($data[$i][$j], $merge_field['value']);
				}
			}
		}
		$this->purge_all_campaigns();
	}

	/**
	 * @dataProvider fileupload_data_provider
	 * @param string   $type
	 * @param callable $file_processor
	 * @return void
	 * @throws \Exception Add target insert failed.
	 */
	public function test_api_targets_fileupload_on_duplicate_key_update($type, callable $file_processor) {
		$this->purge_all_campaigns();
		$campaignid = $this->create_new_campaign(null, CAMPAIGN_TYPE_VOICE);
		$destination = '0412345678';
		$targetkey = 'test-target-key';
		$targetid = api_targets_add_single($campaignid, $destination, $targetkey);
		$targets = api_targets_listall($campaignid);
		$this->assertCount(1, $targets);
		$this->assertSameEquals($destination, array_values($targets)[0]);
		$target_info = api_targets_getinfo($targetid);
		$this->assertSameEquals('READY', $target_info['status']);
		$this->assertNull($target_info['nextattempt']);

		$expected_destination = '0445687952';
		$next_attempt = '2030-10-01 10:00:00';
		$data = [
			['targetkey', 'destination', 'rt-sendat'],
			[$targetkey, $expected_destination, $next_attempt],
			['badtraget', '12345', '']
		];

		$name = 'test_upload.' . $type;
		$file = tempnam('/tmp', $name);

		try {
			$file_processor($file, $data);
			$return = api_targets_fileupload($campaignid, $file, $name);
		} catch (\Exception $exception) {
			unlink($file);
			throw $exception;
		}
		unlink($file);

		$expected = ["rows" => 3, "good" => 1, "bad" => 1, "pci" => 0, "defaulttargetkey" => false];
		$this->assertSameEquals($expected, $return);

		$targets = api_targets_listall($campaignid);
		$this->assertCount(1, $targets);
		$returned_target_id = array_keys($targets)[0];
		$this->assertSameEquals($targetid, $returned_target_id);
		$this->assertSameEquals($expected_destination, $targets[$returned_target_id]);
		$target_info = api_targets_getinfo($targetid);
		$this->assertSameEquals('REATTEMPT', $target_info['status']);
		$this->assertSameEquals($next_attempt, $target_info['nextattempt']);
		$this->purge_all_campaigns();
	}

	/**
	 * @return array
	 */
	public function fileupload_result_builder_provider() {
		return [
			[["defaulttargetkey" => true, "good" => 3, "bad" => 2, "pci" => 0, "rows" => 5]],
			[["defaulttargetkey" => false, "good" => 0, "bad" => 4, "pci" => 0, "rows" => 4]]
		];
	}

	/**
	 * @dataProvider fileupload_result_builder_provider
	 * @param array $results
	 * @return void
	 */
	public function test_api_targets_fileupload_result_builder(array $results) {
		$messages = api_targets_fileupload_result_builder($results);
		if ($results['defaulttargetkey']) {
			$expected[] = "WARNING: No targetkey was found for some records so the destination has been used instead.";
		}
		$expected[] = "Data successfully uploaded - Uploaded <strong>{$results["rows"]}</strong> "
			. "row(s) with <strong>{$results["rows"]}</strong> target(s): <strong>{$results["good"]}</strong> "
			. "good and <strong>{$results['bad']}</strong> bad.";
		$this->assertEquals($expected, $messages);
	}

	/**
	 * @return void
	 */
	public function test_api_targets_count_campaign_total() {
		$source = $this->create_new_campaign(null, 'sms');
		api_targets_add_single($source, "0411111112");
		api_targets_add_single($source, "0411111113");
		$this->assertEquals(2, api_targets_count_campaign_total($source));
	}

	/**
	 * @return void
	 */
	public function test_api_targets_abandontarget() {
		$campaign = $this->create_new_campaign(null, 'phone');
		$targetid = api_targets_add_single($campaign, '0412345687', 'test-target');
		$info = api_targets_getinfo($targetid);
		$this->assertSameEquals('READY', $info['status']);
		api_targets_abandontarget($targetid, 'Excluded');
		$info = api_targets_getinfo($targetid);
		$this->assertSameEquals('ABANDONED', $info['status']);
		$responses = api_data_responses_getall($targetid);
		$this->assertSameEquals(['REMOVED' => 'Excluded'], $responses);
		$this->purge_all_campaigns();
	}

	/**
	 * @return array
	 */
	public function add_single_target_for_wash_data_provider() {
		return [
			'non numeric targetkey should fail' => ['non-numeric', false],
			'numeric targetkey should be accepted' => ['1214565656', true],
			'system generated with prefix RT-TEST should be accepted' => ['RT-TEST-2432432', true],
			'system generated with prefix RT-API should be accepted' => ['RT-API-2432-432', true],
		];
	}

	/**
	 * @dataProvider add_single_target_for_wash_data_provider
	 * @param mixed   $targetKey
	 * @param boolean $expected
	 * @return void
	 */
	public function test_api_targets_add_single_wash_rejects_nonnumeric_targetkey_except_system_generated(
		$targetKey,
		$expected
	) {
		$campaign = $this->create_new_campaign(null, 'wash');
		$this->assertSameEquals(
			$expected,
			(api_targets_add_single($campaign, '0412345687', $targetKey) !== false)
		);
		$this->purge_all_campaigns();
	}
}
