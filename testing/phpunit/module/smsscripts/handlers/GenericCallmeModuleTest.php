<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\smsscripts\handlers;

use testing\module\AbstractPhpunitModuleTest;
use testing\module\helpers\CampaignModuleHelper;
use testing\module\helpers\UserModuleHelper;

/**
 * Class GenericCallmeModuleTest
 */
class GenericCallmeModuleTest extends AbstractPhpunitModuleTest
{
	use CampaignModuleHelper;
	use UserModuleHelper;

	/**
	 * @return void
	 */
	public function testGenericCallmeTestInboundCallme() {
		$did = $this->create_new_smsdid();
		$fromDestination = '61432165498';
		$callmedest = '0412457896';
		$sourceCampaignName = 'source-campaign';
		$targetKey = 'test-target-key';
		$mergeDataCampaign = 'merge-data-campaign';
		$this->purge_all_campaigns();
		// faking SESSION with no user security groupaccess
		$_SESSION['userid'] = 2;
		$sourceCampaignId = api_campaigns_add($sourceCampaignName, CAMPAIGN_TYPE_SMS);
		api_campaigns_setting_set($sourceCampaignId, 'callmedestination', $callmedest);
		$targetid = api_targets_add_single($sourceCampaignId, '61478546986', $targetKey, null, ['campaignname' => $mergeDataCampaign]);
		$this->assertNotFalse($targetid);
		$duplicateCampaignid = api_campaigns_add('duplicate-campaign-test', CAMPAIGN_TYPE_VOICE);
		$tags = [
			'call-me-campaign-prefix' => 'Reachtel-test-call-me-',
			'call-me-campaign-duplicate-id' => $duplicateCampaignid,
			'callme-openhour' => '01:00:00',
			'callme-closehour' => '23:00:00'
		];
		api_tags_set(KEY_STORE_TYPE_SMS_DIDS, $did, $tags);

		$message = [
			'sms_account' => $did,
			'target' => api_targets_getinfo($targetid),
			'contents' => 'Call me',
			'e164' => $fromDestination
		];

		require_once(APP_ROOT_PATH . '/lib/smsscripts/handlers/generic_callme.php');

		handle_generic_callme($message);
		$expectedCampaign = 'Reachtel-test-call-me-' . date("FY");

		$expectedCampaignId = api_campaigns_checknameexists($expectedCampaign);
		$this->assertNotFalse(api_campaigns_checknameexists($expectedCampaign));
		$sourceCampaignResponses = api_data_responses_getall($targetid);
		$this->assertArraySubset(
			[
				'CALLME' => 'yes',
				'CALLME_RESPONSE' => 'Call me'
			],
			$sourceCampaignResponses
		);

		$this->arrayHasKey('CALLME_RESPONSE_TIMESTAMP', $sourceCampaignResponses);

		$targets = api_targets_listall($expectedCampaignId);

		$callmeTargetId = array_keys($targets)[0];
		$this->assertEquals($callmedest, $targets[$callmeTargetId]);

		$callMeResponses = api_data_responses_getall($callmeTargetId);
		$this->assertArraySubset(['sourcecampaign' => $sourceCampaignName, 'source' => 'sms'], $callMeResponses);

		$mergeData = api_data_merge_get_all($expectedCampaignId, api_targets_getinfo($callmeTargetId)['targetkey']);
		$this->assertArraySubset(['campaignname' => $mergeDataCampaign, 'customernumber' => $fromDestination, 'customerrefnum' => $targetKey], $mergeData);
		$this->assertArrayHasKey('date', $mergeData);
	}

