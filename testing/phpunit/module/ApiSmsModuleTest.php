<?php
/**
 * ApiTagsTest
 * Module test for api_sms.php
 *
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use DateTime;
use testing\module\helpers\CampaignModuleHelper;
use testing\module\helpers\SmsDidModuleHelper;
use testing\module\helpers\SmsSupplierModuleHelper;
use testing\module\helpers\UserModuleHelper;

/**
 * Class ApiSmsModuleTest
 */
class ApiSmsModuleTest extends AbstractPhpunitModuleTest
{
	use SmsSupplierModuleHelper;
	use UserModuleHelper;
	use CampaignModuleHelper;

	/**
	 * @return void
	 */
	public function test_api_sms_get_received_sms() {
		$this->create_sms_received(
			(new DateTime("today 10:00:00"))->format("Y-m-d H:i:s"),
			(new DateTime("today 23:59:59"))->format("Y-m-d H:i:s"),
			123,
			"0411111111",
			"test response 1"
		);

		$this->create_sms_received(
			(new DateTime("last week"))->format("Y-m-d H:i:s"),
			(new DateTime("last week +2 hours"))->format("Y-m-d H:i:s"),
			123,
			"0411111112",
			"test response 2"
		);

		$this->create_sms_received(
			(new DateTime("today 00:00:00"))->format("Y-m-d H:i:s"),
			(new DateTime("today 23:59:59"))->format("Y-m-d H:i:s"),
			124,
			"0411111112",
			"test response 3"
		);

		$this->assertCount(1, api_sms_get_received_sms([123], new DateTime("today 00:00:00"), new DateTime("today 23:59:59")));
		$this->assertCount(2, api_sms_get_received_sms([123], new DateTime("last week 00:00:00"), new DateTime("today 23:59:59")));
		$this->assertCount(1, api_sms_get_received_sms([124], new DateTime("today 00:00:00"), new DateTime("today 23:59:59")));
		$this->assertCount(2, api_sms_get_received_sms([124, 123], new DateTime("today 00:00:00"), new DateTime("today 23:59:59")));

		$messages = api_sms_get_received_sms([123], new DateTime("today 00:00:00"), new DateTime("today 23:59:59"));
		$this->assertSameEquals(
			$messages,
			[
				[
					'smsid' => '1',
					'timestamp' => (new DateTime("today 10:00:00"))->format("Y-m-d H:i:s"),
					'received' => (new DateTime("today 23:59:59"))->format("Y-m-d H:i:s"),
					'sms_account' => '123',
					'from' => '0411111111',
					'contents' => 'test response 1'
				]
			]
		);
	}

	/**
	 * @return void
	 */
	public function test_api_sms_gethistory_includes_sms_received_history() {
		$did = $this->create_new_smsdid();
		$destination = '61412345678';
		$timestamp = date('Y-m-d H:i:s');

		$receivedData = [
			[
				'timestamp' => $timestamp,
				'received' => $timestamp,
				'sms_account' => $did,
				'from' => $destination,
				'contents' => 'Test content1'
			],
			[
				'timestamp' => $timestamp,
				'received' => $timestamp,
				'sms_account' => $did,
				'from' => $destination,
				'contents' => 'Test content2'
			]
		];

		foreach ($receivedData as $received) {
			$this->create_sms_received($received['timestamp'], $received['received'], $received['sms_account'], $received['from'], $received['contents']);
		}

		$messages = api_sms_gethistory($did, $destination, 1);

		$messageKey = strtotime($timestamp);
		$this->assertArrayHasKey($messageKey, $messages);

		$this->assertSameEquals(
			$messages[$messageKey],
			[
				[
					'direction' => 'received',
					'contents' => 'Test content1'
				],
				[
					'direction' => 'received',
					'contents' => 'Test content2'
				],
			]
		);
		$this->purge_all_sms_received();
	}
	
