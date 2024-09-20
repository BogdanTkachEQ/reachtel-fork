<?php
/**
 * ApiConferencesModuleTest
 * Module test for api_campaigns.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use DateTime;
use Doctrine\ORM\EntityManager;
use Models\Entities\QueueItem;
use Services\Container\ContainerAccessor;
use Services\Utils\CsvFunctions;
use Services\Utils\XlsFunctions;
use testing\module\helpers\CampaignModuleHelper;
use testing\module\helpers\UserModuleHelper;

/**
 * Api Queur Gearman Module Test
 */
class ApiQueueModuleTest extends AbstractPhpunitModuleTest
{
	use CampaignModuleHelper;
	use UserModuleHelper;

	/**
	 * @group api_queue_process_disable_all_users_from_group
	 * @return void
	 */
	public function test_api_queue_process_disable_all_users_from_group() {

		// create a new group
		$groupid = $this->create_new_group();

		// create diffrent users with random status in this group
		$userMap = [];
		for ($x = 0; $x <= rand(5, 10); $x++) {
			$userid = $this->create_new_user(null, $groupid);
			// set a random status
			$status = array_rand(array_flip(['-5','-4','-3','-2','-1','disabled','0', '1']));
			api_users_setting_set($userid, "status", $status);
			$userMap[$userid] = $status;
		}

		$this->assertTrue(
			api_queue_process_disable_all_users_from_group(['groupid' => $groupid]),
			'api_queue_process_disable_all_users_from_group success expected'
		);

		// check each user is disabled
		foreach ($userMap as $userid => $status) {
			$this->assertEquals(
				-5, // USER_STATUS_CLOSED
				api_users_setting_getsingle($userid, "status"),
				"User id {$userid} status = USER_STATUS_CLOSED"
			);
		}
	}

	/**
	 * @group api_queue_process_delete_all_records_from_group
	 * @return void
	 */
	public function test_api_queue_process_delete_all_records_from_group() {

		// create a new group
		$groupid = $this->create_new_group();

		// create a new campaign
		$campaignId = $this->create_new_campaign(null, 'sms');
		api_campaigns_setting_set($campaignId, "groupowner", $groupid);
		$this->add_campaign_targets($campaignId, null);
		$targets = api_campaigns_get_all_targets($campaignId);
		$this->run_sms_campaign($campaignId, [$targets[0]['targetid']]);

		$sms_sent_before_delete = api_sms_find_sms_sent_by_targetid($targets[0]['targetid']);
		$this->assertEquals(7, count($sms_sent_before_delete));

		api_queue_process_delete_all_records_from_group(['groupid' => $groupid]);

		$sms_sent_after_delete = api_sms_find_sms_sent_by_targetid($targets[0]['targetid']);
		$this->assertEquals(0, count($sms_sent_after_delete));
		
		$this->purge_all_campaigns();
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_queue_is_extract_plotter_data_ready_data() {
		return [
			// plotterimport null
			'extract is ready' => [true, null],
			// plotterimport started empty
			'plotterimport is false' => [true, null, false],
			'plotterimport is null' => [true, null, null],
			'plotterimport is empty' => [true, null, ''],
			// plotterimport started is set
			'plotterimport started 1 hour ago' => [false, 60, '1 hour ago'],
			'plotterimport started 1 hour ago & not queued' => [false, 60, '1 hour ago', false],
			// loop detected so is ready
			'plotterimport started loop detected extract failed' => [true, null, '3 hours ago'],
			'plotterimport started loop detected extract failed & not queue' => [true, null, '3 hours ago', false],
			'plotterimport started loop detected extract success' => [true, null, '3 days ago'],
			'plotterimport started loop detected extract success & not queue' => [true, null, '3 days ago', false],
		];
	}

	/**
	 * @group _api_queue_is_extract_plotter_data_ready
	 * @dataProvider api_queue_is_extract_plotter_data_ready_data
	 * @param boolean $success
	 * @param mixed   $expectedDiff
	 * @param mixed   $plotterimport
	 * @param boolean $isQueued
	 * @return void
	 */
	public function test_api_queue_is_extract_plotter_data_ready($success, $expectedDiff, $plotterimport = null, $isQueued = true) {
		if ($isQueued) {
			$eventId = api_queue_add('kml_export', []);
		} else {
			// Get next id from Auto_increment (Auto_increment not always +1)
			$sql = "SHOW TABLE STATUS
					FROM `" . DB_MYSQL_DATABASE . "`
					WHERE `Name` = 'event_queue';";
			$rs = api_db_query_read($sql);
			$eventId = (int) $rs->GetArray()[0]['Auto_increment'];
		}

		$campaignId = $this->create_new_campaign();

		if ($plotterimport) {
			$this->assertTrue(
				api_campaigns_setting_set($campaignId, CAMPAIGN_SETTING_PLOTTER_IMPORT, strtotime($plotterimport))
			);
		}

		// prepare details
		$details = [
			'queue' => 'kml_export',
			'details' => ['whatever'],
		];
		if ($isQueued) {
			$details['eventid'] = $eventId;
		}

		$this->assertEquals(
			$success,
			_api_queue_is_extract_plotter_data_ready(
				$details,
				$campaignId,
				'TYPE'
			)
		);

		// check diff time in seconds
		$sql = "SELECT `notbefore` FROM `event_queue` WHERE `eventid` = ?;";
		$rs = api_db_query_read($sql, [$eventId]);

		if ($expectedDiff) {
			$this->assertNotNull($rs->Fields("notbefore"), 'event_queue notbefore was not set properly');
			$this->assertTrue(
				// handle slow test with relative date issues
				in_array(
					strtotime($rs->Fields("notbefore")) - strtotime('now'),
					[$expectedDiff - 1, $expectedDiff] // e.g. if 60 is expected, we safely check 59 or 60.
				)
			);
		} else {
			$this->assertNull($rs->Fields("notbefore"));
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_queue_extract_plotter_data_data() {
		return [
			'job success' => [true],
			'job failed' => [false, false],
		];
	}

	/**
	 * @group _api_queue_extract_plotter_data
	 * @dataProvider api_queue_extract_plotter_data_data
	 * @param boolean $expected
	 * @param boolean $success
	 * @return void
	 */
	public function test_api_queue_extract_plotter_data($expected, $success = true) {
		$campaignId = $this->create_new_campaign();

		$plotter = $this
			->getMockBuilder(\Services\Plotter\PlotterDataExtractionContext::class)
			->disableOriginalConstructor()
			->getMock();
		$plotter
			->method('extractAndUpdateCampaign')
			->willReturn($success);

		$this->assertEquals(
			$expected,
			_api_queue_extract_plotter_data(
				$plotter,
				[
					'queue' => 'queue',
					'details' => 'details',
				],
				$campaignId,
				['errors' => 0],
				'type'
			)
		);
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
				['', '0412345659', 'merge_data1-2', 'merge_data2-2', ''],
			]]
		];
	}