	/**
	 * @return void
	 */
	public function testGenericCallMeRespectsDayNumberTags() {
		$did = $this->create_new_smsdid();
		$fromDestination = '61432165498';
		$callmedest = '0412457896';
		$sourceCampaignName = 'source-campaign';
		$targetKey = 'test-target-key';
		$mergeDataCampaign = 'merge-data-campaign';
		$this->purge_all_campaigns();
		// faking SESSION with no user security groupaccess
		$_SESSION['userid'] = 2;
		$sourceCampaignId = api_campaigns_add($sourceCampaignName, CAMPAIGN_TYPE_SMS);
		api_campaigns_setting_set($sourceCampaignId, 'callmedestination', $callmedest);
		$targetid = api_targets_add_single($sourceCampaignId, '61478546986', $targetKey, null, ['campaignname' => $mergeDataCampaign]);
		$this->assertNotFalse($targetid);
		$duplicateCampaignid = api_campaigns_add('duplicate-campaign-test', CAMPAIGN_TYPE_VOICE);
		$userid = $this->create_new_user();
		$this->assertNotFalse($userid);
		api_users_setting_set($userid, 'smsapidid', $did);
		$messageContent = 'Test after hours message for generic call me';
		$tags = [
			'call-me-campaign-prefix' => 'Reachtel-test-call-me-',
			'call-me-campaign-duplicate-id' => $duplicateCampaignid,
			'callme-openhour' => '00:00:00',
			'callme-closehour' => '23:00:00',
			'callme-openhour-' . date('N') => '00:00:00',
			'callme-closehour-' . date('N') => '00:00:00',
			'callme-afterhours-message' => $messageContent,
			'callme-afterhours-apiaccount' => $userid
		];
		api_tags_set(KEY_STORE_TYPE_SMS_DIDS, $did, $tags);

		$message = [
			'sms_account' => $did,
			'target' => api_targets_getinfo($targetid),
			'contents' => 'Call me',
			'e164' => $fromDestination
		];

		require_once(APP_ROOT_PATH . '/lib/smsscripts/handlers/generic_callme.php');
		$this->includeFakeGearman();

		handle_generic_callme($message);
		$this->assertFalse(api_campaigns_checknameexists('Reachtel-test-call-me-' . date("FY")));
		$sql = "SELECT * FROM `event_queue` where queue='sms' ORDER BY `eventid` DESC LIMIT 1;";
		$rs2 = api_db_query_read($sql);
		$job = $rs2->GetArray();
		$this->assertCount(1, $job);
		$job = $job[0];
		$this->assertEquals('sms', $job['queue']);
		$details = unserialize($job['details']);
		$this->assertSameEquals($did, (int) $details['didid'], 'Wrong queue didid');
		$this->assertSameEquals($messageContent, $details['message'], 'Wrong queue message');
	}

	/**
	 * @return void
	 */
	public function testGenericCallmeRespectsMessageExpiry() {
		$did = $this->create_new_smsdid();
		$fromDestination = '61432165498';
		$callmedest = '0412457896';
		$sourceCampaignName = 'source-campaign';
		$targetKey = 'test-target-key';
		$mergeDataCampaign = 'merge-data-campaign';
		$this->purge_all_campaigns();
		// faking SESSION with no user security groupaccess
		$_SESSION['userid'] = 2;
		$sourceCampaignId = api_campaigns_add($sourceCampaignName, CAMPAIGN_TYPE_SMS);
		api_campaigns_setting_set($sourceCampaignId, 'callmedestination', $callmedest);
		$targetid = api_targets_add_single($sourceCampaignId, '61478546986', $targetKey, null, ['campaignname' => $mergeDataCampaign]);
		$this->assertNotFalse($targetid);
		// Create
		$sql = 'INSERT INTO call_results (`targetid`, `campaignid`, `value`, `timestamp`) values (?,?,?,?)';
		$this->assertNotFalse(api_db_query_write($sql, [$targetid, $sourceCampaignId, 'SENT', (new \DateTime('-100 seconds'))->format('Y-m-d H:i:s')]));
		$duplicateCampaignid = api_campaigns_add('duplicate-campaign-test', CAMPAIGN_TYPE_VOICE);
		$tags = [
			'call-me-campaign-prefix' => 'Reachtel-test-call-me-',
			'call-me-campaign-duplicate-id' => $duplicateCampaignid,
			'callme-openhour' => '01:00:00',
			'callme-closehour' => '23:00:00',
			'callme-expiry' => 99,
			'api-account' => 123
		];
		api_tags_set(KEY_STORE_TYPE_SMS_DIDS, $did, $tags);
		$message = [
			'sms_account' => $did,
			'target' => api_targets_getinfo($targetid),
			'contents' => 'Call me',
			'e164' => $fromDestination,
			'from' => '61400000000'
		];

		require_once(APP_ROOT_PATH . '/lib/smsscripts/handlers/generic_callme.php');

		$this->listen_mocked_function('api_targets_add_callme');
		$this->mock_function_value(
			'api_targets_add_callme',
			true
		);
		$this->assertTrue(handle_generic_callme($message));
		$this->assertListenMockFunctionHasBeenCalled('api_targets_add_callme', false);
	}
}