	/**
	 * @return void
	 */
	public function test_api_sms_gethistory_ignores_sms_received_history() {
		$did = $this->create_new_smsdid();
		$destination = '61412345678';
		$timestamp = date('Y-m-d H:i:s');

		$receivedData = [
			[
				'timestamp' => $timestamp,
				'received' => $timestamp,
				'sms_account' => $did,
				'from' => $destination,
				'contents' => 'Test content1'
			],
			[
				'timestamp' => $timestamp,
				'received' => $timestamp,
				'sms_account' => $did,
				'from' => $destination,
				'contents' => 'Test content2'
			]
		];

		foreach ($receivedData as $received) {
			$this->create_sms_received($received['timestamp'], $received['received'], $received['sms_account'], $received['from'], $received['contents']);
		}

		$sentData = [
			[
				'eventid' => rand(100000, 999999),
				'supplier' => 21,
				'supplieruid' => "01EKE755YZX3000ZA9HSTF1Y9G",
				'timestamp' => $timestamp,
				'sms_account' => $did,
				'to' => '61439417626',
				'contents' => "Test Sent content1"
			]
		];

		foreach ($sentData as $sent) {
			$this->create_sms_sent($sent['eventid'], $sent['supplier'], $sent['supplieruid'], $sent['timestamp'], $sent['sms_account'], $sent['to'], $sent['contents']);
		}

		$messages = api_sms_gethistory($did, '61439417626', 1, false);
		$messageKey = strtotime($timestamp);
		$this->assertArrayHasKey($messageKey, $messages);

		$this->assertSameEquals(
			$messages[$messageKey],
			[
				[
					'direction' => 'sent',
					'contents' => 'Test Sent content1',
					'userid' => null
				]
			]
		);
		$this->purge_all_sms_received();
		$this->purge_all_sms_sent();
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_sms_apisend_international_sms_data() {
		return [
			// AU number | no default region | testing all destination formats
			'AU number no country code & no default region' => [
				'0451000222', // destination
				false, // default user region
				'smsaumobile', // expected billing type
				19, // expected supplier id: CLX-Telstra SMPP with AU Capabilities
				'AU', // expected queued region format
				'61451000222', // expected formatted numbber in sms_sent
			],
			'AU number country code without plus sign & no default region' => [
				'61451000222',
				false,
				'smsaumobile',
				19,
				'AU',
				'61451000222',
			],
			'AU number country code with plus sign & no default region' => [
				'+61451000222',
				false,
				'smsaumobile',
				19,
				'AU',
				'61451000222',
			],

			// AU number | default region AU | testing all destination formats
			'AU number no country code & default region AU' => [
				'0451000222',
				'AU',
				'smsaumobile',
				19,
				'AU',
				'61451000222',
			],
			'AU number country code without plus sign & default region AU' => [
				'61451000222',
				'AU',
				'smsaumobile',
				19,
				'AU',
				'61451000222',
			],
			'AU number country code with plus sign & default region AU' => [
				'+61451000222',
				'AU',
				'smsaumobile',
				19,
				'AU',
				'61451000222',
			],

			// AU number | default region NZ | testing all destination formats
			'AU number no country code & default region NZ' => [
				'0451000222', // Should fail because try to format as NZ number
				'NZ',
				false,  // expected billing type = false means failure
				null,
				null,
				null,
			],
			'AU number country code without plus sign & default region NZ' => [
				'61451000222',
				'NZ',
				'smsaumobile',
				19,
				'AU',
				'61451000222',
			],
			'AU number country code with plus sign & default region NZ' => [
				'+61451000222',
				'NZ',
				'smsaumobile',
				19,
				'AU',
				'61451000222',
			],

			// NZ number | no default region | testing all destination formats
			'NZ number no country code & no default region' => [
				'0211000444',
				false,
				false, // expected billing type = false means failure
				null,
				null,
				null,
			],
			'NZ number country code without plus sign & no default region' => [
				'64211000444',
				false,
				'smsnzmobile',
				5,
				'NZ',
				'64211000444',
			],
			'NZ number country code with plus sign & no default region' => [
				'+64211000444',
				false,
				'smsnzmobile',
				5,
				'NZ',
				'64211000444',
			],

			// NZ number | default region AU | testing all destination formats
			'NZ number no country code & default region AU' => [
				'0211000444',
				'AU',
				false, // expected billing type = false means failure
				null,
				null,
				null,
			],
			'NZ number country code without plus sign & default region AU' => [
				'64211000444',
				'AU',
				'smsnzmobile',
				5,
				'NZ',
				'64211000444',
			],
			'NZ number country code with plus sign & default region AU' => [
				'+64211000444',
				'AU',
				'smsnzmobile',
				5,
				'NZ',
				'64211000444',
			],

			// NZ number | default region NZ | testing all destination formats
			'NZ number no country code & default region NZ' => [
				'0211000444',
				'NZ',
				'smsnzmobile',
				5,
				'NZ',
				'64211000444',
			],
			'NZ number country code without plus sign & default region NZ' => [
				'64211000444',
				'NZ',
				'smsnzmobile',
				5,
				'NZ',
				'64211000444',
			],
			'NZ number country code with plus sign & default region NZ' => [
				'+64211000444',
				'NZ',
				'smsnzmobile',
				5,
				'NZ',
				'64211000444',
			],

			// SG number | no default region | testing all destination formats
			'SG number no country code & no default region' => [
				'084398987',
				false,
				false, // expected billing type = false means failure
				null,
				null,
				null,
			],
			'SG number country code without plus sign & no default region' => [
				'6584398987',
				false,
				'smssgmobile',
				19,
				'SG',
				'6584398987',
			],
			'SG number country code with plus sign & no default region' => [
				'+6584398987',
				false,
				'smssgmobile',
				19,
				'SG',
				'6584398987',
			],

			// SG number | default region AU | testing all destination formats
			'SG number no country code & default region AU' => [
				'084398987',
				'AU',
				false, // expected billing type = false means failure
				null,
				null,
				null,
			],
			'SG number country code without plus sign & default region AU' => [
				'6584398987',
				'AU',
				'smssgmobile',
				19,
				'SG',
				'6584398987',
			],
			'SG number country code with plus sign & default region AU' => [
				'+6584398987',
				'AU',
				'smssgmobile',
				19,
				'SG',
				'6584398987',
			],

			// SG number | default region NZ | testing all destination formats
			'SG number no country code & default region NZ' => [
				'084398987',
				'NZ',
				false, // expected billing type = false means failure
				null,
				null,
				null,
			],
			'SG number country code without plus sign & default region NZ' => [
				'6584398987',
				'NZ',
				'smssgmobile',
				19,
				'SG',
				'6584398987',
			],
			'SG number country code with plus sign & default region NZ' => [
				'+6584398987',
				'NZ',
				'smssgmobile',
				19,
				'SG',
				'6584398987',
			],

			// GB number | no default region | testing all destination formats
			'GB number no country code & no default region' => [
				'07312123456',
				false,
				false, // expected billing type = false means failure
				null,
				null,
				null,
			],
			'GB number country code without plus sign & no default region' => [
				'447312123456',
				false,
				'smsgbmobile',
				19,
				'GB',
				'447312123456',
			],
			'GB number country code with plus sign & no default region' => [
				'+447312123456',
				false,
				'smsgbmobile',
				19,
				'GB',
				'447312123456',
			],

			// GB number | default region AU | testing all destination formats
			'GB number no country code & default region AU' => [
				'07312123456',
				'AU',
				false, // expected billing type = false means failure
				null,
				null,
				null,
			],
			'GB number country code without plus sign & default region AU' => [
				'447312123456',
				'AU',
				'smsgbmobile',
				19,
				'GB',
				'447312123456',
			],
			'GB number country code with plus sign & default region AU' => [
				'+447312123456',
				'AU',
				'smsgbmobile',
				19,
				'GB',
				'447312123456',
			],

			// GB number | default region NZ | testing all destination formats
			'GB number no country code & default region NZ' => [
				'07312123456',
				'NZ',
				false, // expected billing type = false means failure
				null,
				null,
				null,
			],
			'GB number country code without plus sign & default region NZ' => [
				'447312123456',
				'NZ',
				'smsgbmobile',
				19,
				'GB',
				'447312123456',
			],
			'GB number country code with plus sign & default region NZ' => [
				'+447312123456',
				'NZ',
				'smsgbmobile',
				19,
				'GB',
				'447312123456',
			],

			// even though we support PH, treated as CAMPAIGN_SMS_REGION_INTERNATIONAL
			// PH number | no default region | testing all destination formats
			'PH number no country code & no default region' => [
				'09668899900',
				false,
				false, // expected billing type = false means failure
				null,
				null,
				null,
			],
			'PH number country code without plus sign & no default region' => [
				'639668899900',
				false,
				'smsphmobile',
				19,
				CAMPAIGN_SMS_REGION_INTERNATIONAL,
				'639668899900',
			],
			'PH number country code with plus sign & no default region' => [
				'+639668899900',
				false,
				'smsphmobile',
				19,
				CAMPAIGN_SMS_REGION_INTERNATIONAL,
				'639668899900',
			],

			// PH number | default region AU | testing all destination formats
			'PH number no country code & default region AU' => [
				'09668899900',
				'AU',
				false, // expected billing type = false means failure
				null,
				null,
				null,
			],
			'PH number country code without plus sign & default region AU' => [
				'639668899900',
				'AU',
				'smsphmobile',
				19,
				CAMPAIGN_SMS_REGION_INTERNATIONAL,
				'639668899900',
			],
			'PH number country code with plus sign & default region AU' => [
				'+639668899900',
				'AU',
				'smsphmobile',
				19,
				CAMPAIGN_SMS_REGION_INTERNATIONAL,
				'639668899900',
			],

			// PH number | default region NZ | testing all destination formats
			'PH number no country code & default region NZ' => [
				'09668899900',
				'NZ',
				false, // expected billing type = false means failure
				null,
				null,
				null,
			],
			'PH number country code without plus sign & default region NZ' => [
				'639668899900',
				'NZ',
				'smsphmobile',
				19,
				CAMPAIGN_SMS_REGION_INTERNATIONAL,
				'639668899900',
			],
			'PH number country code with plus sign & default region NZ' => [
				'+639668899900',
				'NZ',
				'smsphmobile',
				19,
				CAMPAIGN_SMS_REGION_INTERNATIONAL,
				'639668899900',
			],

			// NL (netherlands) number | no default region | testing all destination formats
			'NL number country code without plus sign & no default region' => [
				'31622140000',
				false,
				'smsothermobile',
				17,
				CAMPAIGN_SMS_REGION_INTERNATIONAL,
				'31622140000',
			],
			'NL number country code with plus sign & default region AU' => [
				'+31622140000',
				'AU',
				'smsothermobile',
				17,
				CAMPAIGN_SMS_REGION_INTERNATIONAL,
				'31622140000',
			],
			'NL number country code with plus sign & default region NZ' => [
				'+31622140000',
				'NZ',
				'smsothermobile',
				17,
				CAMPAIGN_SMS_REGION_INTERNATIONAL,
				'31622140000',
			],
		];
	}

	/**
	 * @group api_sms_apisend
	 * @dataProvider api_sms_apisend_international_sms_data
	 * @param mixed $number
	 * @param mixed $userDefaultRegion
	 * @param mixed $expectedBillingType
	 * @param mixed $expectedSupplierId
	 * @param mixed $expectedRegion
	 * @param mixed $expectedDestination
	 * @return void
	 */
	public function test_api_sms_apisend_international_sms($number, $userDefaultRegion, $expectedBillingType, $expectedSupplierId, $expectedRegion, $expectedDestination) {
		$messageContent = uniqid(' api_sms_apisend international sms test ');

		// create new sms did
		$didId = $this->create_new_smsdid();

		// create a new user with sms did attached
		$userid = $this->create_new_user();
		$this->assertTrue(
			api_users_setting_set($userid, 'smsapidid', $didId)
		);
		if ($userDefaultRegion) {
			$this->assertTrue(
				api_users_setting_set($userid, 'region', $userDefaultRegion)
			);
		}

		// we need a GearmanClient class
		$this->includeFakeGearman();

		switch ((int) $expectedSupplierId) {
			// Buletin API mock API
			case 5:
				$this->mock_function_value('curl_exec', true);
				$this->mock_function_value('curl_getinfo', ["http_code" => 204]);
				break;
		}

		// send api sms
		$eventId = api_sms_apisend(
			$number,
			$messageContent,
			$userid,
			null,
			null,
			true
		);
		// assert failed
		if ($expectedBillingType == false) {
			$this->assertFalse($eventId);
			return;
		}

		$this->assertGreaterThan(0, $eventId, 'eventId returned by api_sms_apisend()');

		// find latest job that was queued from api_sms_apisend()
		$sql = "SELECT * FROM `event_queue` ORDER BY `eventid` DESC LIMIT 1;";
		$rs2 = api_db_query_read($sql);
		$job = $rs2->GetArray();

		// assert latest job the correct one
		$this->assertCount(1, $job);
		$job = $job[0];
		$this->assertEquals('sms', $job['queue'], 'Queue name');
		$details = unserialize($job['details']);
		$this->assertSameEquals($didId, (int) $details['didid'], 'Queue didid');
		$this->assertSameEquals($expectedRegion, $details['region'], 'Queue region');
		$this->assertSameEquals($messageContent, $details['message'], 'Queue message');

		// trigger Gearman to send sms
		$this->assertTrue(api_queue_process_sms($details), 'api_queue_process_sms()');
		$this->remove_mocked_functions();

		// check sms type billing type
		$sql = "SELECT s.supplier, s.supplieruid, s.to, s.contents, sm.billingtype, sm.userid
				FROM `sms_sent` s
				JOIN `sms_api_mapping` sm ON (s.eventid = sm.rid)
				WHERE s.eventid = ?;";
		$rs = api_db_query_read($sql, [$details['eventid']]);
		$this->assertCount(1, $data = $rs->GetArray());
		$data = $data[0];
		$this->assertEquals($expectedSupplierId, $data['supplier'], 'sms_sent supplier');
		$this->assertEquals($expectedDestination, $data['to'], 'sms_sent to');
		$this->assertEquals($messageContent, $data['contents'], 'sms_sent contents');
		$this->assertEquals($expectedBillingType, $data['billingtype'], 'sms_api_mapping billingtype');
		$this->assertEquals($userid, $data['userid'], 'sms_api_mapping userid');

		// no errors
		$errors = api_error_printiferror(['return' => true]);
		$this->assertFalse($errors, "Errors: {$errors}");
	}

	/**
	 * @group api_sms_apisend
	 * @return void
	 */
	public function test_api_queue_sms_abandons_target_on_carrier_error() {
		$campaignid = $this->create_new_campaign(null, 'sms');
		$destination = '61412345678';
		$targetid = api_targets_add_single($campaignid, $destination);
		$target = api_targets_getinfo($targetid);
		$eventid = api_misc_uniqueid();
		$this->mock_function_param('api_sms_send', false);
		$details = [
			'region' => 'AU',
			'destination' => $destination,
			'eventid' => $eventid,
			'didid' => 123,
			'message' => 'Test sms',
			'campaignid' => $campaignid,
			'targetid' => $targetid,
			'targetkey' => $target['targetkey']
		];

		$this->assertSameEquals('READY', $target['status']);
		$this->assertFalse(api_queue_process_sms($details, ['errors' => EVENTQUEUE_MAXERROR]));
		$target = api_targets_getinfo($targetid);
		$this->assertSameEquals('ABANDONED', $target['status']);
		$response = api_data_responses_getall($targetid, $eventid);
		$this->assertArraySubset(['reason' => 'CARRIER ERROR'], $response);
		$this->remove_mocked_functions('api_sms_send');
		$this->purge_all_campaigns();
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_sms_send_error_message_data() {
		return [
			'No suppliers' => [
				"Sorry, no upstream SMS suppliers found for '31622000111'; country=Netherlands; ",
				'31622000111',
				[] // no capabilities
			],
			'No suppliers - destination plus sign' => [
				"Sorry, no upstream SMS suppliers found for '31622000111'; country=Netherlands; ",
				'+31622000111',
				[] // no capabilities
			],
			'No suppliers and no available capabilities' => [
				"Sorry, no upstream SMS suppliers found for '31622000222'; country=Netherlands; ",
				'31622000222',
				['aumobile', 'nzmobile'] // no othermobile capabilities
			],
			'No suppliers and no available capabilities - destination plus sign' => [
				"Sorry, no upstream SMS suppliers found for '31622000222'; country=Netherlands; ",
				'+31622000222',
				['aumobile', 'nzmobile'] // no othermobile capabilities
			],
			'supplier 17 failed to send SMS' => [
				"Sorry, could not send SMS to '31622000333' with selected providers: 17; country=Netherlands;",
				'31622000333',
				['othermobile']
			],
			'supplier 17 failed to send SMS - destination plus sign' => [
				"Sorry, could not send SMS to '31622000333' with selected providers: 17; country=Netherlands;",
				'+31622000333',
				['othermobile']
			],
		];
	}

	/**
	 * @group api_sms_send
	 * @dataProvider api_sms_send_error_message_data
	 * @param string $expectedError
	 * @param string $number
	 * @param array  $capabilities
	 * @return void
	 */
	public function test_api_sms_send_error_message($expectedError, $number, array $capabilities) {
		// we need a GearmanClient class
		$this->includeFakeGearman();

		// create new sms did
		$didId = $this->create_new_smsdid();
		// disable other mobile for CLX
		$capabilitiesBackup = api_sms_supplier_setting_getsingle(17, 'capabilities');
		$this->assertTrue(
			api_sms_supplier_setting_set(17, 'capabilities', serialize($capabilities)),
			'capabilities CLX sms supplier (17)'
		);

		// mock CLX function to fail
		$filename = APP_ROOT_PATH . "/lib/SMS/supplier_17.php";
		$this->assertFileExists($filename);
		include_once($filename);
		$this->mock_function_value('api_sms_send_supplier_17', false);

		// test no suppliers found
		$formatted = api_data_numberformat($number, CAMPAIGN_SMS_REGION_INTERNATIONAL);
		$this->assertInternalType('array', $formatted, 'format number');
		$this->assertFalse(
			api_sms_send($didId, $formatted, __FUNCTION__),
			'api_sms_send return value'
		);
		// removed mocks
		$this->remove_mocked_functions();

		// re-enable CLX
		$this->assertTrue(
			api_sms_supplier_setting_set(17, 'capabilities', $capabilitiesBackup),
			'reset capabilities CLX sms supplier (17)'
		);

		$errors = (string) api_error_printiferror(['return' => true]);
		$this->assertContains(
			$expectedError,
			$errors
		);
		$this->assertContains("; did={$didId}", $errors);
	}

	// Has campaign no rest
	// Has campaign with rest
	// Has campaign old api
	// No campaign no rest
	// no campaign with rest
	/**
	 * @return void
	 */
	public function test_api_sms_last_sent_has_campaign_with_none() {
		// create new sms did
		$didId = $this->create_new_smsdid();
		$source = $this->create_new_campaign(null, 'sms');
		$this->assertFalse(api_sms_last_sent_has_campaign($didId, "0411111111"));
	}

	/**
	 * @return void
	 */
	public function test_api_sms_last_sent_has_campaign_no_rest() {
		// create new sms did
		$didId = $this->create_new_smsdid();

		$source = $this->create_new_campaign(null, 'sms');
		$target = api_targets_add_single($source, "0411111111");
		$eventid = api_sms_send_log(5000, rand(1000, 2000), $didId, "0411111111", "test 1");
		api_data_callresult_add($source, $eventid, $target, "SENT");

		$this->assertTrue(api_sms_last_sent_has_campaign($didId, "0411111111"));
	}

	/**
	 * @return void
	 */
	public function test_api_sms_last_sent_has_campaign_with_older_api() {
		// create new sms did
		$didId = $this->create_new_smsdid();
		$endUserNumber = "0411111112";

		$source = $this->create_new_campaign(null, 'sms');
		$target = api_targets_add_single($source, $endUserNumber);

		$eventid1 = api_sms_send_log(5000, rand(1000, 2000), $didId, $endUserNumber, "test 1");
		api_data_callresult_add($source, $eventid1, $target, "SENT");

		$this->assertTrue(api_sms_last_sent_has_campaign($didId, $endUserNumber));

		$eventid = api_sms_send_log(5000, rand(100, 300), $didId, $endUserNumber, "test");
		$sql = "INSERT INTO `sms_api_mapping` (`userid`, `billingtype`, `rid`, `uid`, `messageunits`, `billing_products_region_id`) VALUES (?, ?, ?, ?, ?, ?)";
		api_db_query_write($sql, array(1, "sms", $eventid, 5000, 3, 2));

		$this->assertTrue(api_sms_last_sent_has_campaign($didId, $endUserNumber));
	}

	/**
	 * @return void
	 */
	public function test_api_sms_last_sent_has_campaign_with_newer_old_api() {
		// create new sms did
		$didId = $this->create_new_smsdid();
		$endUserNumber = "0411111112";

		$source = $this->create_new_campaign(null, 'sms');
		$target = api_targets_add_single($source, $endUserNumber);

		$eventid1 = api_sms_send_log(5000, rand(1000, 2000), $didId, $endUserNumber, "test 1");
		api_data_callresult_add($source, $eventid1, $target, "SENT");

		$this->assertTrue(api_sms_last_sent_has_campaign($didId, $endUserNumber));

		$eventid = api_sms_send_log(5000, rand(3000, 4000), $didId, $endUserNumber, "test");
		$sql = "INSERT INTO `sms_api_mapping` (`userid`, `billingtype`, `rid`, `uid`, `messageunits`, `billing_products_region_id`) VALUES (?, ?, ?, ?, ?, ?)";
		api_db_query_write($sql, array(1, "sms", $eventid, 5000, 3, 2));

		$this->assertFalse(api_sms_last_sent_has_campaign($didId, $endUserNumber));
	}

	/**
	 * @return void
	 */
	public function test_api_sms_last_sent_has_campaign_with_no_campaign_newer_rest() {
		// create new sms did
		$didId = $this->create_new_smsdid();
		$from = api_sms_dids_setting_getsingle($didId, "name");
		$number = "0411111111";

		$this->create_sms_out_sms(1, new DateTime("now +1 day"), $from, $number, "test rest api");
		$this->assertFalse(api_sms_last_sent_has_campaign($didId, "0411111111"));
	}

	/**
	 * @return void
	 */
	public function test_api_sms_last_sent_has_campaign_with_newer_rest() {
		// create new sms did
		$didId = $this->create_new_smsdid();
		$from = api_sms_dids_setting_getsingle($didId, "name");
		$number = "0411111111";

		$source = $this->create_new_campaign(null, 'sms');
		$target = api_targets_add_single($source, $number);
		$eventid = api_sms_send_log(5000, rand(1000, 2000), $didId, $number, "test 1");
		api_data_callresult_add($source, $eventid, $target, "SENT");
		$this->create_sms_out_sms(1, new DateTime("now +1 day"), $from, $number, "test rest api");
		$this->assertFalse(api_sms_last_sent_has_campaign($didId, "0411111111"));
	}

	/**
	 * @param string  $timestamp
	 * @param string  $received
	 * @param integer $sms_account
	 * @param string  $e614
	 * @param string  $contents
	 * @return integer
	 */
	private function create_sms_received($timestamp, $received, $sms_account, $e614, $contents) {
		$sql = "INSERT INTO `sms_received` (`timestamp`, `received`, `sms_account`, `from`, `contents`) VALUES (?, ?, ?, ?, ?)";
		api_db_query_write($sql, array($timestamp, $received, $sms_account, $e614, $contents));
		return api_db_lastid();
	}

	/**
	 * @return boolean
	 */
	private function purge_all_sms_received() {
		$sql = "DELETE FROM sms_received";
		return api_db_query_write($sql);
	}

	/**
	 * @param integer $eventid
	 * @param integer $supplier
	 * @param string  $supplieruid
	 * @param string  $timestamp
	 * @param integer $sms_account
	 * @param string  $to
	 * @param string  $contents
	 * @return integer
	 */
	private function create_sms_sent($eventid, $supplier, $supplieruid, $timestamp, $sms_account, $to, $contents) {
		$sql = "INSERT INTO `sms_sent` (`eventid`, `supplier`, `supplieruid`, `timestamp`, `sms_account`, `to`, `contents`) VALUES (?, ?, ?, ?, ?, ?, ?)";
		api_db_query_write($sql, array($eventid, $supplier, $supplieruid, $timestamp, $sms_account, $to, $contents));
		return api_db_lastid();
	}

	/**
	 * @return boolean
	 */
	private function purge_all_sms_sent() {
		$sql = "DELETE FROM sms_sent";
		return api_db_query_write($sql);
	}
	
	/**
	 * @param integer  $user_id
	 * @param DateTime $timestamp
	 * @param string   $from
	 * @param string   $to
	 * @param string   $message
	 * @return boolean
	 */
	private function create_sms_out_sms($user_id, DateTime $timestamp, $from, $to, $message) {
		$sql = "INSERT INTO `sms_out` (
					`userid`,
					`timestamp`,
					`billingtype`,
					`supplier`,
					`supplierid`,
					`from`,
					`destination`,
					`message`
				)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?);";
		$rs = api_db_query_write(
			$sql,
			[
			$user_id,
			$timestamp->format("Y-m-d H:i:s"),
			'smsaumobile', // billingtype
			rand(1, 127), // supplier
			($supplierId = 2), // supplierid
			$from, // from
			$to, // destination
			$message // content
			]
		);
		return api_db_lastid();
	}

