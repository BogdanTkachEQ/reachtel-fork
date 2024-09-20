<?php
/**
 * ApiBillingTransactionsUnitTest
 * Unit test for api_billing_transactions.php
 *
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

use Phake;
use Services\Utils\Billing\Channels;

/**
 * Class ApiBillingTransactionsUnitTest
 */
class ApiBillingTransactionsUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @group api_billing_transactions_fetch_products
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Start date can not be greater than end date
	 * @return void
	 */
	public function test_api_billing_transactions_fetch_products_throws_exception_for_invalid_dates() {
		$start = new \DateTime('+1 second');
		$end = new \DateTime();

		api_billing_transactions_fetch_products(1, $start, $end);
	}

	/**
	 * @group api_billing_transactions_fetch_products
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Invalid group id specified
	 * @return void
	 */
	public function test_api_billing_transactions_fetch_products_throws_exception_for_invalid_group() {
		$group_id = 123;
		$this->mock_function_param_value(
			'api_groups_checkidexists',
			[
				['params' => $group_id, 'return' => false]
			],
			true
		);
		$start = new \DateTime();
		$end = new \DateTime('+1 second');

		api_billing_transactions_fetch_products($group_id, $start, $end);
	}

	/**
	 * @return array
	 */
	public function api_billing_transactions_fetch_products_data_provider() {
		return [
			[
				// Invoice items
				[
					[
						'chargedate' => (new \DateTime('-1 minute'))->getTimestamp(),
						'type' => 25,
						'units' => 4,
						'itemname' => 'invoiceitem1',
					],
					[
						'chargedate' => (new \DateTime('+13 minute'))->getTimestamp(),
						'type' => 25,
						'units' => 3,
						'itemname' => 'invoiceitem2',
					],
					[
						'chargedate' => (new \DateTime('+15 minutes'))->getTimestamp(),
						'type' => 25,
						'units' => 7,
						'itemname' => 'invoiceitem3',
					],
					[
						'chargedate' => (new \DateTime('+18 minutes'))->getTimestamp(),
						'type' => 28,
						'units' => 4,
						'itemname' => 'invoiceitem4',
					],
					[
						'chargedate' => (new \DateTime('+2 days'))->getTimestamp(),
						'type' => 25,
						'units' => 5,
						'itemname' => 'invoiceitem5',
					]
				],

				//api email product config
				//  product_id
				[
					0 => 9
				],
				// api email rates
				[
					'username1' => 3,
					'username2' => 7,
					'username3' => 2
				],
				//sms product config
				// region_id => product_id
				[
					1 => 35,
					4 => 36,
					5 => 37,
					6 => 38,
					7 => 39,
					8 => 40
				],
				// sms api rates
				[
					6 => [
						'username1' => 3,
						'username2' => 4
					],
					7 => [
						'username2' => 7,
					],
					8 => [
						'username1' => 2
					]
				],

				//wash products config
				[
					['region_id' => 6, 'destination_type_id' => 1, 'product_id' => 45],
					['region_id' => 6, 'destination_type_id' => 2, 'product_id' => 46],
					['region_id' => 7, 'destination_type_id' => 1, 'product_id' => 47],
					['region_id' => 7, 'destination_type_id' => 2, 'product_id' => 48],
					['region_id' => 8, 'destination_type_id' => 1, 'product_id' => 49],
					['region_id' => 8, 'destination_type_id' => 2, 'product_id' => 50]
				],

				// wash api rates
				[
					6 => [
						1 => [
							'username1' => 3,
							'username2' => 4,
						],
						2 => [
							'username1' => 4
						]
					],
					7 => [
						2 => ['username1' => 7]
					]
				],

				// Phone products config
				[
					['region_id' => 6, 'destination_type_id' => 1, 'product_id' => 55, 'interval' => 'first'],
					['region_id' => 6, 'destination_type_id' => 1, 'product_id' => 55, 'interval' => 'next'],
					['region_id' => 6, 'destination_type_id' => 2, 'product_id' => 56, 'interval' => 'first'],
					['region_id' => 6, 'destination_type_id' => 2, 'product_id' => 56, 'interval' => 'next'],
					['region_id' => 8, 'destination_type_id' => 1, 'product_id' => 59, 'interval' => 'first'],
					['region_id' => 8, 'destination_type_id' => 1, 'product_id' => 59, 'interval' => 'next'],
					['region_id' => 8, 'destination_type_id' => 2, 'product_id' => 60, 'interval' => 'first'],
					['region_id' => 8, 'destination_type_id' => 2, 'product_id' => 60, 'interval' => 'next'],
				],

				// email products config
				[['product_id' => 85]],

				// api campaigns rate
				[
					20 => [
						'billinginfo' => [
							['region_id' => 1, 'units' => 3],
							['region_id' => 4, 'units' => 5],
							['region_id' => 5, 'units' => 7],
							['region_id' => 6, 'units' => 4],
						],
						'type' => 'sms',
						'name' => 'sms-campaign'
					],
					30 => [
						'billinginfo' => [
							['region_id' => 7, 'destination_type_id' => 1, 'units' => 1],
							['region_id' => 8, 'destination_type_id' => 1, 'units' => 1],
							['region_id' => 8, 'destination_type_id' => 2, 'units' => 1]
						],
						'type' => 'wash',
						'name' => 'wash-campaign'
					],
					40 => [
						'billinginfo' => [
							['units' => 1],
							['units' => 1]
						],
						'type' => 'email',
						'name' => 'email-campaign'
					],
					50 => [
						'billinginfo' => [
							[
								[
									'region_id' => 6,
									'destination_type_id' => 1,
									'units' => [
										'first' => 3,
										'next' => 8
									]
								],
							],
							[
								[
									'region_id' => 6,
									'destination_type_id' => 2,
									'units' => [
										'first' => 2,
										'next' => 19
									]
								]
							],
							[
								[
									'region_id' => 8,
									'destination_type_id' => 1,
									'units' => [
										'first' => 5,
										'next' => 11
									]
								]
							],
							[
								[
									'region_id' => 8,
									'destination_type_id' => 2,
									'units' => [
										'first' => 2,
										'next' => 5
									]
								]
							],
						],
						'type' => 'phone',
						'name' => 'phone-campaign'
					],
					60 => [
						'billinginfo' => [
							[
								[
									'region_id' => 6,
									'destination_type_id' => 1,
									'units' => [
										'first' => 3,
										'next' => 4
									]
								]
							],
							[
								[
									'region_id' => 8,
									'destination_type_id' => 2,
									'units' => [
										'first' => 4,
										'next' => 10
									]
								],
								[
									'region_id' => 8,
									'destination_type_id' => 2,
									'units' => [
										'first' => 3,
										'next' => 6
									]
								]
							],
						],
						'type' => 'phone',
						'name' => 'phone-campaign2'
					]
				],

				// expected
				[
					'adhoc_products' => [
						25 => [
							'units' => 10,
							'description' => 'invoiceitem2,invoiceitem3'
						],
						28 => [
							'units' => 4,
							'description' => 'invoiceitem4'
						]
					],
					'api_email_products' => [
						85 => [
							'username1' => 3,
							'username2' => 7,
							'username3' => 2,
						],
					],
					'api_wash_products' => [
						45 => [
							'username1' => 3,
							'username2' => 4,
						],
						46 => ['username1' => 4],
						48 => ['username1' => 7]
					],
					'api_sms_products' => [
						38 => [
							'username1' => 3,
							'username2' => 4
						],
						39 => ['username2' => 7],
						40 => ['username1' => 2]
					],
					'campaign_products' => [
						20 => [
							'name' => 'sms-campaign',
							'products' => [
								35 => 3,
								36 => 5,
								37 => 7,
								38 => 4
							]
						],
						30 => [
							'name' => 'wash-campaign',
							'products' => [
								47 => 1,
								49 => 1,
								50 => 1
							]
						],
						40 => [
							'name' => 'email-campaign',
							'products' => [
								85 => 2
							]
						],
						50 => [
							'name' => 'phone-campaign',
							'products' => [
								55 => 11,
								56 => 21,
								59 => 16,
								60 => 7
							]
						],
						60 => [
							'name' => 'phone-campaign2',
							'products' => [
								55 => 7,
								60 => 23
							]
						]
					]
				]
			]
		];
	}

	/**
	 * @group api_billing_transactions_fetch_products
	 * @dataProvider api_billing_transactions_fetch_products_data_provider
	 * @param array $invoiceitems
	 * @param array $email_api_products_config
	 * @param array $email_api_rates
	 * @param array $sms_products_config
	 * @param array $sms_api_rates
	 * @param array $wash_products_config
	 * @param array $wash_api_rates
	 * @param array $phone_products_config
	 * @param array $email_products_config
	 * @param array $api_campaigns_rate
	 * @param array $expected
	 * @return void
	 */
	public function test_api_billing_transactions_fetch_products(
		array $invoiceitems,
		array $email_api_products_config,
		array $email_api_rates,
		array $sms_products_config,
		array $sms_api_rates,
		array $wash_products_config,
		array $wash_api_rates,
		array $phone_products_config,
		array $email_products_config,
		array $api_campaigns_rate,
		array $expected
	) {
		if (!defined('DB_MYSQL_READ_HOST_FORCED')) {
			define('DB_MYSQL_READ_HOST_FORCED', 'read_host');
		}
		$group_id = 123;
		$this->mock_function_param_value(
			'api_groups_checkidexists',
			[
				['params' => $group_id, 'return' => true]
			],
			true
		);
		$start = new \DateTime();
		$end = new \DateTime('+1 day');
		$this->listen_mocked_function('api_db_switch_connection');
		$this->mock_function_param_value(
			'api_db_switch_connection',
			[
				['params' => [null, null, null, DB_MYSQL_READ_HOST_FORCED], 'return' => true]
			],
			true
		);

		$this->listen_mocked_function('api_db_reset_connection');
		$this->mock_function_value('api_db_reset_connection', true);

		$this->listen_mocked_function('api_db_ping');
		$this->mock_function_param_value(
			'api_db_ping',
			[
				['params' => [null, null, null, DB_MYSQL_READ_HOST_FORCED], 'return' => true]
			],
			true
		);

		$this->mock_function_param_value(
			'api_groups_setting_getsingle',
			[
				['params' => [$group_id, 'invoiceitems'], 'return' => serialize($invoiceitems)],
			],
			''
		);

		$this->mock_function_param_value(
			'api_campaigns_apirate',
			[
				['params' => [$start, $end, $group_id], 'return' => $sms_api_rates],
			],
			[]
		);

		$this->mock_function_param_value(
			'api_email_smtp_api_sendrate',
			[
				['params' => [$start, $end, $group_id], 'return' => $email_api_rates],
			],
			[]
		);

		$this->mock_function_value('api_billing_get_sms_products_config', $sms_products_config);

		$this->mock_function_param_value(
			'api_campaigns_washrate',
			[
				['params' => [$start, $end, $group_id], 'return' => $wash_api_rates]
			],
			[]
		);

		$this->mock_function_value('api_billing_get_wash_products_config', $wash_products_config);

		$this->mock_function_value('api_billing_get_phone_products_config', $phone_products_config);

		$this->mock_function_value('api_billing_get_email_products_config', $email_products_config);

		$this->mock_function_param_value(
			'api_campaigns_rate',
			[
				['params' => [$start, $end, $group_id], 'return' => $api_campaigns_rate]
			],
			[]
		);

		$products = api_billing_transactions_fetch_products($group_id, $start, $end);

		$this->assertListenMockFunction(
			'api_db_switch_connection',
			[
				['args' => [null, null, null, DB_MYSQL_READ_HOST_FORCED], 'return' => true]
			]
		);

		$this->assertListenMockFunctionHasBeenCalled('api_db_reset_connection', true, 1);
		$this->assertListenMockFunction(
			'api_db_ping',
			[
				['args' => [null, null, null, DB_MYSQL_READ_HOST_FORCED], 'return' => true],
				['args' => [null, null, null, DB_MYSQL_READ_HOST_FORCED], 'return' => true],
				['args' => [null, null, null, DB_MYSQL_READ_HOST_FORCED], 'return' => true],
				['args' => [null, null, null, DB_MYSQL_READ_HOST_FORCED], 'return' => true]
			]
		);
		$this->assertSameEquals($expected, $products);
	}

	/**
	 * @group api_billing_transactions_add_feed
	 * @return void
	 */
	public function test_api_billing_transactions_add_feed() {
		$products = [
			'adhoc_products' => [
				25 => [
					'units' => 10,
					'description' => 'invoiceitem2,invoiceitem3'
				],
				28 => [
					'units' => 4,
					'description' => 'invoiceitem4'
				]
			],
			'api_email_products' => [
				9 => [
					'username1' => 3,
					'username2' => 4,
				]
			],
			'api_wash_products' => [
				45 => [
					'username1' => 3,
					'username2' => 4,
				],
				46 => ['username1' => 4],
			],
			'api_sms_products' => [
				38 => [
					'username1' => 3,
					'username2' => 4
				],
				39 => ['username2' => 7],
			],
			'campaign_products' => [
				20 => [
					'name' => 'sms-campaign',
					'products' => [
						35 => 3,
						36 => 5,
						37 => 7,
						38 => 4
					]
				],
				30 => [
					'name' => 'wash-campaign',
					'products' => [
						47 => 1,
						49 => 1,
						50 => 1
					]
				]
			]
		];
		$group_id = 345;
		$run_id = 3;
		$start = new \DateTime();
		$end = new \DateTime('+1 day');
		$this->mock_function_param_value(
			'api_billing_transactions_fetch_products',
			[
				['params' => [$group_id, $start, $end], 'return' => $products]
			],
			[]
		);

		$expected = [
			[
				'billing_run_id' => 3,
				'product_id' => 45,
				'channel_name' => 'API',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => null,
				'quantity' => 3,
				'username' => 'username1',
			],
			[
				'billing_run_id' => 3,
				'product_id' => 45,
				'channel_name' => 'API',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => null,
				'quantity' => 4,
				'username' => 'username2',
			],
			[
				'billing_run_id' => 3,
				'product_id' => 46,
				'channel_name' => 'API',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => null,
				'quantity' => 4,
				'username' => 'username1',
			],
			[
				'billing_run_id' => 3,
				'product_id' => 38,
				'channel_name' => 'API',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => null,
				'quantity' => 3,
				'username' => 'username1',
			],
			[
				'billing_run_id' => 3,
				'product_id' => 38,
				'channel_name' => 'API',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => null,
				'quantity' => 4,
				'username' => 'username2',
			],
			[
				'billing_run_id' => 3,
				'product_id' => 39,
				'channel_name' => 'API',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => null,
				'quantity' => 7,
				'username' => 'username2',
			],
			[
				'billing_run_id' => 3,
				'product_id' => 9,
				'channel_name' => 'API',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => null,
				'quantity' => 3,
				'username' => 'username1',
			],
			[
				'billing_run_id' => 3,
				'product_id' => 9,
				'channel_name' => 'API',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => null,
				'quantity' => 4,
				'username' => 'username2',
			],
			[
				'billing_run_id' => 3,
				'product_id' => 25,
				'channel_name' => 'WEB',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => 'invoiceitem2,invoiceitem3',
				'quantity' => 10,
				'username' => null,
			],
			[
				'billing_run_id' => 3,
				'product_id' => 28,
				'channel_name' => 'WEB',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => 'invoiceitem4',
				'quantity' => 4,
				'username' => null,
			],
			[
				'billing_run_id' => 3,
				'product_id' => 35,
				'channel_name' => 'WEB',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => 'sms-campaign',
				'quantity' => 3,
				'username' => null,
			],
			[
				'billing_run_id' => 3,
				'product_id' => 36,
				'channel_name' => 'WEB',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => 'sms-campaign',
				'quantity' => 5,
				'username' => null,
			],
			[
				'billing_run_id' => 3,
				'product_id' => 37,
				'channel_name' => 'WEB',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => 'sms-campaign',
				'quantity' => 7,
				'username' => null,
			],
			[
				'billing_run_id' => 3,
				'product_id' => 38,
				'channel_name' => 'WEB',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => 'sms-campaign',
				'quantity' => 4,
				'username' => null,
			],
			[
				'billing_run_id' => 3,
				'product_id' => 47,
				'channel_name' => 'WEB',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => 'wash-campaign',
				'quantity' => 1,
				'username' => null,
			],
			[
				'billing_run_id' => 3,
				'product_id' => 49,
				'channel_name' => 'WEB',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => 'wash-campaign',
				'quantity' => 1,
				'username' => null,
			],
			[
				'billing_run_id' => 3,
				'product_id' => 50,
				'channel_name' => 'WEB',
				'group_id' => 345,
				'transaction_timestamp' => $start->format('Y-m-d H:i:s'),
				'subject' => 'wash-campaign',
				'quantity' => 1,
				'username' => null,
			],
		];
		$this->listen_mocked_function('api_billing_transactions_insert_feed');
		$this->mock_function_value('api_billing_transactions_insert_feed', true);
		api_billing_transactions_add_feed($run_id, $group_id, $start, $end);

		$called_params = $this->fetchListenedMockFunctionParamValues('api_billing_transactions_insert_feed');
		$args = $called_params[0]['args'];

		$this->assertSameEquals($expected, $args[0]);
		$this->assertInstanceOf(Channels::class, $args[1]);
	}

	/**
	 * @group api_billing_transactions_insert_feed
	 * @return void
	 */
	public function test_api_billing_transactions_insert_feed() {
		$feed = [
			[
				'billing_run_id' => 3,
				'product_id' => 49,
				'channel_name' => 'WEB',
				'group_id' => 345,
				'transaction_timestamp' => '2019-08-02 10:00:00',
				'subject' => 'wash-campaign',
				'quantity' => 1,
				'username' => null,
			],
			[
				'billing_run_id' => 3,
				'product_id' => 39,
				'channel_name' => 'API',
				'group_id' => 345,
				'transaction_timestamp' => '2019-08-04 10:00:00',
				'subject' => null,
				'quantity' => 7,
				'username' => 'username2',
			]
		];

		$sql = 'INSERT INTO `billing_transactions` 
		(`billing_product_id`, `billing_channel_id`, `group_id`, `transaction_timestamp`, `quantity`, `subject`, `billing_run_id`, `username`)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?),(?, ?, ?, ?, ?, ?, ?, ?)';
		$expected = [
			$sql,
			[
				49,
				123,
				345,
				'2019-08-02 10:00:00',
				1,
				'wash-campaign',
				3,
				null,
				39,
				321,
				345,
				'2019-08-04 10:00:00',
				7,
				null,
				3,
				'username2'
			]
		];

		$channel = Phake::mock(Channels::class);
		Phake::whenStatic($channel)->getChannelIdByName('WEB')->thenReturn(123);
		Phake::whenStatic($channel)->getChannelIdByName('API')->thenReturn(321);

		$this->remove_mocked_functions('api_db_query_write');
		$this->listen_mocked_function('api_db_query_write');
		$this->mock_function_value('api_db_query_write', true);
		$this->assertTrue(api_billing_transactions_insert_feed($feed, $channel));
		$called_params = $this->fetchListenedMockFunctionParamValues('api_db_query_write');
		$this->assertSameEquals($expected, $called_params[0]['args']);
	}

	/**
	 * @return array
	 */
	public function api_billing_transactions_has_billing_run_for_the_day_data() {
		return [
			'when date is null it uses today\'s date' => [
				null,
				[1],
				true,
				[
					(new \DateTime())->format('Y-m-d 00:00:00'),
					(new \DateTime())->format('Y-m-d 23:59:59'),
					1
				]
			],
			'when date is passed it uses the passed date' => [
				new \DateTime('yesterday'),
				[2],
				true,
				[
					(new \DateTime('yesterday'))->format('Y-m-d 00:00:00'),
					(new \DateTime('yesterday'))->format('Y-m-d 23:59:59'),
					1
				]
			],
			'when no records are returned the function returns false' => [
				new \DateTime('tomorrow'),
				[],
				false,
				[
					(new \DateTime('tomorrow'))->format('Y-m-d 00:00:00'),
					(new \DateTime('tomorrow'))->format('Y-m-d 23:59:59'),
					1
				]
			]
		];
	}

	/**
	 * @group api_billing_transactions_has_billing_run_for_the_day
	 * @dataProvider api_billing_transactions_has_billing_run_for_the_day_data
	 * @param \DateTime $date
	 * @param array     $records
	 * @param boolean   $expected
	 * @param array     $expected_sql_params
	 * @return void
	 */
	public function test_api_billing_transactions_has_billing_run_for_the_day(
		\DateTime $date = null,
		array  $records,
		$expected,
		array $expected_sql_params
	) {
		$sql = 'SELECT * FROM `billing_runs` WHERE `billing_period_start` >= ? AND `billing_period_end` <= ? AND `status` = ? limit 1';

		$this->remove_mocked_functions('api_db_query_read');
		$this->listen_mocked_function('api_db_query_read');

		// Query actually fetches a different data set, however we are just asserting the count and so
		// the data set does not matter.
		$this->mock_function_value('api_db_query_read', $this->mock_ado_records($records));
		$this->assertSameEquals($expected, api_billing_transactions_has_billing_run_for_the_day($date));
		$called_params = $this->fetchListenedMockFunctionParamValues('api_db_query_read');

		$this->assertSameEquals(
			[$sql, $expected_sql_params],
			$called_params[0]['args']
		);
	}

	/**
	 * @group api_billing_transactions_create_billing_run
	 * @return void
	 */
	public function test_api_billing_transactions_create_billing_run() {
		$start_time = '2019-08-10 01:00:00';
		$end_time = '2019-08-10 10:00:00';
		$start = \DateTime::createFromFormat('Y-m-d H:i:s', $start_time);
		$end = \DateTime::createFromFormat('Y-m-d H:i:s', $end_time);

		$this->remove_mocked_functions('api_db_query_write');
		$this->listen_mocked_function('api_db_query_write');
		$sql = 'INSERT INTO `billing_runs` (`status`, `billing_period_start`, `billing_period_end`) VALUES (?, ?, ?)';
		$expected_params = [$sql, [0, $start_time, $end_time]];
		$this->mock_function_param_value(
			'api_db_query_write',
			[
				['params' => $expected_params, 'return' => true]
			],
			false
		);

		$last_id = 12;
		$this->mock_function_value('api_db_lastid', $last_id);

		$this->assertSameEquals($last_id, api_billing_transactions_create_billing_run($start, $end));

		$called_params = $this->fetchListenedMockFunctionParamValues('api_db_query_write');

		$this->assertSameEquals($expected_params, $called_params[0]['args']);
	}

	/**
	 * @group api_billing_transactions_complete_billing_run
	 * @return void
	 */
	public function test_api_billing_transactions_complete_billing_run() {
		$sql = 'UPDATE `billing_runs` set `status` = ?, `errors` = ? WHERE `id` = ?';
		$this->remove_mocked_functions('api_db_query_write');

		$this->listen_mocked_function('api_db_query_write');
		$this->mock_function_value('api_db_query_write', true);

		$run_id = 43;
		$errors = 4;
		api_billing_transactions_complete_billing_run($run_id, $errors);

		$called_params = $this->fetchListenedMockFunctionParamValues('api_db_query_write');
		$this->assertSameEquals([$sql, [1, $errors, $run_id]], $called_params[0]['args']);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Error while setting billing run id 23 to complete
	 * @return void
	 */
	public function test_api_billing_transactions_complete_billing_run_throws_exception() {
		$this->remove_mocked_functions('api_db_query_write');
		$this->mock_function_value('api_db_query_write', false);
		api_billing_transactions_complete_billing_run(23, 34);
	}

	/**
	 * @group api_billing_transactions_run_billing
	 * @return void
	 */
	public function test_api_billing_transactions_run_billing() {
		$start = Phake::mock(\DateTime::class);
		$end = Phake::mock(\DateTime::class);

		$run_id = 78;

		$this->mock_function_param_value(
			'api_billing_transactions_create_billing_run',
			[
				['params' => [$start, $end], 'return' => $run_id]
			],
			0
		);

		$this->mock_function_param_value(
			'api_billing_transactions_add_feed',
			[
				['params' => [$run_id, 3, $start, $end], 'return' => true],
				['params' => [$run_id, 4, $start, $end], 'return' => false],
				['params' => [$run_id, 5, $start, $end], 'return' => true],
			]
		);

		$this->listen_mocked_function('api_billing_transactions_complete_billing_run');
		$this->mock_function_value('api_billing_transactions_complete_billing_run', true);
		$this->assertSameEquals($run_id, api_billing_transactions_run_billing([3, 4, 5], $start, $end));
		$called_params = $this->fetchListenedMockFunctionParamValues('api_billing_transactions_complete_billing_run');
		$this->assertSameEquals([$run_id, 1], $called_params[0]['args']);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Billing transactions export can not be performed as SELCOMM_ACCOUNT_CODE_PREFIX is not configured.
	 * @return void
	 */
	public function test_api_billing_transactions_selcomm_export_throws_selcom_prefix_exception() {
		$this->mock_function_param_value(
			'defined',
			[
				['params' => 'SELCOMM_ACCOUNT_CODE_PREFIX', 'return' => false]
			],
			false
		);

		api_billing_transactions_selcomm_export([123], '/tmp/test.dat');
	}

	/**
	 * @return array
	 */
	public function api_billing_transactions_selcomm_export_data() {
		return [
			'when nothing fails' => [false, false, null],
			'when it fails at fetching transactions from the table' => [true, false, 'Some thing went wrong with the sql to export transaction data'],
			'when it fails at updating export file with the csv data' => [false, true, 'Error occurred during export of transaction data to the file path']
		];
	}

	/**
	 * @param boolean $failed_transaction_data_retrieve
	 * @param boolean $failed_file_update
	 * @param string  $exception_message
	 * @dataProvider api_billing_transactions_selcomm_export_data
	 * @throws \Exception Some thing went wrong with the sql to export transaction data.
	 * @throws \Exception Error occurred during export of transaction data to the file path.
	 * @return void
	 */
	public function test_api_billing_transactions_selcomm_export(
		$failed_transaction_data_retrieve = false,
		$failed_file_update = false,
		$exception_message = null
	) {
		if (!defined('SELCOMM_ACCOUNT_CODE_PREFIX')) {
			define('SELCOMM_ACCOUNT_CODE_PREFIX', 'RETL');
		}

		$sql = 'SELECT CONCAT("RETL", t.group_id) AS `Account_Code`, p.`code` AS `Product_Code`, 
		c.`code` AS `Channel_Code`, DATE_FORMAT(t.`transaction_timestamp`, "%d/%m/%Y") AS `Transaction_Date`, 
		DATE_FORMAT(t.`transaction_timestamp`, "%H:%i:%s") AS `Transaction_Time`, "" AS `Third_Party_Cost`,
		"" AS `Enquiry_Purpose_Code`, "" AS `Matched`, "" AS `Job_Number`, COALESCE(t.`subject`, "") AS `Subject`,
		"" AS `File_Number`, "" AS `User_Id`, COALESCE(t.`username`, "") AS `User_Name`, t.`quantity` AS `Quantity`,
		COALESCE(t.`client_defined1`, "") AS `Client_Defined_Field_1`, "" AS `Client_Defined_Field_2`, "" AS `Search_Criteria`,
		t.`id` AS `Transaction_reference`, "REACHTEL" AS `Source`, "" AS `IA_Portfolio_Name`
		FROM `billing_transactions` t JOIN billing_channels c ON (c.`id`=t.`billing_channel_id`) 
		JOIN `billing_products` p ON (p.`id`=t.`billing_product_id`) WHERE t.billing_run_id IN (?,?,?)';

		$run_ids = [59, 89, 12];
		$filename = '\tmp\test.dat';
		$records = [
			['account_code' => 'RTEL1', 'product_code' => 1, 'channel_code' => 3],
			['account_code' => 'RTEL2', 'product_code' => 2, 'channel_code' => 1]
		];

		$csv_data = "account_code|product_code|channel_code\nRTEL1|1|3\nRTEL2|2|1";

		$this->remove_mocked_functions('api_db_query_read');
		$this->listen_mocked_function('api_db_query_read');
		$this->mock_function_param_value(
			'api_db_query_read',
			[
				[
					'params' => [$sql, $run_ids],
					'return' => $failed_transaction_data_retrieve ? false : $this->mock_ado_records($records)
				]
			],
			false
		);

		if (!$failed_transaction_data_retrieve) {
			// When it fails at transaction data fetch it does not proceed further and so there
			// is no point mocking further.
			$this->listen_mocked_function('file_put_contents');
			$this->listen_mocked_function('api_csv_string');
			array_unshift($records, array_keys($records[0]));
			$this->mock_function_param_value(
				'api_csv_string',
				[
					['params' => [$records, '|', chr(0)], 'return' => $csv_data]
				],
				[]
			);

			$this->mock_function_value(
				'file_put_contents',
				!$failed_file_update
			);

			if (!$failed_file_update) {
				// When it fails to update file it does not proceed further and so there
				// is no point mocking further.
				$this->remove_mocked_functions('api_db_query_write');
				$this->listen_mocked_function('api_db_query_write');
				$this->mock_function_value('api_db_query_write', true);
			}
		}

		if ($exception_message) {
			$this->expectException(\Exception::class);
			$this->expectExceptionMessage($exception_message);
		}

		$assertion_callback = function() use (
			$failed_transaction_data_retrieve,
			$failed_file_update,
			$sql,
			$run_ids,
			$filename,
			$csv_data,
			$records
		) {
			$params = $this->fetchListenedMockFunctionParamValues('api_db_query_read');
			$this->assertSameEquals([$sql, $run_ids], $params[0]['args']);

			if (!$failed_transaction_data_retrieve) {
				$params = $this->fetchListenedMockFunctionParamValues('api_csv_string');
				$this->assertSameEquals([$records, '|', chr(0)], $params[0]['args']);

				$params = $this->fetchListenedMockFunctionParamValues('file_put_contents');
				$this->assertSameEquals([$filename, $csv_data], $params[0]['args']);

				if (!$failed_file_update) {
					$update_sql = 'UPDATE `billing_transactions` SET `processed`="y" WHERE `billing_run_id` IN (?,?,?)';
					$params = $this->fetchListenedMockFunctionParamValues('api_db_query_write');
					$this->assertSameEquals([$update_sql, $run_ids], $params[0]['args']);
				}
			}
		};

		try {
			$return = api_billing_transactions_selcomm_export($run_ids, $filename);
		} catch (\Exception $e) {
			$assertion_callback();
			throw $e;
		}
		$this->assertTrue($return);
		$assertion_callback();
	}
}
