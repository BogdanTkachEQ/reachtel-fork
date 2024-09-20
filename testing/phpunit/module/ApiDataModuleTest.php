<?php
/**
 * ApiDataModuleTest
 * Module test for api_assets.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use testing\module\traits\CampaignDataArchiveTest;

/**
 * Api Data Module Test
 */
class ApiDataModuleTest extends AbstractPhpunitModuleTest
{
	use CampaignDataArchiveTest;

	/**
	 * @group api_data_merge_get_count
	 * @return void
	 */
	public function test_api_data_merge_get_count() {
		$campaign_id = $this->get_expected_next_campaign_id();

		// campaign does not exists
		$this->assertSameEquals(
			0,
			api_data_merge_get_count($campaign_id, 'whatever')
		);

		// create new campaign
		$this->assertSameEquals(
			$campaign_id,
			$this->create_new_campaign(uniqid(), 'phone')
		);
		// campaign exists no merge data
		$this->assertSameEquals(0, api_data_merge_get_count($campaign_id, 'whatever'));

		// add merge data
		$targetkey = uniqid('targetkey');
		$targetid = api_targets_add_single(
			$campaign_id,
			'045' . rand(1000000, 9999999),
			$targetkey,
			null,
			['data1' => 'value1', 'data2' => 'value2']
		);
		$this->assertGreaterThan(0, $targetid);

		// campaign have merge data
		$this->assertSameEquals(
			2,
			api_data_merge_get_count($campaign_id, $targetkey)
		);
	}

	/**
	 * @return array
	 */
	public function api_campaigns_archive_data_data_provider() {
		return $this->build_data_archive_data_provider(['call_results', 'response_data', 'merge_data']);
	}

	/**
	 * @return void
	 */
	public function test_api_data_responses_sms_report() {
		$campaignid = $this->create_new_campaign(null, CAMPAIGN_TYPE_SMS);
		$targets = [
			'0412345678' => ['merge1' => 'value1'],
			'0412345668' => ['merge2' => 'value2'],
			'0412345658' => ['merge3' => 'value3'],
			'0412345648' => ['merge4' => 'value4'],
			'0412345638' => ['merge5' => 'value5'],
			'0412345628' => ['merge6' => 'value6'],
		];

		$i = 0;

		$targets_to_process = [];

		foreach ($targets as $destination => $merge_data) {
			$targetid = api_targets_add_single($campaignid, $destination, $destination, null, $merge_data);

			$i++;

			if ($i <= 4) {
				$targets_to_process[] = $targetid;
				continue;
			}
		}

		$this->run_sms_campaign($campaignid, $targets_to_process);

		$report = api_data_responses_sms_report($campaignid);
		$this->assertCount(6, $report);
		foreach ($report as $data) {
			$this->assertArrayHasKey('destination', $data);
			$this->assertArrayHasKey('status', $data);
			$this->assertArrayHasKey('campaignid', $data);
			$this->assertArrayHasKey('merge_data', $data);
			$this->assertSame($data['merge_data'], $targets[$data['destination']]);

			if (in_array($data['destination'], ['0412345638', '0412345628'])) {
				$this->assertSameEquals('READY', $data['status']);
				$this->assertArrayNotHasKey('response_data', $data);
				$this->assertArrayNotHasKey('events', $data);
			} else {
				$this->assertSameEquals('COMPLETE', $data['status']);
				$this->assertArrayHasKey('response_data', $data);
				$this->assertArrayHasKey('events', $data);
				$this->arrayHasKey('SENT', $data['response_data']);
			}
		}

		$start = new \DateTime('-5 minutes');
		$end = new \DateTime('+5 minutes');
		$report = api_data_responses_sms_report($campaignid, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), true);

		$this->assertCount(4, $report);
		$received_destinations = [];
		foreach ($report as $data) {
			$this->assertArrayHasKey('destination', $data);
			$received_destinations[] = $data['destination'];
			$this->assertArrayHasKey('status', $data);
			$this->assertArrayHasKey('campaignid', $data);
			$this->assertArrayHasKey('merge_data', $data);
			$this->assertSame($data['merge_data'], $targets[$data['destination']]);
			$this->assertSameEquals('COMPLETE', $data['status']);
			$this->assertArrayHasKey('response_data', $data);
			$this->arrayHasKey('SENT', $data['response_data']);
			$this->assertArrayHasKey('events', $data);
			$this->assertArrayHasKey('billinginfo', $data);
			$this->assertSameEquals(
				[
					'units' => 1,
					'region_id' => 1
				],
				$data['billinginfo']
			);
		}

		$this->assertSameEquals(
			[
				'0412345678',
				'0412345668',
				'0412345658',
				'0412345648',
			],
			$received_destinations
		);
	}

	/**
	 * @return void
	 */
	public function test_api_data_responses_campaign_get_response_count() {
		$campaignid = $this->create_new_campaign(null, CAMPAIGN_TYPE_SMS);

		$targets = [
			'0412345678' => ['merge1' => 'value1'],
			'0412345668' => ['merge2' => 'value2'],
			'0412345658' => ['merge3' => 'value3'],
			'0412345648' => ['merge4' => 'value4'],
			'0412345638' => ['merge5' => 'value5'],
			'0412345628' => ['merge6' => 'value6'],
		];

		$targets_to_process = [];
		foreach ($targets as $destination => $merge) {
			$targetid = api_targets_add_single($campaignid, $destination, $destination, null, $merge);
			$targets_to_process[] = $targetid;
		}

		$this->run_sms_campaign($campaignid, $targets_to_process, new \DateTime("-5 years"));

		$this->assertEquals(6, api_data_responses_campaign_get_response_count($campaignid));
		$this->assertEquals(
			6,
			api_data_responses_campaign_get_response_count(
				$campaignid,
				new \DateTime("-6 years"),
				new \DateTime("-1 years")
			)
		);

		$this->assertEquals(
			0,
			api_data_responses_campaign_get_response_count(
				$campaignid,
				null,
				new \DateTime("-10 years")
			)
		);

		$this->assertEquals(
			0,
			api_data_responses_campaign_get_response_count(
				$campaignid,
				new \DateTime("-2 years")
			)
		);
	}
}
