<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Class ApiTargetsUnitTest
 */
class ApiTargetsUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return void
	 */
	public function test_api_targets_get_merge_data_for_target_without_info() {
		$targetid = 345;
		$this->mock_function_param_value(
			'api_targets_getinfo',
			[['params' => $targetid, 'return' => []]],
			false
		);

		$this->assertSameEquals([], api_targets_get_merge_data($targetid));
	}

	/**
	 * @return void
	 */
	public function test_api_targets_get_merge_data() {
		$targetid = 345;
		$targetkey = 456;
		$this->mock_function_param_value(
			'api_targets_getinfo',
			[
				[
					'params' => $targetid,
					'return' => ['targetid' => 345, 'destination' => '0412345678', 'campaignid' => 234, 'targetkey' => $targetkey]
				]
			],
			false
		);

		$records = [
			['campaignid' => 234, 'targetkey' => $targetkey, 'element' => 'element1', 'value' => 'value1'],
			['campaignid' => 234, 'targetkey' => $targetkey, 'element' => 'element2', 'value' => 'value2'],
		];

		$sql = 'SELECT * FROM `merge_data` WHERE `targetkey` = ? AND `campaignid` = ?';
		$params = [$targetkey, 234];
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_param_value(
			'api_db_query_read',
			[
				[
					'params' => [$sql, $params], 'return' => $this->mock_ado_records($records)
				]
			],
			false
		);

		$this->assertSameEquals($records, api_targets_get_merge_data($targetid));
	}

	/**
	 * @return void
	 */
	public function test_api_targets_get_last_sms_sent_time() {
		$this->mock_function_param_value(
			'api_data_callresult_get_all_bytargetid',
			[
				['params' => [123, 'resultid'], 'return' => ['SENT' => '2020-07-31 15:00:00']]
			],
			null
		);
		$this->assertNull(api_targets_get_last_sms_sent_time(345));
		$this->assertSameEquals('2020-07-31 15:00:00', api_targets_get_last_sms_sent_time(123));
	}
}
