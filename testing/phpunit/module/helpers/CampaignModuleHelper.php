<?php
/**
 * CampaignModuleHelper
 * Helper to create campaigns
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

use Exception;

/**
 * Trait Helper for campaigns
 */
trait CampaignModuleHelper
{
	use SmsModuleHelper;

	/**
	 * @param string
	 */
	private $default_sms_content = 'Default SMS content';

	/**
	 * @param integer
	 */
	private $default_seconds_gap = 10;

	/**
	 * @param array
	 */
	private $test_fixtures = [
		'sms' => ['+61411111001', '0411111002'],
		'phone' => ['+61422222001', '0422222002'],
		'wash' => ['61433333001', '0433333002'],
		'email' => ['test@morpheus.dev', 'test2@reachtel.com.au']
	];

	/**
	 * @return string
	 */
	protected static function get_campaign_type() {
		return 'CAMPAIGNS';
	}

	/**
	 * @return string
	 */
	protected function get_expected_next_campaign_id() {
		return $this->get_expected_next_id(self::get_campaign_type());
	}

	/**
	 * @return string
	 */
	protected function get_campaign_types() {
		return array_keys(self::get_config('helpers.campaign.default_expected_type_values'));
	}

	/**
	 * @param string  $name
	 * @param string  $type
	 * @param integer $user_id
	 * @param array   $settings
	 * @return integer|false
	 */
	protected function create_new_campaign($name = null, $type = null, $user_id = null, array $settings = []) {
		$all_types = $this->get_campaign_types();

		if ($type && !in_array($type, $all_types)) {
			return false;
		}
		$type = $type ? : $all_types[rand(0, count($all_types) - 1)];

		$expected_campaign_id = $this->get_expected_next_id(self::get_campaign_type());

		if (isset($_SESSION)) {
			unset($_SESSION['userid']);
		}

		if ($user_id) {
			$_SESSION['userid'] = $user_id;
		}

		$campaign_id = api_campaigns_add($name ? : uniqid('test'), $type);

		if ($campaign_id) {
			$campaign_id = (int) $campaign_id;
			$this->assertSameEquals($expected_campaign_id, $campaign_id);

			if ($type == 'sms' && !isset($settings['content'])) {
				$settings['content'] = $this->default_sms_content;
			}

			foreach ($settings as $setting => $value) {
				$this->assertTrue(api_campaigns_setting_set($campaign_id, $setting, $value), "Failed to set setting for campaign id#{$campaign_id}: Setting '$setting' to value " . var_export($value, true));
			}
		}

		return $campaign_id;
	}

	/**
	 * @param integer $campaign_id
	 * @param mixed   $targets
	 * @return true
	 */
	protected function add_campaign_targets($campaign_id, $targets = null) {
		$target_ids = [];

		$campaign_type = api_campaigns_setting_getsingle($campaign_id, "type");
		$this->assertNotFalse($campaign_type, "Type campaign for id '{$campaign_id}' not found");

		if ($targets === null) {
			$targets = $this->get_target_fixtures_by_type($campaign_type);
		}

		foreach ($targets as $targetkey => $target) {
			$target_id = api_targets_add_single($campaign_id, $target, $targetkey);
			$this->assertNotFalse($target_id, "Failed adding target '{$target}' to $campaign_type campaign id {$campaign_id}");
			$target_ids[$target_id] = $target;
		}
		$this->assertSameEquals(count($target_ids), count(api_targets_listall($campaign_id)));

		return true;
	}

