<?php
/**
 * CampaignDataArchiveModuleHelper
 * Helper for campaign archive data
 *
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\traits;

use Services\Campaign\Archiver\ArchiverEnum;
use testing\module\helpers\CampaignModuleHelper;

/**
 * Trait CampaignDataArchiveTest
 */
trait CampaignDataArchiveTest
{
	use CampaignModuleHelper;

	/**
	 * @return array
	 */
	public function api_campaigns_archive_data_data_provider() {
		return $this->build_data_archive_data_provider(['all']);
	}

	/**
	 * @param array $archive_types
	 * @return array
	 */
	protected function build_data_archive_data_provider(array $archive_types) {
		$data_provider = [];
		foreach ($archive_types as $type) {
			$data_provider['wrong sms campaign id without override campaign check with archive type ' . $type] = ['sms', 'sent', true, false, false, $type];
			$data_provider['wrong phone campaign id without override campaign check with archive type ' . $type] = ['phone', 'generated', true, false, false, $type];
			$data_provider['wrong wash campaign id without override campaign check with archive type ' . $type] = ['wash', 'generated', true, false, false, $type];
			$data_provider['wrong email campaign id without override campaign check with archive type ' . $type] = ['email', 'sent', true, false, false, $type];
			$data_provider['wrong sms campaign id override campaign check with archive type ' . $type] = ['sms', 'sent', true, true, true, $type];
			$data_provider['wrong phone campaign id override campaign check with archive type ' . $type] = ['phone', 'generated', true, true, true, $type];
			$data_provider['wrong wash campaign id override campaign check with archive type ' . $type] = ['wash', 'generated', true, true, true, $type];
			$data_provider['wrong email campaign id override campaign check with archive type ' . $type] = ['email', 'sent', true, true, true, $type];
			$data_provider['right sms campaign id with archive type ' . $type] = ['sms', 'sent', false, true, false, $type];
			$data_provider['right phone campaign id with archive type ' . $type] = ['phone', 'generated', false, true, false, $type];
			$data_provider['right wash campaign id with archive type ' . $type] = ['wash', 'generated', false, true, false, $type];
			$data_provider['right email campaign id with archive type ' . $type] = ['email', 'sent', false, true, false, $type];
		}

		return $data_provider;
	}

	/**
	 * @dataProvider api_campaigns_archive_data_data_provider
	 * @param string  $type
	 * @param string  $call_result_value
	 * @param boolean $expected
	 * @param boolean $wrong_campaign
	 * @param boolean $override_campaign_id_check
	 * @param string  $archive_type
	 * @return void
	 */
	public function test_api_campaigns_archive_data(
		$type,
		$call_result_value,
		$expected,
		$wrong_campaign,
		$override_campaign_id_check,
		$archive_type
	) {
		$this->purge_all_campaigns();
		$this->purge_all_archived_data();

		$targets = [
			$type !== 'email' ? '0412345678' : 'test1@test.com',
			$type !== 'email' ? '0412356478' : 'test2@test.com'
		];

		$merge_data_element = [
			'element1' => 'value1',
			'element2' => 'value2'
		];

		if (!$wrong_campaign) {
			$campaign_id = $this->create_new_campaign(null, $type);

			$targetids = [
				api_targets_add_single($campaign_id, $targets[0]),
				api_targets_add_single($campaign_id, $targets[1])
			];

			api_targets_add_extradata_multiple($campaign_id, $targets[0], $merge_data_element);
			api_targets_add_extradata_multiple($campaign_id, $targets[1], $merge_data_element);

			$this->add_campaign_call_result('2018-05', $targetids[0], $call_result_value);
		} else {
			$campaign_id = 0;
		}

		switch ($archive_type) {
			case 'targets':
				$this->assertSameEquals($expected, api_targets_archive($campaign_id, $override_campaign_id_check, ArchiverEnum::MANUAL()));
				$this->assert_targets_and_archive();
				break;

			case 'response_data':
				$this->assertSameEquals($expected, api_data_responses_archive($campaign_id, $override_campaign_id_check));
				$this->assert_response_data_and_archive();
				break;

			case 'merge_data':
				$this->assertSameEquals($expected, api_data_merge_archive($campaign_id, $override_campaign_id_check));
				$this->assert_merge_data_and_archive();
				break;

			case 'call_results':
				$this->assertSameEquals($expected, api_data_callresult_archive($campaign_id, $override_campaign_id_check));
				$this->assert_call_results_and_archive();
				break;

			default:
				$this->assertSameEquals($expected, api_campaigns_archive_data($campaign_id, $override_campaign_id_check));
				$this->assert_targets_and_archive();
				$this->assert_call_results_and_archive();
				$this->assert_merge_data_and_archive();
				$this->assert_response_data_and_archive();
				break;
		}
	}

