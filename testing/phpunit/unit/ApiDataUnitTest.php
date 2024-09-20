<?php
/**
 * ApiDataTest
 * Unit test for api_data.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

use testing\helpers\MethodParametersHelper;

/**
 * Api Data Unit Test class
 */
class ApiDataUnitTest extends AbstractPhpunitUnitTest
{
	use MethodParametersHelper;

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_target_status_data() {
		$target_status = function($ready = 0, $inprogress = 0, $reattempt = 0, $abandoned = 0, $complete = 0) {
			return [
				'READY' => $ready,
				'INPROGRESS' => $inprogress,
				'REATTEMPT' => $reattempt,
				'ABANDONED' => $abandoned,
				'COMPLETE' => $complete,
				'TOTAL' => array_sum(func_get_args())
			];
		};

		return [
			// Failure wrong campaign id
			[false, false],
			[false, null],
			[false, ''],

			// Success no targets
			[$target_status(), 99],

			// Success
			[$target_status(), 1, []],
			[$target_status(2), 2, ['READY' => 2]],
			[$target_status(0, 2), 3, ['INPROGRESS' => 2]],
			[$target_status(0, 0, 2), 4, ['REATTEMPT' => 2]],
			[$target_status(0, 0, 0, 2), 5, ['ABANDONED' => 2]],
			[$target_status(0, 0, 0, 0, 2), 6, ['COMPLETE' => 2]],
			[
				$target_status(
					($ready = rand(2, 6)),
					($inprogress = rand(2, 6)),
					($reattempt = rand(2, 6)),
					($abandoned = rand(2, 6)),
					($complete = rand(2, 6))
				),
				7,
				[
					'READY' => $ready,
					'INPROGRESS' => $inprogress,
					'REATTEMPT' => $reattempt,
					'ABANDONED' => $abandoned,
					'COMPLETE' => $complete,
				]
			],
		];
	}

	/**
	 * @group api_data_target_status
	 * @dataProvider api_data_target_status_data
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @param array $ado_records
	 * @return void
	 */
	public function test_api_data_target_status($expected_value, $campaignid, array $ado_records = []) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value('api_db_query_read', $this->mock_ado_records($ado_records));