	/**
	 * @dataProvider fileupload_provider
	 * @param string $filename
	 * @param array  $data
	 * @return void
	 */
	public function test_api_queue_process_fileupload($filename, array $data) {
		$campaignId = $this->create_new_campaign(null, "phone");
		$this->mock_function_value('api_queue_gearman_add', true);
		$filename = $filename . ".csv";
		$filepath = "/tmp/$filename";
		CsvFunctions::arrayToCsv($data, $filepath);
		$queueItem = api_targets_queued_fileupload($campaignId, $filepath, $filename, 1);

		$this->assertTrue(api_queue_process_fileupload(["queue_id" => $queueItem->getId()], []));
		$this->assertTrue($queueItem->isHasRun());
		$this->assertEquals('{"rows":4,"good":3,"bad":0,"pci":0,"defaulttargetkey":true}', $queueItem->getReturnText());
		if (file_exists($filepath)) {
			unlink($filepath);
		}
	}

	/**
	 * @dataProvider fileupload_provider
	 * @param string $filename
	 * @param array  $data
	 * @return void
	 */
	public function test_api_queue_process_fileupload_next_attempt($filename, array $data) {
		$campaignId = $this->create_new_campaign(null, "phone");
		$this->mock_function_value('api_queue_gearman_add', true);
		$filename = $filename . ".csv";
		$filepath = "/tmp/$filename";
		CsvFunctions::arrayToCsv($data, $filepath);
		$queueItem1 = api_targets_queued_fileupload($campaignId, $filepath, $filename, 1);
		CsvFunctions::arrayToCsv($data, $filepath);

		$queueItem = api_targets_queued_fileupload($campaignId, $filepath, $filename, 1);

		$queueItem1->setIsRunning(true);
		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$em->persist($queueItem1);
		$em->flush();

		$this->assertTrue(api_queue_process_fileupload(["queue_id" => $queueItem->getId()], []));
		$this->assertFalse($queueItem->isHasRun());
		$this->assertContains("Trying again", $queueItem->getData());

		if (file_exists($filepath)) {
			unlink($filepath);
		}
	}

	/**
	 * @dataProvider fileupload_provider
	 * @param string $filename
	 * @param array  $data
	 * @return void
	 */
	public function test_api_queue_process_fileupload_xls($filename, array $data) {
		$campaignId = $this->create_new_campaign(null, "phone");
		$this->mock_function_value('api_queue_gearman_add', true);
		$filename = $filename . ".xls";
		$filepath = "/tmp/$filename";
		XlsFunctions::arrayToXls($data, $filepath);
		$testData = XlsFunctions::xlsToArray($filepath);
		$queueItem = api_targets_queued_fileupload($campaignId, $filepath, $filename, 1);

		$this->assertTrue(api_queue_process_fileupload(["queue_id" => $queueItem->getId()], []));
		$this->assertTrue($queueItem->isHasRun());
		$this->assertEquals('{"rows":4,"good":3,"bad":0,"pci":0,"defaulttargetkey":true}', $queueItem->getReturnText());
		if (file_exists($filepath)) {
			unlink($filepath);
		}
	}

	/**
	 * @return void
	 */
	public function test_api_queue_process_fileupload_no_files() {
		$campaignId = $this->create_new_campaign();
		$this->mock_function_value('api_queue_gearman_add', true);
		$filename = "test999.csv";
		$filepath = "/tmp/$filename";
		CsvFunctions::arrayToCsv(["123"], $filepath);
		$queueItem = api_targets_queued_fileupload($campaignId, $filepath, $filename, 1);
		$queueItem->getQueueFiles()->clear();
		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$em->persist($queueItem);
		$em->flush();
		$process = api_queue_process_fileupload(["queue_id" => $queueItem->getId()], []);
		$this->assertTrue($process);
		$errors = api_error_geterrors();
		$this->assertNotEmpty($errors);
		$this->assertContains("FILE_UPLOAD_FAILURE: No files to process for file for queue id", $errors[0]);
		if (file_exists($filepath)) {
			unlink($filepath);
		}
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		$this->remove_mocked_functions();
		$em = ContainerAccessor::getContainer()->get(EntityManager::class);
		$em->getRepository(QueueItem::class)->deleteBetweenDates(new DateTime("1900-01-01"), new DateTime());
	}
}
