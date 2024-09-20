<?php
/**
 * MbfsCascadeCampaignTest
 * Unit test for script mbfs_cascade_campaign.php
 *
 * @author      christopher.colborne@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\scripts\hooks;

use testing\unit\AbstractPhpunitUnitTest;

/**
 * MbfsCascadeCampaignTest
 */
class MbfsCascadeCampaignTest extends AbstractPhpunitUnitTest
{
	/**
	 * Include script for testing
	 *
	 * @return void
	 */
	public static function setUpBeforeClass() {
		require_once(APP_ROOT_PATH . '/scripts/hooks/mbfs_cascade_campaign.php');

		parent::setUpBeforeClass();
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function mbfsCascadeCampaignGetNextCampaignNameDataProvider() {
		return [
			[false, 'Bad Campaign Name'],
			['MBFS-IVR-11Feb2019-Contact2', 'MBFS-IVR-11Feb2019-SFTP'],
			['MBFS-IVR-11Feb2019-Contact3', 'MBFS-IVR-11Feb2019-Contact2'],
			[false, 'MBFS-IVR-11Feb2019-Contact3'],
		];
	}

	/**
	 * @dataProvider mbfsCascadeCampaignGetNextCampaignNameDataProvider
	 * @param string|boolean $expected
	 * @param string         $campaignName
	 * @return void
	 */
	public function testMbfsCascadeCampaignGetNextCampaignName($expected, $campaignName) {
		$this->assertEquals($expected, \_mbfs_cascade_campaign_get_next_campaign_name($campaignName));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function mbfsCascadeCampaignGetNextTargetKeyDataProvider() {
		return [
			[false, ['campaign_number' => 99, 'target_key' => 'aaa']],
			[false, ['campaign_number' => 3, 'target_key' => 't2-3']],

			['c1-t1-2', ['campaign_number' => 1, 'target_key' => 'c1-t1-1']],
			['c1-t1-3', ['campaign_number' => 2, 'target_key' => 'c1-t1-2']],
		];
	}

	/**
	 * @dataProvider mbfsCascadeCampaignGetNextTargetKeyDataProvider
	 * @param string|boolean $expected
	 * @param array          $params
	 * @return void
	 */
	public function testMbfsCascadeCampaignGetNextTargetKey($expected, array $params) {
		$this->assertEquals($expected, \_mbfs_cascade_campaign_get_next_target_key($params['campaign_number'], $params['target_key']));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function mbfsCascadeCampaignGetCampaignNumberDataProvider() {
		return [
			[false, 'Bad Campaign Name'],
			[1, 'MBFS-IVR-11Feb2019-SFTP'],
			[2, 'MBFS-IVR-11Feb2019-Contact2'],
			[3, 'MBFS-IVR-11Feb2019-Contact3'],
		];
	}

	/**
	 * @dataProvider mbfsCascadeCampaignGetCampaignNumberDataProvider
	 * @param string|boolean $expected
	 * @param string         $campaignName
	 * @return void
	 */
	public function testMbfsCascadeCampaignGetCampaignNumber($expected, $campaignName) {
		$this->assertEquals($expected, \_mbfs_cascade_campaign_get_campaign_number($campaignName));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function mbfsCascadeCampaignGetDestinationDataProvider() {
		return [
			[false, ['campaign_number' => 99, 'merge_data' => [
				'Primary_Contact_Number' => '61400000001',
				'Contact_Number_2' => '61400000002',
				'Contact_Number_3' => '61400000003',
			]]],

			['61400000001', ['campaign_number' => 1, 'merge_data' => [
				'Primary_Contact_Number' => '61400000001',
				'Contact_Number_2' => '61400000002',
				'Contact_Number_3' => '61400000003',
			]]],
			['61400000002', ['campaign_number' => 2, 'merge_data' => [
				'Primary_Contact_Number' => '61400000001',
				'Contact_Number_2' => '61400000002',
				'Contact_Number_3' => '61400000003',
			]]],
			['61400000003', ['campaign_number' => 3, 'merge_data' => [
				'Primary_Contact_Number' => '61400000001',
				'Contact_Number_2' => '61400000002',
				'Contact_Number_3' => '61400000003',
			]]],
		];
	}

	/**
	 * @dataProvider mbfsCascadeCampaignGetDestinationDataProvider
	 * @param string|boolean $expected
	 * @param array          $params
	 * @return void
	 */
	public function testMbfsCascadeCampaignGetDestination($expected, array $params) {
		$this->assertEquals($expected, \_mbfs_cascade_campaign_get_destination($params['campaign_number'], $params['merge_data']));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function apiCampaignsHooksMbfsCascadeCampaignDataProvider() {
		$contact_merge_data = [
			'Primary_Contact_Number' => '61400000001',
			'Contact_Number_2' => '61400000002',
			'Contact_Number_3' => '61400000003',
			'Contact_Name' => 'Name 1',
			'Contact_DOB_Day' => '01',
			'Contact_DOB_Month' => '01',
			'Contact_DOB_Year' => '2001',
			'Contact_Name_2' => 'Name 2',
			'Contact_DOB_Day_2' => '02',
			'Contact_DOB_Month_2' => '02',
			'Contact_DOB_Year_2' => '2002',
			'Contact_Name_3' => 'Name 3',
			'Contact_DOB_Day_3' => '03',
			'Contact_DOB_Month_3' => '03',
			'Contact_DOB_Year_3' => '2003',
		];

		return [
			[
				true,
				[
					'campaign_id' => '1',
					'next_campaign_id' => '2',
					'tags' => [
						'next-call-delay-hours' => 2,
						'send-rate-base-hours' => 2,
					],
					'expected_sendrate' => 1,
					'targets' => [
						'11' => [
							'targetid' => '11',
							'campaignid' => '1',
							'targetkey' => 'c1-t1-1',
							'priority' => '1',
							'status' => 'ABANDONED',
							'destination' => '61400000011',
							'nextattempt' => null,
							'reattempts' => null,
							'ringouts' => 0,
							'errors' => 0,
							'call_results' => [
								'GENERATED' => date('Y-m-d H:i:s', strtotime('15 minutes ago')),
							],
							'merge_data' => [
								'Primary_Contact_Number' => '61400000011',
								'Contact_Number_2' => '61400000012',
								'Contact_Number_3' => '61400000013',
								'base_target_key' => 'c1-t1',
							] + $contact_merge_data + [
								'CURRENT_NAME' => 'Name 1',
								'DOB_DAY' => '01',
								'DOB_MONTH' => '01',
								'DOB_YEAR' => '2001',
							],
						],
					],
					'campaigns' => [
						'1' => 'MBFS-IVR-11Feb2019-SFTP',
						'2' => 'MBFS-IVR-11Feb2019-Contact2',
						'3' => 'MBFS-IVR-11Feb2019-Contact3',
					],
				]
			],
			[
				true,
				[
					'campaign_id' => '2',
					'next_campaign_id' => '3',
					'tags' => [
						'next-call-delay-hours' => 3,
						'send-rate-base-hours' => 5,
					],
					'expected_sendrate' => 1,
					'targets' => [
						'21' => [
							'targetid' => '21',
							'campaignid' => '2',
							'targetkey' => 'c1-t1-2',
							'priority' => '1',
							'status' => 'ABANDONED',
							'destination' => '61400000012',
							'nextattempt' => null,
							'reattempts' => null,
							'ringouts' => 0,
							'errors' => 0,
							'call_results' => [
								// NO GENERATED!
							],
							'merge_data' => [
								'Primary_Contact_Number' => '61400000011',
								'Contact_Number_2' => '61400000012',
								'Contact_Number_3' => '61400000013',
								'base_target_key' => 'c1-t1',
							] + $contact_merge_data,
						],
					],
					'campaigns' => [
						'1' => 'MBFS-IVR-11Feb2019-SFTP',
						'2' => 'MBFS-IVR-11Feb2019-Contact2',
						'3' => 'MBFS-IVR-11Feb2019-Contact3',
					],
				]
			],
			// test send rate
			[
				true,
				[
					'campaign_id' => '1',
					'next_campaign_id' => '2',
					'tags' => [
						'next-call-delay-hours' => 2,
						'send-rate-base-hours' => 2,
					],
					'expected_sendrate' => 2,
					'targets' => [
						'11' => [
							'targetid' => '11',
							'campaignid' => '1',
							'targetkey' => 'c1-t1-1',
							'priority' => '1',
							'status' => 'ABANDONED',
							'destination' => '61400000011',
							'nextattempt' => null,
							'reattempts' => null,
							'ringouts' => 0,
							'errors' => 0,
							'call_results' => [
								'GENERATED' => date('Y-m-d H:i:s', strtotime('15 minutes ago')),
							],
							'merge_data' => [
								'Primary_Contact_Number' => '61400000011',
								'Contact_Number_2' => '61400000012',
								'Contact_Number_3' => '61400000013',
								'base_target_key' => 'c1-t1',
							] + $contact_merge_data,
						],
						'12' => [
							'targetid' => '12',
							'campaignid' => '1',
							'targetkey' => 'c1-t2-1',
							'priority' => '1',
							'status' => 'ABANDONED',
							'destination' => '61400000011',
							'nextattempt' => null,
							'reattempts' => null,
							'ringouts' => 0,
							'errors' => 0,
							'call_results' => [
								'GENERATED' => date('Y-m-d H:i:s', strtotime('15 minutes ago')),
							],
							'merge_data' => [
								'Primary_Contact_Number' => '61400000011',
								'Contact_Number_2' => '61400000012',
								'Contact_Number_3' => '61400000013',
								'base_target_key' => 'c1-t2',
							] + $contact_merge_data,
						],
						'13' => [
							'targetid' => '13',
							'campaignid' => '1',
							'targetkey' => 'c1-t3-1',
							'priority' => '1',
							'status' => 'ABANDONED',
							'destination' => '61400000011',
							'nextattempt' => null,
							'reattempts' => null,
							'ringouts' => 0,
							'errors' => 0,
							'call_results' => [
								'GENERATED' => date('Y-m-d H:i:s', strtotime('15 minutes ago')),
							],
							'merge_data' => [
								'Primary_Contact_Number' => '61400000011',
								'Contact_Number_2' => '61400000012',
								'Contact_Number_3' => '61400000013',
								'base_target_key' => 'c1-t3',
							] + $contact_merge_data,
						],
						'14' => [
							'targetid' => '14',
							'campaignid' => '1',
							'targetkey' => 'c1-t4-1',
							'priority' => '1',
							'status' => 'ABANDONED',
							'destination' => '61400000011',
							'nextattempt' => null,
							'reattempts' => null,
							'ringouts' => 0,
							'errors' => 0,
							'call_results' => [
								'GENERATED' => date('Y-m-d H:i:s', strtotime('15 minutes ago')),
							],
							'merge_data' => [
								'Primary_Contact_Number' => '61400000011',
								'Contact_Number_2' => '61400000012',
								'Contact_Number_3' => '61400000013',
								'base_target_key' => 'c1-t4',
							] + $contact_merge_data,
						],
					],
					'campaigns' => [
						'1' => 'MBFS-IVR-11Feb2019-SFTP',
						'2' => 'MBFS-IVR-11Feb2019-Contact2',
						'3' => 'MBFS-IVR-11Feb2019-Contact3',
					],
				]
			],
		];
	}

	/**
	 * Test the hook
	 * This test is using mocks to test the parameters passed to methods
	 * It fails if it falls through to the real function.
	 *
	 * @dataProvider apiCampaignsHooksMbfsCascadeCampaignDataProvider
	 * @param boolean $expected
	 * @param array   $data
	 *
	 * @return void
	 */
	public function testApiCampaignsHooksMbfsCascadeCampaign($expected, array $data) {
		$campaigns_getsingle_array = array_map(
			function($id, $name) {
				return [
					'params' => [(string) $id, 'name'],
					'return' => $name,
				];
			},
			array_keys($data['campaigns']),
			$data['campaigns']
		);

		$this->mock_function_param_value('api_campaigns_setting_getsingle', $campaigns_getsingle_array, null, true);

		$this->mock_function_param_value(
			'api_campaigns_tags_get',
			[
				[
					'params' => $data['campaign_id'],
					'return' => $data['tags'],
				]
			],
			null,
			true
		);

		$listall_array = array_map(
			function($elem) {
				return [
					'targetid' => $elem['targetid'],
					'destination' => $elem['destination'],
				];
			},
			$data['targets']
		);

		$this->mock_function_param_value(
			'api_targets_listall',
			[
				['params' => $data['campaign_id'], 'return' => $listall_array],
			],
			null,
			true
		);

		$checknameexists_array = array_map(
			function($id, $name) {
				return [
					'params' => $name,
					'return' => (string) $id,
				];
			},
			array_keys($data['campaigns']),
			$data['campaigns']
		);

		$this->mock_function_param_value('api_campaigns_checknameexists', $checknameexists_array, null, true);

		$getinfo_array = array_values(
			array_map(
				function($elem) {
					return [
						'params' => (int) $elem['targetid'],
						'return' => [
							'targetid' => $elem['targetid'],
							'campaignid' => $elem['campaignid'],
							'targetkey' => $elem['targetkey'],
							'priority' => $elem['priority'],
							'status' => $elem['status'],
							'destination' => $elem['destination'],
							'nextattempt' => $elem['nextattempt'],
							'reattempts' => $elem['reattempts'],
							'ringouts' => $elem['ringouts'],
							'errors' => $elem['errors'],
						]
					];
				},
				$data['targets']
			)
		);

		$this->mock_function_param_value('api_targets_getinfo', $getinfo_array, null, true);

		$merge_data_array = [];
		$add_targets_array = [];
		$callresult_array = [];

		foreach ($data['targets'] as $elem) {
			$base_target_key = isset($elem['merge_data']['base_target_key']) ? $elem['merge_data']['base_target_key'] : '';

			$callresult_array[]  = [
				'params' => (int) $elem['targetid'],
				'return' => $elem['call_results'],
			];

			$merge_data_array[] = [
				'params' => [$data['campaign_id'], $base_target_key . '-1'],
				'return' => $elem['merge_data'],
			];
			$merge_data_array[] = [
				'params' => [$data['campaign_id'], $base_target_key . '-2'],
				'return' => $elem['merge_data'],
			];
			$merge_data_array[] = [
				'params' => [$data['campaign_id'], $base_target_key . '-3'],
				'return' => $elem['merge_data'],
			];

			$next_call_delay_hours = $data['tags']['next-call-delay-hours'];

			// add next-call-attempt-aest to expected merge data
			$generated_time = isset($elem['call_results']['GENERATED']) ? $elem['call_results']['GENERATED'] : 'now';
			$elem['merge_data']['next-call-attempt-aest'] = date('Y-m-d H:i:00', strtotime($generated_time) + ($next_call_delay_hours * 60 * 60));

			$destination2 = isset($elem['merge_data']['Contact_Number_2']) ? $elem['merge_data']['Contact_Number_2'] : '';
			$well_known_fields2 = [
				'CURRENT_NAME' => $elem['merge_data']['Contact_Name_2'],
				'DOB_DAY' => $elem['merge_data']['Contact_DOB_Day_2'],
				'DOB_MONTH' => $elem['merge_data']['Contact_DOB_Month_2'],
				'DOB_YEAR' => $elem['merge_data']['Contact_DOB_Year_2'],
			];

			$add_targets_array[] = [
				'params' => [
					(string) array_keys($data['campaigns'])[1],
					$destination2,
					$base_target_key . '-2',
					$elem['priority'],
					array_merge($elem['merge_data'], $well_known_fields2),
				],
				'return' => true,
			];

			$destination3 = isset($elem['merge_data']['Contact_Number_3']) ? $elem['merge_data']['Contact_Number_3'] : '';
			$well_known_fields3 = [
				'CURRENT_NAME' => $elem['merge_data']['Contact_Name_3'],
				'DOB_DAY' => $elem['merge_data']['Contact_DOB_Day_3'],
				'DOB_MONTH' => $elem['merge_data']['Contact_DOB_Month_3'],
				'DOB_YEAR' => $elem['merge_data']['Contact_DOB_Year_3'],
			];

			$add_targets_array[] = [
				'params' => [
					(string) array_keys($data['campaigns'])[2],
					$destination3,
					$base_target_key . '-3',
					$elem['priority'],
					array_merge($elem['merge_data'], $well_known_fields3),
				],
				'return' => true,
			];
		};

		$this->mock_function_param_value('api_data_merge_get_all', $merge_data_array, null, true);
		$this->mock_function_param_value('api_targets_add_single', $add_targets_array, null, true);
		$this->mock_function_param_value('api_data_callresult_get_all_bytargetid', $callresult_array, null, true);

		// add status active call and sendrate call
		$campaigns_setsingle_array = [
			[
				'params' => [
					$data['next_campaign_id'],
					'status',
					'ACTIVE',
				],
				'return' => true,
			],
			[
				'params' => [
					$data['next_campaign_id'],
					'sendrate',
					$data['expected_sendrate']
				],
				'return' => true,
			],
		];
		$this->mock_function_param_value(
			'api_campaigns_setting_set',
			$campaigns_setsingle_array,
			null,
			true
		);

		ob_start();
		api_campaigns_hooks_mbfs_cascade_campaign($data['campaign_id']);
		$result = ob_get_contents();
		ob_end_flush();
		$this->assertEmpty($result, 'Unexpected test output - see above for errors');
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function mbfsCascadeCampaignGetWellKnownFieldsDataProvider() {
		$merge_data = [
			'Primary_Contact_Number' => '61400000001',
			'Contact_Number_2' => '61400000002',
			'Contact_Number_3' => '61400000003',
			'Contact_Name' => 'Name 1',
			'Contact_DOB_Day' => '01',
			'Contact_DOB_Month' => '01',
			'Contact_DOB_Year' => '2001',
			'Contact_Name_2' => 'Name 2',
			'Contact_DOB_Day_2' => '02',
			'Contact_DOB_Month_2' => '02',
			'Contact_DOB_Year_2' => '2002',
			'Contact_Name_3' => 'Name 3',
			'Contact_DOB_Day_3' => '03',
			'Contact_DOB_Month_3' => '03',
			'Contact_DOB_Year_3' => '2003',
		];

		return [
			[false, ['campaign_number' => 99, 'merge_data' => $merge_data]],

			[[
				'CURRENT_NAME' => 'Name 1',
				'DOB_DAY' => '01',
				'DOB_MONTH' => '01',
				'DOB_YEAR' => '2001',
			], ['campaign_number' => 1, 'merge_data' => $merge_data]],
			[[
				'CURRENT_NAME' => 'Name 2',
				'DOB_DAY' => '02',
				'DOB_MONTH' => '02',
				'DOB_YEAR' => '2002',
			], ['campaign_number' => 2, 'merge_data' => $merge_data]],
			[[
				'CURRENT_NAME' => 'Name 3',
				'DOB_DAY' => '03',
				'DOB_MONTH' => '03',
				'DOB_YEAR' => '2003',
			], ['campaign_number' => 3, 'merge_data' => $merge_data]],
		];
	}

	/**
	 * @dataProvider mbfsCascadeCampaignGetWellKnownFieldsDataProvider
	 * @param string|boolean $expected
	 * @param array          $params
	 * @return void
	 */
	public function testMbfsCascadeCampaignGetWellKnownFields($expected, array $params) {
		$this->assertEquals($expected, \_mbfs_cascade_campaign_get_well_known_fields($params['campaign_number'], $params['merge_data']));
	}
}