		$this->assertSameEquals($expected_value, api_data_target_status($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_target_status_email_data() {
		$target_status = function($statuses = []) {
			return ($statuses + [
				'HARDBOUNCE' => 0,
				'OPEN' => 0,
				'SOFTBOUNCE' => 0,
				'BOUNCED' => 0,
				'DUPLICATE' => 0,
				'UNSUBSCRIBED' => 0,
				'UNSUBSCRIBE' => 0,
				'DNC' => 0,
				'CLICK' => 0,
				'TRACK' => 0,
				'WEBVIEW' => 0,
				'REMOVED' => 0,
				'SENT' => 0,
			]);
		};

		return [
			// Failure wrong campaign id
			[false, false],
			[false, null],
			[false, ''],

			// Success no targets
			[$target_status(), 1],

			// Success
			[$target_status(['BOUNCED' => 5, 'DNC' => 45]), 2],
			[$target_status(['UNSUBSCRIBED' => 1, 'VALUE' => 1]), 3],
		];
	}

	/**
	 * @group api_data_target_status_email
	 * @dataProvider api_data_target_status_email_data
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @param array $ado_records
	 * @return void
	 */
	public function test_api_data_target_status_email($expected_value, $campaignid, array $ado_records = []) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_param_value(
			'api_db_query_read',
			[
				['params' => [1 => [1]], 'return' => $this->mock_ado_records([])],
				['params' => [1 => [1, 'REMOVED']], 'return' => $this->mock_ado_records([])],
				['params' => [1 => [2]], 'return' => $this->mock_ado_records(['BOUNCED' => 5, 'DNC' => 45])],
				['params' => [1 => [2, 'REMOVED']], 'return' => $this->mock_ado_records([])],
				['params' => [1 => [3]], 'return' => $this->mock_ado_records(['UNSUBSCRIBED' => 1])],
				['params' => [1 => [3, 'REMOVED']], 'return' => $this->mock_ado_records([['value' => 'VALUE', 'count' => 1]])],
			]
		);

		$this->assertSameEquals($expected_value, api_data_target_status_email($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_target_status_sms_data() {
		$target_status = function($statuses = []) {
			return ($statuses + [
				'EXPIRED' => 0,
				'UNDELIVERED' => 0,
				'DELIVERED' => 0,
				'DNC' => 0,
				'DUPLICATE' => 0,
				'UNKNOWN' => 0,
				'REMOVED' => 0,
				'SENT' => 0,
			]);
		};

		return [
			// Failure wrong campaign id
			[false, false],
			[false, null],
			[false, ''],

			// Success no targets
			[$target_status(), 1],

			// Success
			[$target_status(['UNDELIVERED' => 10, 'DNC' => 11]), 2],
			[$target_status(['DUPLICATE' => 12, 'VALUE' => 1]), 3],
		];
	}

	/**
	 * @group api_data_target_status_sms
	 * @dataProvider api_data_target_status_sms_data
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @param array $ado_records
	 * @return void
	 */
	public function test_api_data_target_status_sms($expected_value, $campaignid, array $ado_records = []) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_param_value(
			'api_db_query_read',
			[
				['params' => [1 => [1]], 'return' => $this->mock_ado_records([])],
				['params' => [1 => [1, 'REMOVED']], 'return' => $this->mock_ado_records([])],
				['params' => [1 => [2]], 'return' => $this->mock_ado_records(['UNDELIVERED' => 10, 'DNC' => 11])],
				['params' => [1 => [2, 'REMOVED']], 'return' => $this->mock_ado_records([])],
				['params' => [1 => [3]], 'return' => $this->mock_ado_records(['DUPLICATE' => 12])],
				['params' => [1 => [3, 'REMOVED']], 'return' => $this->mock_ado_records([['value' => 'VALUE', 'count' => 1]])],
			]
		);

		$this->assertSameEquals($expected_value, api_data_target_status_sms($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_callresult_add_data() {
		return array_merge(
			// Failure wrong campaign id
			$this->get_test_data_from_parameters_combinations(
				[$this, 'test_api_data_callresult_add'],
				[
					'expected_value' => false,
					'campaignid' => $this->add_parameter_possibilities([null, false, '']),
					'eventid' => $this->add_parameter_possibilities([null, false, '', 1]),
					'targetid' => $this->add_parameter_possibilities([null, false, '', 2]),
					'value' => $this->add_parameter_possibilities([null, false, ''])
				]
			),
			[
				// Failure query fail
				[false, 1],

				// Success
				[99, 2]
			]
		);
	}

	/**
	 * @group api_data_callresult_add
	 * @dataProvider api_data_callresult_add_data
	 * @param mixed  $expected_value
	 * @param mixed  $campaignid
	 * @param mixed  $eventid
	 * @param mixed  $targetid
	 * @param string $value
	 * @return void
	 */
	public function test_api_data_callresult_add($expected_value, $campaignid, $eventid = 1, $targetid = 2, $value = 'value') {
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_param_value(
			'api_db_query_write',
			[
				['params' => [1 => [2, $eventid, $targetid, $value]], 'return' => true],
			],
			false
		);
		$this->mock_function_value('api_db_lastid', 99);

		$this->assertSameEquals($expected_value, api_data_callresult_add($campaignid, $eventid, $targetid, $value));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_callresult_delete_all_data() {
		return [
			// Failure campaign id failure
			[false, false],
			[false, null],
			[false, ''],

			// Failure campaigns not exists
			[false, 1],

			// Success
			[true, 2],
		];
	}

	/**
	 * @group api_data_callresult_delete_all
	 * @dataProvider api_data_callresult_delete_all_data
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @return void
	 */
	public function test_api_data_callresult_delete_all($expected_value, $campaignid) {
		$this->mock_function_param_value(
			'api_campaigns_checkidexists',
			[
				['params' => [2], 'return' => true],
			],
			false
		);
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_value('api_db_query_write', null);

		$this->assertSameEquals($expected_value, api_data_callresult_delete_all($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_callresult_get_all_bytargetid_data() {
		return [
			// Failure target id failure
			[false, false],
			[false, null],
			[false, ''],

			// Failure query
			[[], 1],

			// Success
			[[], 2],
			[[1, 2, 3], 3],
		];
	}

	/**
	 * @group api_data_callresult_get_all_bytargetid
	 * @dataProvider api_data_callresult_get_all_bytargetid_data
	 * @param mixed $expected_value
	 * @param mixed $targetid
	 * @return void
	 */
	public function test_api_data_callresult_get_all_bytargetid($expected_value, $targetid) {
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_param_value(
			'api_db_query_write',
			[
				['params' => [1 => [2]], 'return' => $this->mock_ado_records([])],
				['params' => [1 => [3]], 'return' => $this->mock_ado_records([1, 2, 3])],
			],
			false
		);

		$this->assertSameEquals($expected_value, api_data_callresult_get_all_bytargetid($targetid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_callresult_get_all_data() {
		return [
			// Failure campaign id failure
			[false, false],
			[false, null],
			[false, ''],

			// Failure query
			[[], 1],

			// Success no results
			[[], 2],

			// Success
			[[1, 2, 3], 3],
		];
	}

	/**
	 * @group api_data_callresult_get_all
	 * @dataProvider api_data_callresult_get_all_data
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @return void
	 */
	public function test_api_data_callresult_get_all($expected_value, $campaignid) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_param_value(
			'api_db_query_read',
			[
				['params' => [1 => [2]], 'return' => $this->mock_ado_records([])],
				['params' => [1 => [3]], 'return' => $this->mock_ado_records([1, 2, 3])],
			],
			false
		);

		$this->assertSameEquals($expected_value, api_data_callresult_get_all($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_target_results_data() {
		$targets = function($targets = []) {
			return ($targets + [
				'GENERATED' => 0,
				'ANSWER' => 0,
				'BUSY' => 0,
				'CONGESTION' => 0,
				'CANCEL' => 0,
				'NOANSWER' => 0,
				'DISCONNECTED' => 0
			]);
		};

		return [
			// Failure campaign not exists
			[false, 1],

			// Success not targets
			[$targets(), 2],

			// Success type not phone or wash
			[['WHATEVER' => 99, 'GENERATED' => 0], 3, 'sms'],
			[['WHATEVER' => 99, 'GENERATED' => 0], 3, 'email'],

			// Success
			[$targets(['WHATEVER' => 99]), 3, 'phone'],
			[$targets(['WHATEVER' => 99]), 3, 'wash'],
			[
				$targets([
					'GENERATED' => 10,
					'ANSWER' => 20,
					'BUSY' => 30,
					'CONGESTION' => 40,
					'CANCEL' => 50,
					'NOANSWER' => 60,
					'DISCONNECTED' => 70,
					'WHATEVER' => 99
				]),
				4,
				'phone'
			],
			[
				$targets([
					'GENERATED' => 10,
					'ANSWER' => 20,
					'BUSY' => 30,
					'CONGESTION' => 40,
					'CANCEL' => 50,
					'NOANSWER' => 60,
					'DISCONNECTED' => 70,
					'WHATEVER' => 99
				]),
				4,
				'wash'
			],
		];
	}

	/**
	 * @group api_data_target_results
	 * @dataProvider api_data_target_results_data
	 * @param mixed  $expected_value
	 * @param mixed  $campaignid
	 * @param string $type
	 * @return void
	 */
	public function test_api_data_target_results($expected_value, $campaignid, $type = 'phone') {
		$this->mock_function_param_value(
			'api_campaigns_checkidexists',
			[
				['params' => [1], 'return' => false],
			],
			true
		);

		$this->mock_function_value('api_campaigns_setting_getsingle', $type);

		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_param_value(
			'api_db_query_read',
			[
				['params' => [1 => [2]], 'return' => $this->mock_ado_records([])],
				[
					'params' => [1 => [3]],
					'return' => $this->mock_ado_records(['WHATEVER' => 99])
				],
				[
					'params' => [1 => [4]],
					'return' => $this->mock_ado_records(
						[
							'GENERATED' => 10,
							'ANSWER' => 20,
							'BUSY' => 30,
							'CONGESTION' => 40,
							'CANCEL' => 50,
							'NOANSWER' => 60,
							'DISCONNECTED' => 70,
							'WHATEVER' => 99
						]
					)
				]
			],
			false
		);

		$this->assertSameEquals($expected_value, api_data_target_results($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_responses_add_data() {
		return array_merge(
			// Failure wrong arguments
			$this->get_test_data_from_parameters_combinations(
				[$this, 'test_api_data_responses_add'],
				[
					'expected_value' => false,
					'campaignid' => $this->add_parameter_possibilities([null, false, '', 1]),
					'eventid' => $this->add_parameter_possibilities([null, false, '', 2]),
					'targetid' => $this->add_parameter_possibilities([null, false, '']),
					'targetkey' => 'whatever',
					'action' => 'whatever',
					'value' => 'whatever'
				]
			),
			[
				// Failure sql
				[false, 1, 99, 99, 'targetkey', 'action', 'value'],

				// Success
				[99, 2, 3, 4, 'targetkey', 'action', 'value']
			]
		);
	}

	/**
	 * @group api_data_responses_add
	 * @dataProvider api_data_responses_add_data
	 * @param mixed  $expected_value
	 * @param mixed  $campaignid
	 * @param mixed  $eventid
	 * @param mixed  $targetid
	 * @param string $targetkey
	 * @param string $action
	 * @param string $value
	 * @return void
	 */
	public function test_api_data_responses_add($expected_value, $campaignid, $eventid, $targetid, $targetkey, $action, $value) {
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_value('api_db_query_write', $campaignid > 1);
		$this->mock_function_value('api_db_lastid', 99);
		$this->assertSameEquals($expected_value, api_data_responses_add($campaignid, $eventid, $targetid, $targetkey, $action, $value));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_responses_getall_data() {
		return [
			// Failure target id failure
			[false, false],
			[false, null],
			[false, ''],

			// Failure sql
			[[], 1],

			// Success no results
			[[], 2],

			// Success targetid
			[['targetid'], 3],
			[['targetid'], 3, false],
			[['targetid'], 3, null],
			[['targetid'], 3, ''],

			// Success targetid + eventid
			[['targetid', 'eventid'], 3, 1],
		];
	}

	/**
	 * @group api_data_responses_getall
	 * @dataProvider api_data_responses_getall_data
	 * @param mixed $expected_value
	 * @param mixed $targetid
	 * @param mixed $eventid
	 * @return void
	 */
	public function test_api_data_responses_getall($expected_value, $targetid, $eventid = null) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_param_value(
			'api_db_query_read',
			[
				['params' => [1 => [2]], 'return' => $this->mock_ado_records([])],
				['params' => [1 => [3]], 'return' => $this->mock_ado_records(['targetid'])],
				['params' => [1 => [3, 1]], 'return' => $this->mock_ado_records(['targetid', 'eventid'])]
			],
			false
		);
		$this->assertSameEquals($expected_value, api_data_responses_getall($targetid, $eventid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_responses_summary_data() {
		return [
			// Failure campaign id failure
			[false, false],
			[false, null],
			[false, ''],

			// Success no results
			[[], 1],

			// Success
			[
				[
					[
						'question' => 'q1',
						'answers' => ['a1' => 1],
						'answercount' => 1,
						'total' => 1
					],
					[
						'question' => 'q2',
						'answers' => ['a2' => 2],
						'answercount' => 1,
						'total' => 2
					],
					[
						'question' => 'q3',
						'answers' => ['a3' => 3],
						'answercount' => 1,
						'total' => 3
					],
					[
						'question' => 'q4',
						'answers' => ['a4' => 4],
						'answercount' => 1,
						'total' => 4
					]
				],
				2
			],
		];
	}

	/**
	 * @group api_data_responses_summary
	 * @dataProvider api_data_responses_summary_data
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @return void
	 */
	public function test_api_data_responses_summary($expected_value, $campaignid) {
		$params = function($campaignid) {
			return [$campaignid, 'DELIVERED', 'EXPIRED', 'SENT', 'UNDELIVERED', 'UNKNOWN'];
		};

		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_param_value(
			'api_db_query_read',
			[
				[
					'params' => [
						$this->mock_function_regexp_param('/^SELECT COUNT/'),
						$params(2)
					],
					'return' => $this->mock_ado_records(
						[
							['action' => 'q1', 'value' => 'a1', 'count' => 1],
							['action' => 'q2', 'value' => 'a2', 'count' => 2]
						]
					)
				],
				[
					'params' => [
						$this->mock_function_regexp_param('/^SELECT `action`/'),
						$params(2)
					],
					'return' => $this->mock_ado_records(
						[
							['action' => 'q3', 'value' => 'a3', 'count' => 3],
							['action' => 'q4', 'value' => 'a4', 'count' => 4]
						]
					)
				]
			],
			$this->mock_ado_records([])
		);

		$this->assertSameEquals($expected_value, api_data_responses_summary($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_responses_getquestions_data() {
		return array_merge(
			[
				// Failure campaign id failure
				[false, false],
				[false, null],
				[false, ''],

				// Success reportformatoverride
				[['Q1', 'Q2', 'Q3'], 1],

				// Success empty results
				[[], 2],
				[['SENT', 'TRACK', 'CLICK', 'WEBVIEW', 'HARDBOUNCE', 'SOFTBOUNCE', 'UNSUBSCRIBE', 'REMOVED'], 2, 'email'],
				[['SENT', 'DELIVERED', 'UNDELIVERED', 'UNKNOWN', 'DUPLICATE'], 2, 'sms'],
				[['status'], 2, 'wash'],

				// Success results
				[['action1', 'action2'], 3],
				[['SENT', 'TRACK', 'CLICK', 'WEBVIEW', 'HARDBOUNCE', 'SOFTBOUNCE', 'UNSUBSCRIBE', 'REMOVED', 'action1', 'action2'], 3, 'email'],
				[['SENT', 'DELIVERED', 'UNDELIVERED', 'UNKNOWN', 'DUPLICATE', 'action1', 'action2'], 3, 'sms'],
				[['status', 'action1', 'action2'], 3, 'wash'],
			],
			// Success wash returncarrier and returnhlrcode not set
			$this->get_test_data_from_parameters_combinations(
				[$this, 'test_api_data_responses_getquestions'],
				[
					'expected_value' => ['status'],
					'campaignid' => 4,
					'type' => 'wash',
					'returncarrier' => $this->add_parameter_possibilities([null, false, '', 'off', 'whatever']),
					'returnhlrcode' => $this->add_parameter_possibilities([null, false, '', 'off', 'whatever'])
				]
			),
			// Success wash returncarrier set and returnhlrcode not set
			$this->get_test_data_from_parameters_combinations(
				[$this, 'test_api_data_responses_getquestions'],
				[
					'expected_value' => ['status', 'carrier'],
					'campaignid' => 5,
					'type' => 'wash',
					'returncarrier' => 'on',
					'returnhlrcode' => $this->add_parameter_possibilities([null, false, '', 'off', 'whatever'])
				]
			),
			// Success wash returncarrier not set and returnhlrcode set
			$this->get_test_data_from_parameters_combinations(
				[$this, 'test_api_data_responses_getquestions'],
				[
					'expected_value' => ['status', 'hlrcode'],
					'campaignid' => 6,
					'type' => 'wash',
					'returncarrier' => $this->add_parameter_possibilities([null, false, '', 'off', 'whatever']),
					'returnhlrcode' => 'on'
				]
			),
			[
				// Success wash returncarrier and returnhlrcode set
				[['status', 'carrier', 'hlrcode'], 7, 'wash', 'on', 'on'],

				// Success unset keys
				[[], 8],
				[['SENT', 'TRACK', 'CLICK', 'WEBVIEW', 'HARDBOUNCE', 'SOFTBOUNCE', 'UNSUBSCRIBE', 'REMOVED'], 8, 'email'],
				[['SENT', 'DELIVERED', 'UNDELIVERED', 'UNKNOWN', 'DUPLICATE'], 8, 'sms'],
				[['status'], 8, 'wash'],
			]
		);
	}

	/**
	 * @group api_data_responses_getquestions
	 * @dataProvider api_data_responses_getquestions_data
	 * @param mixed  $expected_value
	 * @param mixed  $campaignid
	 * @param string $type
	 * @param mixed  $returncarrier
	 * @param mixed  $returnhlrcode
	 * @return void
	 */
	public function test_api_data_responses_getquestions($expected_value, $campaignid, $type = 'phone', $returncarrier = null, $returnhlrcode = null) {
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [1 => 'type'], 'return' => $type],
				['params' => [1 => 'returncarrier'], 'return' => $returncarrier],
				['params' => [1 => 'returnhlrcode'], 'return' => $returnhlrcode],
				['params' => [1, 'reportformatoverride'], 'return' => 'Q1,Q2,Q3,Q3']
			],
			false
		);

		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_param_value(
			'api_db_query_read',
			[
				['params' => [1 => [3]], 'return' => $this->mock_ado_records([['action' => 'action1'], ['action' => 'action2']])],
				['params' => [1 => [8]], 'return' => $this->mock_ado_records(
					[
						['action' => 'TRACKCLIENT'],
						['action' => 'CLICKCLIENT'],
						['action' => 'UNSUBSCRIBECLIENT'],
						['action' => 'rt-carriercode'],
						['action' => 'rt-hlrcode'],
					]
				)]
			],
			$this->mock_ado_records([])
		);

		$this->assertSameEquals($expected_value, api_data_responses_getquestions($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_responses_delete_data() {
		return [
			// Failure campaign id failure
			[false, false],
			[false, null],
			[false, ''],

			// Failure campaign not exists
			[false, 1],

			// Success
			[true, 2],
		];
	}

	/**
	 * @group api_data_responses_delete
	 * @dataProvider api_data_responses_delete_data
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @return void
	 */
	public function test_api_data_responses_delete($expected_value, $campaignid) {
		$this->mock_function_param_value(
			'api_campaigns_checkidexists',
			[
				['params' => [2], 'return' => true],
			],
			false
		);
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_value('api_db_query_write', null);

		$this->assertSameEquals($expected_value, api_data_responses_delete($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_format_telephonenumber_data() {
		return [
			// Failure number
			['', false],
			['', null],
			['', ''],
			['', 'whatever'],
			['', 'Whatever'],
			['', 'WHATEVER'],

			// Failure /^1300[0-9]{6}$/
			['13001', 13001],
			['13001', '1300 1'],
			['1300123', 1300123],
			['130012345', 130012345],
			['13001234567', 13001234567],
			['13001234567', '1300 123 4567'],

			// Failure /^1800[0-9]{6}$/
			['18001', 18001],
			['18001', '1800 1'],
			['1800123', 1800123],
			['180012345', 180012345],
			['18001234567', 18001234567],
			['18001234567', '1800 123 4567'],

			// Failure /^13[0-9]{4}$/
			['131', 131],
			['1312', 1312],
			['1312', '13 12'],
			['13123', 13123],
			['1312345', 1312345],
			['131234567', 131234567],
			['1312345678', 1312345678],

			// Failure /^0[2356789][0-9]{8}$/
			['9912345678', '9912345678'],
			['021234567', '021234567'],
			['02123456789', '02123456789'],
			['0412345678', '0412345678'],

			// Failure /^[0-9]{8}$/
			['123456789', 123456789],
			['123456789', '123456789'],
			['1234567890', '1234567890'],

			// Success /^1300[0-9]{6}$/
			['1300 123 456', 1300123456],
			['1300 123 456', '1300123456'],
			['1300 123 456', '1300 123 456'],
			['1300 123 456', 'Contact number: 1300 123 456. Thanks!'],

			// Success /^1800[0-9]{6}$/
			['1800 123 456', 1800123456],
			['1800 123 456', '1800123456'],
			['1800 123 456', '1800 123 456'],
			['1800 123 456', 'Contact number: 1800 123 456. Thanks!'],

			// Success /^13[0-9]{4}$/
			['13 12 34', 131234],
			['13 12 34', '131234'],
			['13 12 34', '13 12 34'],
			['13 12 34', 'Contact number: 13 12 34. Thanks!'],

			// Success /^0[2356789][0-9]{8}$/
			['(02) 1234 5678', '0212345678'],
			['(03) 1234 5678', '0312345678'],
			['(05) 1234 5678', '0512345678'],
			['(06) 1234 5678', '0612345678'],
			['(07) 1234 5678', '0712345678'],
			['(08) 1234 5678', '0812345678'],
			['(09) 1234 5678', '0912345678'],
			['(09) 1234 5678', '09-1234-5678'],
			['(09) 1234 5678', '(09) 1234 5678'],
			['(09) 1234 5678', 'Contact number: (09) 1234 5678. Thanks!'],

			// Success /^[0-9]{8}$/
			['(07) 1234 5678', 12345678],
			['(07) 8765 4321', 87654321],
			['(07) 1234 5678', '0712345678'],
			['(07) 1234 5678', '07-1234-5678'],
			['(07) 1234 5678', '07  1234  5678'],
			['(07) 1234 5678', '(07) 1234 5678'],
			['(07) 1234 5678', 'Contact number: 12345678. Thanks!'],

			// Success with specific chars
			['(07) 1234 5678', '!@#$%^&*()_+-{}][:"/.,	>?12345678/*- -*']
		];
	}

	/**
	 * @group api_data_format_telephonenumber
	 * @dataProvider api_data_format_telephonenumber_data
	 * @param mixed $expected_value
	 * @param mixed $number
	 * @return void
	 */
	public function test_api_data_format_telephonenumber($expected_value, $number) {
		$this->assertSameEquals($expected_value, api_data_format_telephonenumber($number));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_merge_get_single_data() {
		return [
			// Failure sql
			[false, 1, 'targetkey', 'element'],

			// Failure no results
			[false, 2, 'targetkey', 'element'],

			// Success
			[99, 3, 'targetkey', 'element'],
		];
	}

	/**
	 * @group api_data_merge_get_single
	 * @dataProvider api_data_merge_get_single_data
	 * @param mixed  $expected_value
	 * @param mixed  $campaignid
	 * @param string $targetkey
	 * @param string $element
	 * @return void
	 */
	public function test_api_data_merge_get_single($expected_value, $campaignid, $targetkey, $element) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_param_value(
			'api_db_query_read',
			[
				['params' => [1 => [2, $targetkey, $element]], 'return' => $this->mock_ado_records([])],
				['params' => [1 => [3, $targetkey, $element]], 'return' => $this->mock_ado_records(['value' => 99])],
			],
			false
		);

		$this->assertSameEquals($expected_value, api_data_merge_get_single($campaignid, $targetkey, $element));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_merge_get_count_data() {
		return [
			// Failure sql
			[false, 1, 'targetkey'],

			// Failure no results
			[0, 2, 'targetkey'],

			// Success
			[99, 3, 'targetkey'],
		];
	}

	/**
	 * @group api_data_merge_get_count
	 * @dataProvider api_data_merge_get_count_data
	 * @param mixed  $expected_value
	 * @param mixed  $campaignid
	 * @param string $targetkey
	 * @return void
	 */
	public function test_api_data_merge_get_count($expected_value, $campaignid, $targetkey) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_param_value(
			'api_db_query_read',
			[
				['params' => [1 => [2, $targetkey]], 'return' => $this->mock_ado_records(['count' => 0])],
				['params' => [1 => [3, $targetkey]], 'return' => $this->mock_ado_records(['count' => 99])],
			],
			false
		);

		$this->assertSameEquals($expected_value, api_data_merge_get_count($campaignid, $targetkey));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_merge_get_all_data() {
		return [
			// Failure sql
			[[], 1, 'targetkey'],

			// Failure no results
			[[], 2, 'targetkey'],

			// Success
			[[99], 3, 'targetkey'],
		];
	}

	/**
	 * @group api_data_merge_get_all
	 * @dataProvider api_data_merge_get_all_data
	 * @param mixed  $expected_value
	 * @param mixed  $campaignid
	 * @param string $targetkey
	 * @return void
	 */
	public function test_api_data_merge_get_all($expected_value, $campaignid, $targetkey) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_param_value(
			'api_db_query_read',
			[
				['params' => [1 => [2, $targetkey]], 'return' => $this->mock_ado_records([])],
				['params' => [1 => [3, $targetkey]], 'return' => $this->mock_ado_records([99])],
			],
			false
		);

		$this->assertSameEquals($expected_value, api_data_merge_get_all($campaignid, $targetkey));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_merge_get_alldata_data() {
		return [
			// Failure sql
			[[], 1],

			// Failure no results
			[[], 2],

			// Success
			[['key' => ['el' => 1000]], 3],
		];
	}

	/**
	 * @group api_data_merge_get_alldata
	 * @dataProvider api_data_merge_get_alldata_data
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @return void
	 */
	public function test_api_data_merge_get_alldata($expected_value, $campaignid) {
		// create 1000 results (more than 100 results)
		$ado_records = array_map(
			function($i) {
				return ['targetkey' => 'key', 'element' => 'el', 'value' => $i];
			},
			range(1, 1000)
		);

		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_param_value(
			'api_db_query_read',
			[
				['params' => [1 => [2]], 'return' => $this->mock_ado_records([])],
				['params' => [1 => [3]], 'return' => $this->mock_ado_records($ado_records)],
			],
			false
		);

		$this->assertSameEquals($expected_value, api_data_merge_get_alldata($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_merge_stats_data() {
		return [
			// Failure campaign id failure
			[false, false],
			[false, null],
			[false, ''],

			// Success empty results
			[false, 2],

			// Success element = rt-remoteattachments
			[[], 3],

			// Success
			[[['element' => 'el1', 'count' => 1], ['element' => 'el3', 'count' => 3]], 4],
		];
	}

	/**
	 * @group api_data_merge_stats
	 * @dataProvider api_data_merge_stats_data
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @return void
	 */
	public function test_api_data_merge_stats($expected_value, $campaignid) {
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_param_value(
			'api_db_query_read',
			[
				['params' => [1 => [2]], 'return' => $this->mock_ado_records([])],
				['params' => [1 => [3]], 'return' => $this->mock_ado_records([['element' => 'rt-remoteattachments', 'count' => 3]])],
				['params' => [1 => [4]], 'return' => $this->mock_ado_records(
					[
						['element' => 'el1', 'count' => 1],
						['element' => 'rt-remoteattachments', 'count' => 2],
						['element' => 'el3', 'count' => 3]
					]
				)],
			],
			false
		);
		$this->assertSameEquals($expected_value, api_data_merge_stats($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_merge_delete_data() {
		return [
			// Failure campaign id failure
			[false, false],
			[false, null],
			[false, ''],

			// Success
			[true, 1],
		];
	}

	/**
	 * @group api_data_merge_delete
	 * @dataProvider api_data_merge_delete_data
	 * @param mixed  $expected_value
	 * @param mixed  $campaignid
	 * @param string $element
	 * @return void
	 */
	public function test_api_data_merge_delete($expected_value, $campaignid, $element = 'el') {
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_value('api_db_query_write', true);

		$this->assertSameEquals($expected_value, api_data_merge_delete($campaignid, $element));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_merge_delete_all_data() {
		return [
			// Failure campaign id failure
			[false, false],
			[false, null],
			[false, ''],

			// Success
			[true, 1],
		];
	}

	/**
	 * @group api_data_merge_delete_all
	 * @dataProvider api_data_merge_delete_all_data
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @return void
	 */
	public function test_api_data_merge_delete_all($expected_value, $campaignid) {
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_value('api_db_query_write', true);

		$this->assertSameEquals($expected_value, api_data_merge_delete_all($campaignid));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_merge_process_data() {
		return [
			// Failure no match
			[false, false],
			[null, null],
			['', ''],
			['123', '123'],
			['123', '123'],
			['[%', '[%'],
			['%]', '%]'],
			['[%test', '[%test'],
			['test%]', 'test%]'],
			['[%test%', '[%test%'],
			['%test%]', '%test%]'],
			['[ %test%]', '[ %test%]'],
			['[%test% ]', '[%test% ]'],
			['[%%]', '[%%]'],
			['[%%%]', '[%%%]'],

			// Failure no target id
			['[%no target id%]', '[%no target id%]', false],

			// Failure
			[false, '[%key%]'],

			[false, '[%key1%]'],

			// Failure empty emailreport
			[false, '[%test_empty_emailreport_settings%]'],

			// Success
			['Replace Value 1', 'Replace [%mergeField1%]'],
			['Replace Value 1 Value 2', 'Replace [%mergeField1%] [%mergeField2%]'],
			['Value 1 Value 2 Value 1 Value 2', '[%mergeField1%] [%mergeField2%] [%mergeField1%] [%mergeField2%]'],
			['[%Value 1%]', '[%[%mergeField1%]%]'],

			// Success fallback
			['Replace fallback', 'Replace [%whatever|fallback%]'],

			// Success graceful fail
			['graceful fail ', 'graceful fail [%whatever%]', 500, true],

			// Success target priority
			['target priority Value 1', 'target priority [%mergeField@%]'],

			// Success targetkey
			['target key key', 'target key [%targetkey%]'],

			// Success targetid
			['targetid 666', 'targetid [%targetid%]'],

			// Success destination
			['targetid 2098587545', 'targetid [%destination%]'],

			// Success destination
			['targetid 2098587545', 'targetid [%destination%]'],

			// Success campaignid
			['targetid 1', 'targetid [%campaignid%]'],

			// Success enctargetid
			['targetid CRYPTED', 'targetid [%enctargetid%]'],

			// Success rt-date
			['targetid ' . date('d/m/Y'), 'targetid [%rt-date%]'],

			// Success rt-time
			['targetid 10:20am', 'targetid [%rt-time%]'],
			['targetid 04:00am', 'targetid [%rt-time%]', 450, false, '04:00:00'],
			['targetid 08:21pm', 'targetid [%rt-time%]', 451, false, '20:21:59'],
		];
	}

	/**
	 * @group api_data_merge_process
	 * @dataProvider api_data_merge_process_data
	 * @param mixed   $expected_value
	 * @param mixed   $content
	 * @param mixed   $targetid
	 * @param boolean $gracefulFail
	 * @param string  $time
	 * @return void
	 */
	public function test_api_data_merge_process($expected_value, $content, $targetid = 500, $gracefulFail = false, $time = '10:20:45') {
		$targets_info = [
			'campaignid' => ($content == '[%test_empty_emailreport_settings%]' ? 2 : 1),
			'targetkey' => 'key',
			'targetid' => 666,
			'destination' => 2098587545,
			'priority' => 1
		];

		$this->listen_mocked_function('api_email_template');

		$this->mock_function_param_value(
			'api_campaigns_setting_get_multi_byitem',
			[
				['params' => [1, ['emailreport', 'name', 'owner']], 'return' => ['emailreport' => 'test@example.com', 'name' => 'Test campaign', 'owner' => 1]],
				['params' => [2, ['emailreport', 'name', 'owner']], 'return' => ['name' => 'Test campaign 2', 'owner' => '1']],
			],
			['type' => null]
		);

		if ($content == "[%key%]") {
			$this->mock_function_value('api_users_setting_getsingle', "owner@example.com");
		} else {
			$this->mock_function_value('api_users_setting_getsingle', "");
		}

		$this->mock_function_value('api_misc_crypt_safe', 'CRYPTED');
		$this->mock_function_value('api_email_template', null);
		$this->mock_function_value('api_campaigns_setting_cas', true);
		$this->mock_function_value('api_campaigns_setting_getsingle', null);
		$this->mock_function_value('api_targets_getinfo', ($targetid ? $targets_info : false));
		$this->mock_function_value('api_campaigns_gettimezone', null);
		$this->mock_function_value('date_create', new \DateTime(date("Y-m-d {$time}"), new \DateTimeZone('Australia/Brisbane')));

		$this->mock_function_param_value(
			'api_data_merge_get_single',
			[
				['params' => [2 => 'mergeField1'], 'return' => 'Value 1'],
				['params' => [2 => 'mergeField2'], 'return' => 'Value 2'],
			],
			false
		);

		$this->assertSameEquals($expected_value, api_data_merge_process($content, $targetid, $gracefulFail));

		if (!$expected_value && !$gracefulFail && preg_match_all("/\[%([^%]+)%\]/i", $content)) {
			$params = ($this->fetchListenedMockFunctionParamValues('api_email_template'));
			switch ($content) {
				case "[%key%]":
					$this->assertEquals("owner@example.com", $params[0]["args"][0]["to"]);
					break;
				case "[%key1%]":
					$this->assertEquals("test@example.com", $params[0]["args"][0]["to"]);
					break;
				default:
					$this->assertEquals("ReachTEL Support <support@reachtel.com.au>", $params[0]["args"][0]["to"]);
					break;
			}
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_rate_data() {
		return [
			// Failure campaign id failure
			[[], false],
			[[], null],
			[[], ''],

			// Failure wrong campaign type
			[[], false],
			[[], null],
			[[], ''],
			[[], 'whatever'],
			[[], 0],

			// Success campaign type = phone
			[['region_id' => 10, 'destination_type_id' => 2, 'units' => ['first' => 1]], 1, 10, 2, 4, ['firstinterval' => 50, 'nextinterval' => 10]], // billsec <= firstinterval
			[['region_id' => 11, 'destination_type_id' => 12, 'units' => ['first' => 1, 'next' => 2]], 1, 11, 12, 20, ['firstinterval' => 10, 'nextinterval' => 5]], // billsec > firstinterval
			[['region_id' => 3, 'destination_type_id' => 5, 'units' => ['first' => 1, 'next' => 4.0]], 101, 3, 5, 40, ['firstinterval' => 20, 'nextinterval' => 6]], // billsec > firstinterval
			[['region_id' => 1, 'destination_type_id' => 2, 'units' => ['first' => 1, 'next' => 4.0]], 101, null, null, 40, ['firstinterval' => 20, 'nextinterval' => 6]], //when destination is of invalid format

			// Success campaign type = sms
			[[], 2, null, null, 0, [], false], // eventid not numeric
			[[], 2, 5, null, 0, [], null], // eventid not numeric
			[[], 2, 6, null, 0, [], ''], // eventid not numeric
			[[], 2, 8, null, 0, []],
			[['units' => 1, 'region_id' => 8], 102, 8, null, 0, [], 23],
			[['units' => 1, 'region_id' => 7], 2, 7, null, 0, [], 160],
			[['units' => 2, 'region_id' => 6], 2, 6, null, 0, [], 306],
			[['units' => 3, 'region_id' => 4], 102, 4, null, 0, [], 358],
			[['units' => 3, 'region_id' => 6], 102, null, null, 0, [], 358], //when destination is of invalid format

			// Success campaign type = email
			[['units' => 1], 3, 0],

			// Success campaign type = wash
			[['region_id' => 1, 'destination_type_id' => 2, 'units' => 1], 4, 1, 2],
			[['region_id' => 3, 'destination_type_id' => 4, 'units' => 1], 104, 3, 4],
			[['region_id' => 5, 'destination_type_id' => 6, 'units' => 1], 104, 5, 6],
			[['region_id' => 6, 'destination_type_id' => 13, 'units' => 1], 104, null, null], //when destination is of invalid format
		];
	}

	/**
	 * @group api_data_rate
	 * @dataProvider api_data_rate_data
	 * @param mixed $expected_value
	 * @param mixed $campaignid
	 * @param mixed $region_id
	 * @param mixed $destination_type
	 * @param mixed $billsec
	 * @param array $intervals
	 * @param mixed $eventid
	 * @param mixed $settings
	 * @return void
	 */
	public function test_api_data_rate($expected_value, $campaignid, $region_id = 0, $destination_type = 0, $billsec = 0, array $intervals = [], $eventid = 1234, $settings = null) {
		$this->mock_function_param_value(
			'api_campaigns_setting_get_multi_byitem',
			[
				['params' => [1], 'return' => ['type' => 'phone']],
				['params' => [101], 'return' => ['type' => 'phone', 'region' => 'region']],
				['params' => [2], 'return' => ['type' => 'sms']],
				['params' => [102], 'return' => ['type' => 'sms', 'region' => 'region']],
				['params' => [3], 'return' => ['type' => 'email']],
				['params' => [4], 'return' => ['type' => 'wash']],
				['params' => [104], 'return' => ['type' => 'wash', 'region' => 'region']],
			],
			['type' => null]
		);

		$groupowner = 1;

		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [$campaignid, 'groupowner'], 'return' => $groupowner]
			],
			$groupowner
		);

		$this->mock_function_param_value(
			'api_groups_setting_get_multi_byitem',
			[
				['params' => [$groupowner, ['firstinterval', 'nextinterval']], 'return' => $intervals]
			],
			[]
		);

		// @codingStandardsIgnoreStart
		// numberformat is campaignid > 100
		$this->mock_function_value(
			'api_data_numberformat',
			(is_null($region_id) && is_null($destination_type)) ?
			false :
			['billing_region_id' => $region_id, 'billing_destination_type_id' => $destination_type]
		);
		// @codingStandardsIgnoreEnd
		$this->remove_mocked_functions('api_db_query_read');
		$this->mock_function_value(
			'api_db_query_read',
			$this->mock_ado_records($eventid !== 1234 ? ['length' => $eventid] : [])
		);

		$actual = api_data_rate($campaignid, $billsec, 131111, $eventid, $settings);

		$this->assertSameEquals($expected_value, $actual);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage No phone intervals found for group id 2 when fetching billing data
	 * @return void
	 */
	public function test_api_data_rate_throws_exception_if_no_intervals_found() {
		$campaignid = 1;
		$groupowner = 2;
		$this->mock_function_param_value(
			'api_campaigns_setting_get_multi_byitem',
			[
				['params' => [$campaignid, ['type', 'region', 'content']], 'return' => ['type' => 'phone']],
			],
			[]
		);

		$this->mock_function_value('api_data_numberformat', ['billing_region_id' => 1, 'billing_destination_type_id' => 2]);

		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				['params' => [$campaignid, 'groupowner'], 'return' => $groupowner]
			],
			null
		);

		$this->mock_function_param_value(
			'api_groups_setting_get_multi_byitem',
			[
				['params' => [$groupowner, ['firstinterval', 'nextinterval']], 'return' => []],
			],
			[]
		);
		api_data_rate($campaignid, 5);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_rate_events_data() {
		return [
			[[], ['events' => []]],
			// just events
			[
				[['region_id' => 10, 'destination_type_id' => 2, 'units' => ['first' => 1, 'next' => 2]]],
				['events' => [1 => ['billsec' => 2]]],
				10,
				2
			],
			[
				[
					['region_id' => 10, 'destination_type_id' => 2, 'units' => ['first' => 1]],
					['region_id' => 10, 'destination_type_id' => 2, 'units' => ['first' => 1, 'next' => 2]]
				],
				['events' => [1 => ['billsec' => 1], 2 => ['billsec' => 2]]],
				10,
				2
			],
			// just 1_TRANSDUR or 2_TRANSDUR
			[
				[['region_id' => 10, 'destination_type_id' => 2, 'units' => ['first' => 1]]],
				['response_data' => ['1_TRANSDUR' => 1]],
				10,
				2
			],
			[
				[['region_id' => 2, 'destination_type_id' => 3, 'units' => ['first' => 1, 'next' => 2]]],
				['response_data' => ['2_TRANSDUR' => 2]],
				2,
				3
			],
			// just TRANSDUR and CALLBACK_TRANSDUR
			[
				[
					['region_id' => 3, 'destination_type_id' => 4, 'units' => ['first' => 1, 'next' => 4]],
					['region_id' => 3, 'destination_type_id' => 4, 'units' => ['first' => 1]]
				],
				['response_data' => ['1_TRANSDUR' => 1, 'CALLBACK_TRANSDUR' => 10, 'CALLBACK_TRANSDEST' => 10]],
				3,
				4
			],
			// events and (1_TRANSDUR or 2_TRANSDUR)
			[
				[
					['region_id' => 10, 'destination_type_id' => 2, 'units' => ['first' => 1]],
					['region_id' => 10, 'destination_type_id' => 2, 'units' => ['first' => 1, 'next' => 2]]
				],
				['events' => [1 => ['billsec' => 1]], 'response_data' => ['1_TRANSDUR' => 2]],
				10,
				2
			],
			[
				[
					['region_id' => 1, 'destination_type_id' => 2, 'units' => ['first' => 1, 'next' => 2]],
					['region_id' => 1, 'destination_type_id' => 2, 'units' => ['first' => 1]]
				],
				['events' => [2 => ['billsec' => 2]], 'response_data' => ['2_TRANSDUR' => 1]],
				1,
				2
			],
			[
				[
					['region_id' => 3, 'destination_type_id' => 4, 'units' => ['first' => 1, 'next' => 2]],
					['region_id' => 3, 'destination_type_id' => 4, 'units' => ['first' => 1, 'next' => 2]],
					['region_id' => 3, 'destination_type_id' => 4, 'units' => ['first' => 1, 'next' => 2]]
				],
				['events' => [1 => ['billsec' => 2], 2 => ['billsec' => 2]], 'response_data' => ['1_TRANSDUR' => 2, '2_TRANSDUR' => 2]],
				3,
				4
			],
		];
	}

	/**
	 * @group api_data_rate_events
	 * @dataProvider api_data_rate_events_data
	 * @param integer $expected_value
	 * @param array   $result
	 * @param integer $region_id
	 * @param integer $destination_type
	 * @return void
	 */
	public function test_api_data_events_new($expected_value, array $result, $region_id = 0, $destination_type = 0) {
		$result['destination'] = 'whatever';

		$this->mock_function_param_value(
			'api_data_rate',
			[
				['params' => [1, 1], 'return' => ['region_id' => $region_id, 'destination_type_id' => $destination_type, 'units' => ['first' => 1]]],
				['params' => [1, 2], 'return' => ['region_id' => $region_id, 'destination_type_id' => $destination_type, 'units' => ['first' => 1, 'next' => 2]]],
				['params' => [1, 10], 'return' => ['region_id' => $region_id, 'destination_type_id' => $destination_type, 'units' => ['first' => 1, 'next' => 4]]]
			],
			50
		);

		$this->assertSameEquals($expected_value, api_data_rate_events(1, $result));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_format_data() {
		return [
			// failure wrong type
			[false, 'dest', 'whatever'],

			// failure type phone - wrong formatted destination
			[false, 'dest', 'phone', 'AU', false],
			[false, 'dest', 'phone', 'AU', null],
			[false, 'dest', 'phone', 'AU', ''],
			// success type phone
			['61451123456', 'dest', 'phone', 'AU'],

			// failure type sms - wrong formatted destination
			[false, 'dest', 'sms', 'AU', false],
			[false, 'dest', 'sms', 'AU', null],
			[false, 'dest', 'sms', 'AU', ''],
			[false, 'dest', 'sms'], // type not '*mobile' pattern
			// success type sms
			['61451123456', 'dest', 'sms', 'AU', ['destination' => '61451123456', 'type' => 'aumobile']],

			// failure email
			[false, false, 'email'],
			[false, null, 'email'],
			[false, '', 'email'],
			[false, 'no mail', 'email'],
			[false, 'mail@mail', 'email'],
			[false, 'mail [at] mail [dot] com', 'email'],
			// FIXME MOR-14 [false, ' mail @ reachtel . com . au', 'email'],
			// FIXME MOR-14 [false, 'Phpunit Test <phpunit.test@reachtel.com.au>', 'email'],
			[false, '61451123456', 'email'],
			[false, 'phpunit.test@reachtel.com.au, phpunit.test2@reachtel.com.au', 'email'],
			// success email
			['test@test.com', "test@test.com", 'email'],
			['test@test.com', " \n test@test.com 	 ", 'email'],

			// failure wash
			[false, false, 'wash'],
			[false, null, 'wash'],
			[false, '', 'wash'],
			// success wash
			['test', 'test', 'wash'],
			[str_repeat(' ', 253) . 'te', str_repeat(' ', 253) . 'test', 'wash'], // case trim < 255 chars
		];
	}

	/**
	 * @group api_data_format
	 * @dataProvider api_data_format_data
	 * @param mixed $expected_value
	 * @param mixed $destination
	 * @param mixed $type
	 * @param mixed $region
	 * @param mixed $data_numberformat
	 * @return void
	 */
	public function test_api_data_format($expected_value, $destination, $type, $region = 'AU', $data_numberformat = ['destination' => '61451123456', 'type' => 'unknown']) {
		$this->mock_function_value('api_data_numberformat', $data_numberformat);

		$this->assertSameEquals($expected_value, api_data_format($destination, $type, $region));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_delimit_data() {
		return [
			// no data
			[','],
			[',', null],
			[',', false],

			// no data with delimiter
			[',', '', 0],
			[';', '', 1],
			['|', '', 2],
			["\t", '', 3],
			[',', '', 99], // wrong delimiter

			// delimiter = 0 and data as ,
			['"yep, string contains a comma",', 'yep, string contains a comma', 0],
			['"yep, string contains comma & "double quotes"",', 'yep, string contains comma & "double quotes"', 0],

			// data and delimiter
			['data,', 'data', 0],
			['data;', 'data', 1],
			['data|', 'data', 2],
			["data\t", "data", 3],
			['"data1,data2",', 'data1,data2', 0],
			['data1,data2;', 'data1,data2', 1],
			['data1,data2|', 'data1,data2', 2],
			["data1,data2\t", "data1,data2", 3],
			['data,', "data", 99], // wrong delimiter
			['"data1,data2",', "data1,data2", 99], // wrong delimiter
		];
	}

	/**
	 * @group api_data_delimit
	 * @dataProvider api_data_delimit_data
	 * @param string $expected_value
	 * @param mixed  $data
	 * @param mixed  $delimiter
	 * @return void
	 */
	public function test_api_data_delimit($expected_value, $data = "", $delimiter = 0) {
		$this->mock_function_value(
			'api_data_get_delimiters',
			[",", ";", "|", "\t"]
		);
		$this->assertSameEquals($expected_value, api_data_delimit($data, $delimiter));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_numberformat_failures_data() {
		return [
			// Failure empty destination
			[false, false],
			[false, null],
			[false, ''],
			[false, '+61000'],
			[false, '000'],

			// Failure test MOR-1249
			[false, '0145336623', 'AU'],

			// Failure region
			[false, '312345678', 'FR'],
		];
	}

	/**
	 * @group api_data_numberformat
	 * @dataProvider api_data_numberformat_failures_data
	 * @param string $expected_value
	 * @param mixed  $destination
	 * @param mixed  $region
	 * @return void
	 */
	public function test_api_data_numberformat_failures($expected_value, $destination, $region = 'AU') {
		$this->assertEquals($expected_value, api_data_numberformat($destination, $region));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_numberformat_australia_data() {
		// NOTE http://libphonenumber.appspot.com/

		$au = function(array $v = []) {
			return array_merge(
				[
					'destination' => '61412345678',
					'type' => 'aumobile',
					'fnn' => '0412345678',
					'country' => 'au',
					'countryname' => 'Australia',
					'numbertype' => 'Mobile',
					'billing_region_id' => 1,
					'billing_destination_type_id' => 1,
				],
				$v
			);
		};

		return [
			// Success match /^614[0-9]{8}$/
			[
				$au(),
				'61412345678'
			],
			[
				$au([
						'destination' => '+61460345678',
						'type' => 'aumobile',
						'fnn' => '0460345678',
						'country' => 'au',
						'countryname' => 'Australia',
						'numbertype' => 'Mobile',
						'billing_region_id' => 1,
						'billing_destination_type_id' => 1,
					]),
				'+61460345678'
			],
			// Success Strip the 0011 international prefix
			[
				$au(),
				'001161412345678'
			],

			// Success match /^61[2378][0-9]{8}$/
			[
				$au([
					'destination' => '61212345678',
					'type' => 'aufixedline',
					'fnn' => '0212345678',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'61212345678'
			],
			[
				$au([
					'destination' => '61312345678',
					'type' => 'aufixedline',
					'fnn' => '0312345678',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'61312345678'
			],
			[
				$au([
					'destination' => '61712345678',
					'type' => 'aufixedline',
					'fnn' => '0712345678',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'61712345678'
			],
			[
				$au([
					'destination' => '61812345678',
					'type' => 'aufixedline',
					'fnn' => '0812345678',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'61812345678'
			],

			// Success match AU /^04[0-9]{8}$/
			[
				$au(),
				'0412345678'
			],

			// Success match AU  /^0[2378]{1}[0-9]{8}$/
			[
				$au([
					'destination' => '61212345678',
					'type' => 'aufixedline',
					'fnn' => '0212345678',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'0212345678'
			],
			[
				$au([
					'destination' => '61212345678',
					'type' => 'aufixedline',
					'fnn' => '0212345678',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'212345678'
			],
			[
				$au([
					'destination' => '61312345678',
					'type' => 'aufixedline',
					'fnn' => '0312345678',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'0312345678'
			],
			[
				$au([
					'destination' => '61312345678',
					'type' => 'aufixedline',
					'fnn' => '0312345678',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'312345678'
			],
			[
				$au([
					'destination' => '61712345678',
					'type' => 'aufixedline',
					'fnn' => '0712345678',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'0712345678'
			],
			[
				$au([
					'destination' => '61712345678',
					'type' => 'aufixedline',
					'fnn' => '0712345678',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'712345678'
			],
			[
				$au([
					'destination' => '61812345678',
					'type' => 'aufixedline',
					'fnn' => '0812345678',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'0812345678'
			],
			[
				$au([
					'destination' => '61812345678',
					'type' => 'aufixedline',
					'fnn' => '0812345678',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'812345678'
			],

			// Success Google Lib tests
			[
				$au([
					'destination' => '61451050200',
					'fnn' => '0451050200',
				]),
				'+61 451 050 200'
			],
			[
				$au([
					'destination' => '61240123456',
					'fnn' => '0240123456',
					'type' => 'aufixedline',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'+612 4012 3456'
			],
			[
				$au([
					'destination' => '611800123456',
					'fnn' => '1800123456',
					'type' => 'auoneeight',
					'numbertype' => 'Toll free',
					'billing_destination_type_id' => 3
				]),
				'+61 1800 123 456'
			],
			[
				$au([
					'destination' => '611901234567',
					'fnn' => '1901234567',
					'type' => 'aupremiumrate',
					'numbertype' => 'Premium rate',
					'billing_destination_type_id' => 4
				]),
				'19 0123 4567'
			],
			[
				$au([
					'destination' => '61133333',
					'fnn' => '133333',
					'type' => 'auonethree',
					'numbertype' => 'Shared cost',
					'billing_destination_type_id' => 5
				]),
				'133 333'
			],
			[
				$au([
					'destination' => '61550123456',
					'fnn' => '0550123456',
					'type' => 'auvoip',
					'numbertype' => 'VOIP',
					'billing_destination_type_id' => 6
				]),
				'0550123456'
			],
			[
				$au([
					'destination' => '61161234567',
					'fnn' => '0161234567',
					'type' => 'aupager',
					'numbertype' => 'Pager',
					'billing_destination_type_id' => 8
				]),
				'0161234567'
			],
			[
				$fr = [
					'destination' => '33664248956',
					'fnn' => '0664248956',
					'type' => 'othermobile',
					'numbertype' => 'Mobile',
					'countryname' => 'France',
					'billing_destination_type_id' => 1,
					'billing_region_id' => 6
				],
				'+33664248956',
				CAMPAIGN_SMS_REGION_INTERNATIONAL
			],
			[
				$fr,
				'33664248956',
				CAMPAIGN_SMS_REGION_INTERNATIONAL
			],
		];
	}

	/**
	 * @group api_data_numberformat
	 * @dataProvider api_data_numberformat_australia_data
	 * @param string $expected_value
	 * @param mixed  $destination
	 * @param string $region
	 * @return void
	 */
	public function test_api_data_numberformat_australia($expected_value, $destination, $region = "AU") {
		$this->assertEquals($expected_value, api_data_numberformat($destination, $region));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_numberformat_new_zealand_data() {
		// NOTE http://libphonenumber.appspot.com/

		$nz = function(array $v = []) {
			return array_merge(
				[
					'destination' => '???',
					'fnn' => '???',
					'countryname' => 'New Zealand',
					'country' => 'nz',
					'type' => 'nzmobile',
					'numbertype' => 'Mobile',
					'billing_destination_type_id' => 1,
					'billing_region_id' => 2
				],
				$v
			);
		};

		return [
			// Success NZ Mobile phones ^2[0-9]{7,9}$
			[
				$nz([
					'destination' => '6421234567',
					'fnn' => '021234567'
				]),
				'021234567'
			],
			[
				$nz([
					'destination' => '64701234567',
					'fnn' => '0701234567',
					'type' => 'nzpersonalnumber',
					'numbertype' => 'Personal number',
					'billing_destination_type_id' => 7,
				]),
				'701234567'
			],
			[
				$nz([
					'destination' => '6426123456',
					'fnn' => '026123456',
					'type' => 'nzpager',
					'numbertype' => 'Pager',
					'billing_destination_type_id' => 8,
				]),
				'026123456'
			],
			[
				$nz([
					'destination' => '64508123456',
					'fnn' => '0508123456',
					'type' => 'nzoneeight',
					'numbertype' => 'Toll free',
					'billing_destination_type_id' => 3,
				]),
				'0508123456'
			],
			[
				$nz([
					'destination' => '64900555555',
					'fnn' => '0900555555',
					'type' => 'nzpremiumrate',
					'numbertype' => 'Premium rate',
					'billing_destination_type_id' => 4,
				]),
				'0900555555'
			],

			// Success NZ Fixed lines ^[34679][0-9]{7}
			[
				$nz([
					'destination' => '6432012345',
					'type' => 'nzfixedline',
					'fnn' => '032012345',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2,
				]),
				'032012345'
			],
			[
				$nz([
					'destination' => '6432012345',
					'type' => 'nzfixedline',
					'fnn' => '032012345',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2,
				]),
				'32012345'
			],
			[
				$nz([
					'destination' => '6445123456',
					'type' => 'nzfixedline',
					'fnn' => '045123456',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2,
				]),
				'045123456'
			],
			[
				$nz([
					'destination' => '6445123456',
					'type' => 'nzfixedline',
					'fnn' => '045123456',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2,
				]),
				'45123456'
			],
			[
				$nz([
					'destination' => '6463812345',
					'type' => 'nzfixedline',
					'fnn' => '063812345',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2,
				]),
				'063812345'
			],
			[
				$nz([
					'destination' => '6463812345',
					'type' => 'nzfixedline',
					'fnn' => '063812345',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2,
				]),
				'63812345'
			],
			[
				$nz([
					'destination' => '6473612345',
					'type' => 'nzfixedline',
					'fnn' => '073612345',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2,
				]),
				'073612345'
			],
			[
				$nz([
					'destination' => '6473612345',
					'type' => 'nzfixedline',
					'fnn' => '073612345',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2,
				]),
				'73612345'
			],
			[
				$nz([
					'destination' => '6494112345',
					'type' => 'nzfixedline',
					'fnn' => '094112345',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2,
				]),
				'094112345'
			],
			[
				$nz([
					'destination' => '6494112345',
					'type' => 'nzfixedline',
					'fnn' => '094112345',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2,
				]),
				'94112345'
			],
		];
	}

	/**
	 * @group api_data_numberformat
	 * @dataProvider api_data_numberformat_new_zealand_data
	 * @param string $expected_value
	 * @param mixed  $destination
	 * @return void
	 */
	public function test_api_data_numberformat_new_zealand($expected_value, $destination) {
		$this->assertEquals($expected_value, api_data_numberformat($destination, 'NZ'));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_numberformat_great_britain_data() {
		// NOTE http://libphonenumber.appspot.com/

		$gb = function(array $v = []) {
			return array_merge(
				[
					'destination' => '???',
					'fnn' => '???',
					'countryname' => 'Great Britain',
					'country' => 'gb',
					'type' => 'gbmobile',
					'numbertype' => 'Mobile',
					'billing_destination_type_id' => 1,
					'billing_region_id' => 4
				],
				$v
			);
		};

		return [
			// Success GB Mobile phones ^[127][0-9]{8,9}$
			[
				$gb([
					'destination' => '447312123456',
					'fnn' => '07312123456'
				]),
				'07-3121-23456'
			],
			[
				$gb([
					'destination' => '447312123456',
					'fnn' => '07312123456'
				]),
				'7-3121-23456'
			],
			[
				$gb([
					'destination' => '441223123456',
					'type' => 'gbfixedline',
					'fnn' => '01223123456',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'01223123456'
			],
			[
				$gb([
					'destination' => '441223123456',
					'type' => 'gbfixedline',
					'fnn' => '01223123456',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'1223123456'
			],
			[
				$gb([
					'destination' => '449012345678',
					'type' => 'gbpremiumrate',
					'fnn' => '9012345678',
					'numbertype' => 'Premium rate',
					'billing_destination_type_id' => 4
				]),
				'9012345678'
			],
			[
				$gb([
					'destination' => '445612345678',
					'type' => 'gbvoip',
					'fnn' => '05612345678',
					'numbertype' => 'VOIP',
					'billing_destination_type_id' => 6
				]),
				'5612345678'
			],
			[
				$gb([
					'destination' => '447012345678',
					'type' => 'gbpersonalnumber',
					'fnn' => '07012345678',
					'numbertype' => 'Personal number',
					'billing_destination_type_id' => 7
				]),
				'7012345678'
			],
			[
				$gb([
					'destination' => '447640123456',
					'type' => 'gbpager',
					'fnn' => '07640123456',
					'numbertype' => 'Pager',
					'billing_destination_type_id' => 8
				]),
				'7640123456'
			],
		];
	}

	/**
	 * @group api_data_numberformat
	 * @dataProvider api_data_numberformat_great_britain_data
	 * @param string $expected_value
	 * @param mixed  $destination
	 * @return void
	 */
	public function test_api_data_numberformat_great_britain($expected_value, $destination) {
		$this->assertEquals($expected_value, api_data_numberformat($destination, 'GB'));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_numberformat_singapore_data() {
		// NOTE http://libphonenumber.appspot.com/

		$sg = function(array $v = []) {
			return array_merge(
				[
					'destination' => '???',
					'fnn' => '???',
					'countryname' => 'Singapore',
					'country' => 'sg',
					'type' => 'sgmobile',
					'numbertype' => 'Mobile',
					'billing_destination_type_id' => 1,
					'billing_region_id' => 3
				],
				$v
			);
		};

		return [
			// Success SG Mobile phones +65 XXXX XXXX
			[
				$sg([
					'destination' => '6584398987',
					'fnn' => '84398987'
				]),
				'8439-8987'
			],
			[
				$sg([
					'destination' => '6584129999',
					'fnn' => '84129999'
				]),
				'+6584129999'
			],
			[
				$sg([
					'destination' => '6563297537',
					'fnn' => '63297537',
					'type' => 'sgfixedline',
					'numbertype' => 'Fixed line',
					'billing_destination_type_id' => 2
				]),
				'6329-7537'
			],
			[
				$sg([
					'destination' => '658001206880',
					'fnn' => '8001206880',
					'type' => 'sgoneeight',
					'numbertype' => 'Toll free',
					'billing_destination_type_id' => 3
				]),
				'+65 800 120 6880'
			],
			[
				$sg([
					'destination' => '6531234567',
					'fnn' => '31234567',
					'type' => 'sgvoip',
					'numbertype' => 'VOIP',
					'billing_destination_type_id' => 6
				]),
				'31234567'
			],
		];
	}

	/**
	 * @group api_data_numberformat
	 * @dataProvider api_data_numberformat_singapore_data
	 * @param string $expected_value
	 * @param mixed  $destination
	 * @return void
	 */
	public function test_api_data_numberformat_singapore($expected_value, $destination) {
		$this->assertEquals($expected_value, api_data_numberformat($destination, 'SG'));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_numberformat_international_sms() {
		return [
			// Failures
			[ // destination is not a string
				false,
				'not a valid destination',
				'INTERNATIONAL_SMS'
			],
			[ // Not mobile France
				false,
				'+33954982245',
				'INTERNATIONAL_SMS'
			],
			// Success
			[ // Mobile France
				[
					'destination' => '33654982245',
					'fnn' => '0654982245',
					'type' => 'othermobile',
					'numbertype' => 'Mobile',
					'countryname' => 'Country',
					'billing_region_id' => 6,
					'billing_destination_type_id' => 1,
				],
				'+33654982245',
				'INTERNATIONAL_SMS'
			],
			[ // Mobile India
				[
					'destination' => '919605613349',
					'fnn' => '09605613349',
					'type' => 'othermobile',
					'numbertype' => 'Mobile',
					'countryname' => 'Country',
					'billing_region_id' => 6,
					'billing_destination_type_id' => 1,
				],
				'+91 9605613349',
				'INTERNATIONAL_SMS'
			],
			[  // Mobile Canada mobile_or_landline
				[
					"destination" => "15877076311",
					"fnn" => "5877076311",
					"type" => "othermobile",
					"numbertype" => "Mobile",
					"countryname" => "Country",
					"billing_region_id" => 6,
					"billing_destination_type_id" => 1
				],
				"+15877076311",
				'INTERNATIONAL_SMS'
			]
		];
	}

	/**
	 * @group api_data_numberformat
	 * @dataProvider api_data_numberformat_international_sms
	 * @param mixed  $expected_value
	 * @param string $destination
	 * @param string $region
	 * @return void
	 */
	public function test_api_data_numberformat_international_sms($expected_value, $destination, $region) {
		$this->mock_function_value('api_country_get_name', 'Country');
		$this->assertEquals($expected_value, api_data_numberformat($destination, $region));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_data_get_delimiters_data() {
		return [
			[[",", ";", "|", "\t"]],
			[["Comma", "Semicolon", "Pipe", "Tab"], true],
		];
	}

	/**
	 * @group api_data_get_delimiters
	 * @dataProvider  api_data_get_delimiters_data
	 * @param mixed   $expected_value
	 * @param boolean $ui
	 * @return void
	 */
	public function test_api_data_get_delimiters_data($expected_value, $ui = false) {
		$this->assertEquals($expected_value, api_data_get_delimiters($ui));
	}

	/**
	 * @return void
	 */
	public function test_api_data_responses_delete_by_targetid() {
		$targetid = 12345;
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_param_value(
			'api_db_query_write',
			[
				[
					'params' => [
						'DELETE from `response_data` where `targetid`=?',
						[$targetid]
					],
					'return' => true
				],
			],
			false
		);

		$this->assertTrue(api_data_responses_delete_by_targetid($targetid));
	}

	/**
	 * @return void
	 */
	public function test_api_data_callresult_delete_by_targetid() {
		$targetid = 12345;
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_param_value(
			'api_db_query_write',
			[
				[
					'params' => [
						'DELETE from `call_results` where `targetid`=?',
						[$targetid]
					],
					'return' => true
				],
			],
			false
		);

		$this->assertTrue(api_data_callresult_delete_by_targetid($targetid));
	}

	/**
	 * @return void
	 */
	public function test_api_data_merge_delete_by_targetkey() {
		$targetkey = 'test';
		$campaignid = 1234;
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_param_value(
			'api_db_query_write',
			[
				[
					'params' => [
						'DELETE from `merge_data` where `campaignid` = ? and `targetkey` = ?',
						[$campaignid, $targetkey]
					],
					'return' => true
				],
			],
			false
		);

		$this->assertTrue(api_data_merge_delete_by_targetkey($campaignid, $targetkey));
	}
}