	/**
	 * @param string  $invoice_month
	 * @param integer $target_id
	 * @param string  $value
	 * @param integer $seconds_gap
	 * @param string  $date
	 * @return array
	 */
	protected function add_campaign_call_result($invoice_month, $target_id, $value, $seconds_gap = null, $date = 'now') {
		$call_results = [];
		$date = strtotime($date);

		$seconds_gap = (int) ($seconds_gap ? : $this->default_seconds_gap);
		$target = api_targets_getinfo($target_id);
		$this->assertNotFalse($target, "Failed to find info for target id {$target_id}");
		$campaign_id = $target['campaignid'];

		$campaign_type = api_campaigns_setting_getsingle($campaign_id, 'type');
		$billingmonth = api_campaigns_setting_getsingle($campaign_id, 'billingmonth');

		if (!is_null($invoice_month) && $billingmonth == 'perpetual' && date('Y-m', $date) !== $invoice_month) {
			return $call_results;
		}

		$call_results_value_map = $this->get_call_results_value_map($value, $campaign_type);
		$event_id = api_misc_uniqueid();

		// generate call results
		$nb_values = count($call_results_value_map['values']);
		foreach ($call_results_value_map['values'] as $i => $value) {
			$result_id = api_data_callresult_add($campaign_id, $event_id, $target_id, $value);
			$this->assertNotFalse($result_id);

			// gap in seconds
			$timestamp_add = $seconds_gap * ($nb_values - $i);

			// update timestamp
			$sql = "UPDATE `call_results` SET `timestamp` = DATE_SUB(?, INTERVAL ? SECOND) WHERE `resultid` = ?;";
			api_db_query_write($sql, [date('Y-m-d H:i:s', $date), $timestamp_add, $result_id]);
			$this->assertSameEquals(1, api_db_affectedrows());

			$sql = "SELECT `timestamp` FROM `call_results` WHERE `resultid` = ?;";
			$rs = api_db_query_read($sql, [$result_id]);
			$this->assertInstanceOf('ADORecordSet_mysqli', $rs);
			$this->assertNotNull($rs->Fields('timestamp'));

			$call_results[$event_id][$result_id] = [
				'timestamp' => $rs->Fields('timestamp'),
				'value' => $value,
			];
		}

		$call_results[$event_id]['billsec'] = $seconds_gap;
		$call_results[$event_id]['duration'] = $seconds_gap * ($nb_values - 1);

		// generate responses data (optional)
		if (isset($call_results_value_map['responses_data'][$campaign_type])) {
			foreach ($call_results_value_map['responses_data'][$campaign_type] as $i => $value) {
				$this->assertInternalType('array', $value, 'responses data must items of arrays');
				$this->assertArrayHasKey(0, $value);
				$this->assertArrayHasKey(1, $value);
				$result_id = api_data_responses_add($campaign_id, $event_id, $target_id, $target['targetkey'], $value[0], $value[1]);

				// update timestamp
				$sql = "UPDATE `response_data` rd
						INNER JOIN `call_results` cr ON (rd.campaignid = cr.campaignid AND rd.targetid = cr.targetid AND rd.eventid = cr.eventid)
						SET rd.`timestamp` = cr.`timestamp` WHERE rd.`resultid` = ?;";
				api_db_query_write($sql, [$result_id]);
				$this->assertSameEquals(1, api_db_affectedrows());
			}
		}

		return $call_results;
	}

	/**
	 * @return void
	 */
	protected function purge_all_campaigns() {
		$all_campaigns = api_campaigns_list_all();
		foreach ($all_campaigns as $campaign_id) {
			$this->assertTrue(api_campaigns_delete($campaign_id));
		}
	}

	/**
	 * @param integer        $campaignid
	 * @param array          $targetids_to_process
	 * @param \DateTime|null $date
	 * @throws \RuntimeException Non sms campaign passed.
	 * @return void
	 */
	protected function run_sms_campaign($campaignid, array $targetids_to_process = [], \DateTime $date = null) {
		if (api_campaigns_setting_getsingle($campaignid, CAMPAIGN_SETTING_TYPE) !== CAMPAIGN_TYPE_SMS) {
			throw new \RuntimeException('Non sms campaign passed.');
		}
		$targets = api_campaigns_get_all_targets($campaignid);
		if (is_null($date)) {
			$date = new \DateTime();
		}
		$message = api_campaigns_setting_getsingle($campaignid, CAMPAIGN_SETTING_CONTENT);
		foreach ($targets as $target) {
			if ($targetids_to_process && !in_array($target['targetid'], $targetids_to_process)) {
				continue;
			}
			$this->assertTrue(api_targets_updatestatus_completebytargetkey($campaignid, $target['targetkey']));
			$call_result = $this->add_campaign_call_result(null, $target['targetid'], 'sent', null, $date->format('Y-m-d H:i:s'));
			$eventid = array_keys($call_result)[0];
			$this->assertNotFalse($this->create_campaign_sms($target['targetid'], $message, $eventid, $date));
		}
	}

	/**
	 * @param string $key
	 * @param mixed  $campaign_type
	 * @return array
	 * @throws Exception If key does not exists.
	 */
	private function get_call_results_value_map($key, $campaign_type = null) {
		$call_results_value = self::get_config("helpers.campaign.call_results_map.{$key}");
		$this->assertArrayHasKey('values', $call_results_value, "No 'values' found for '{$key}' in call results value map");

		if (isset($call_results_value['types']) && !in_array($campaign_type, (array) $call_results_value['types'])) {
			throw new Exception("Call results key '{$key}' is not valid for campign type '{$campaign_type}'");
		}

		return $call_results_value;
	}

	/**
	 * @param string $type
	 * @return array
	 */
	private function get_target_fixtures_by_type($type) {
		$this->assertArrayHasKey($type, $this->test_fixtures);
		return $this->test_fixtures[$type];
	}
}