	/**
	 * @return void
	 */
	public function testSms2EmailExclusionHasExclusionFilters() {
		$sms_id = $this->create_new_smsdid();
		$content = 'A random text';
		$this->assertFalse(api_sms_sm2email_message_has_exclusion_filters($sms_id, $content));

		$filters = 'asdfsd|exclusion|dfdggffhg';
		api_sms_dids_setting_set($sms_id, 'sms2emailexclusionfilters', $filters);
		$this->assertFalse(api_sms_sm2email_message_has_exclusion_filters($sms_id, $content));

		$content = 'This is to test exclusion filters';
		$this->assertTrue(api_sms_sm2email_message_has_exclusion_filters($sms_id, $content));
	}

	/**
	 * @return void
	 */
	public function testApiSmsHandleDncOptInOut() {
		$listname = uniqid('list_');
		$groupowner = $this->get_default_group_id();
		$list = api_restrictions_donotcontact_addlist($listname, $groupowner);
		$this->assertNotFalse($list);
		$destination = '+61423456780';
		$this->assertTrue(api_sms_handle_dnc_opt_in_out($list, 'opt out', $destination));
		$this->assertNotFalse(api_restrictions_donotcontact_check_single('phone', $destination, $list));

		$this->assertTrue(api_sms_handle_dnc_opt_in_out($list, 'opt in', $destination));
		$this->assertFalse(api_restrictions_donotcontact_check_single('phone', $destination, $list));
		$this->assertTrue(api_restrictions_donotcontact_remove_list($list));
	}
}