	/**
	 * @param boolean $archive
	 * @return array
	 */
	private function fetch_all_response_data($archive = false) {
		$sql = sprintf(
			'SELECT `resultid`, `campaignid`, `targetid`, `eventid`, `targetkey`, `action`, `value` FROM %s',
			$archive ? 'response_data_archive' : 'response_data'
		);
		return $this->fetch_data($sql);
	}

	/**
	 * @param boolean $archive
	 * @return array
	 */
	private function fetch_all_targets($archive = false) {
		$sql = sprintf(
			'SELECT `targetid`, `campaignid`, `targetkey`, `priority`, `status`, `destination`, `nextattempt`
			`reattempts`, `ringouts`, `errors` FROM %s',
			$archive ? 'targets_archive' : 'targets'
		);

		return $this->fetch_data($sql);
	}

	/**
	 * @param boolean $archive
	 * @return array
	 */
	private function fetch_all_call_results($archive = false) {
		$sql = sprintf(
			'SELECT `resultid`, `eventid`, `campaignid`, `targetid`, `value` 	FROM %s',
			$archive ? 'call_results_archive' : 'call_results'
		);

		return $this->fetch_data($sql);
	}

	/**
	 * @param boolean $archive
	 * @return array
	 */
	private function fetch_all_merge_data($archive = false) {
		$sql = sprintf(
			'SELECT `campaignid`, `targetkey`, `element`, `value` FROM %s',
			$archive ? 'merge_data_archive' : 'merge_data'
		);

		return $this->fetch_data($sql);
	}

	/**
	 * @return boolean
	 */
	private function purge_response_data_archive() {
		return $this->purge_data('response_data_archive');
	}

	/**
	 * @return boolean
	 */
	private function purge_targets_archive() {
		return $this->purge_data('targets_archive');
	}

	/**
	 * @return boolean
	 */
	private function purge_call_results_archive() {
		return $this->purge_data('call_results_archive');
	}

	/**
	 * @return boolean
	 */
	private function purge_merge_data_archive() {
		return $this->purge_data('merge_data_archive');
	}

	/**
	 * @return void
	 */
	private function purge_all_archived_data() {
		$this->assertTrue($this->purge_targets_archive());
		$this->assertTrue($this->purge_response_data_archive());
		$this->assertTrue($this->purge_call_results_archive());
		$this->assertTrue($this->purge_merge_data_archive());
	}

	/**
	 * @return void
	 */
	private function assert_targets_and_archive() {
		$this->assertSameEquals($this->fetch_all_targets(), $this->fetch_all_targets(true));
	}

	/**
	 * @return void
	 */
	private function assert_response_data_and_archive() {
		$this->assertSameEquals($this->fetch_all_response_data(), $this->fetch_all_response_data(true));
	}

	/**
	 * @return void
	 */
	private function assert_merge_data_and_archive() {
		$this->assertSameEquals($this->fetch_all_merge_data(), $this->fetch_all_merge_data(true));
	}

	/**
	 * @return void
	 */
	private function assert_call_results_and_archive() {
		$this->assertSameEquals($this->fetch_all_call_results(), $this->fetch_all_call_results(true));
	}

	/**
	 * @param string $table_name
	 * @return boolean
	 */
	private function purge_data($table_name) {
		return api_db_query_write(sprintf('DELETE FROM %s', $table_name)) !== false;
	}

	/**
	 * @param string $sql
	 * @return array
	 */
	private function fetch_data($sql) {
		$rs = api_db_query_read($sql);

		return $rs->RecordCount() ? $rs->GetArray() : [];
	}
}
