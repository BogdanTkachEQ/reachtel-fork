<?php
/**
 * TransurbanTest
 * Unit test for script transurban-sftp.php
 *
 * @author      kevin.ohayon@reachtel.com.au
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\script;

use testing\unit\AbstractPhpunitUnitTest;

/**
 * TransurbanUnitTest
 *
 * @runTestsInSeparateProcesses
 */
class TransurbanUnitTest extends AbstractPhpunitUnitTest
{
	const GLOBAL_EMAIL_KEY = 'transurban_email';
	const GLOBAL_SFTP_KEY = 'transurban_sftp';
	const MOCK_TARGET_LTI_ACCOUNT_ID = '333333';

	/** @var string */
	private $filename;

	/**
	 * Clean transurban tmp directory
	 *
	 * @return void
	 */
	public function setUp() {
		// remove all previous sema files
		array_map('unlink', glob('/tmp/LTIs*.csv'));
		array_map('unlink', glob('/tmp/Upload_MAIL_TPT_*.csv'));

		parent::setUp();
	}

	/**
	 * Remove transurban uploaded file
	 *
	 * @return void
	 */
	public function tearDown() {
		if ($this->filename && is_file($this->filename)) {
			unlink($this->filename);
		}

		parent::tearDown();
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function data_provider() {
		$date_dmY = date('d.m.Y');
		$date_Ymd = date('Y.m.d');
		$date_jFy = date('jFy');

		$VESMobileLandline = [
			'LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1'
		];

		// Abstract Retail OR VES (Retail if both) function generator test
		$getVESorRETAILTests = function($LITAge) use ($date_dmY, $date_Ymd, $date_jFy) {
			$data = [
				"Case {$LITAge}-RETAIL Available contact method R_PRIM_EMAIL found" => [
					// input CSV
					$this->generate_csv(
						[
							'LTI_AGE' => $LITAge,
							'REPORT_TYPE' => 'RETAIL',
							'R_PRIM_EMAIL' => 'transurban@phpunit',
							//this LTI_ACCOUNT_ID will add the target
							'LTI_ACCOUNT_ID' => self::MOCK_TARGET_LTI_ACCOUNT_ID,
						]
					),
					// options
					[
						// campaigns returned by api_campaigns_list_all()
						'campaigns_list' => [1 => 'Previous-Campaign-Name'],
					],
					// expected console outputs
					[
						"Row 000001 | {$LITAge}-RETAIL | Added target for column 'R_PRIM_EMAIL'.\n",
						"Finalising campaigns...\nTransurban-Linkt-{$date_jFy}-Email-Com11R (100)",
						'Campaign not activated.',
					],
					// expected email subject => [contents, contents ...]
					[
						'Transurban Data File Load Report' => [
							'We have received a data file and processing is now complete',
							'Total Records: 1',
							'Imported Records: 1',
							'Campaigns Generated: 1',
							'Files Generated: 0',
						]
					],
				],
				"Case {$LITAge}-RETAIL NONE available contact method (SEMA)" => [
					// input CSV
					$this->generate_csv(
						[
							'LTI_AGE' => $LITAge,
							'REPORT_TYPE' => 'RETAIL',
							'R_PRIM_EMAIL' => 'transurban@phpunit',
						]
					),
					// options
					[
						// campaigns returned by api_campaigns_list_all()
						'campaigns_list' => [1 => 'Previous-Campaign-Name'],
					],
					// expected console outputs
					[
						"Row 000001 | {$LITAge}-RETAIL |",
						'Added in SEMA file ' . ($sema = "/tmp/LTIs-{$date_dmY}-L2-Retail.csv"),
						'Added in Bulk file ' . ($bulk = "/tmp/Upload_MAIL_TPT_060_{$date_Ymd}.csv"),
						"Finalising campaigns...\nTransurban-Linkt-{$date_jFy}-Email-Com11R (100)",
						'Campaign not activated.',
						],
						// expected email subject => [contents, contents ...]
					[
						'Transurban Data File Load Report' => [
							'We have received a data file and processing is now complete',
							'Total Records: 1',
							'Imported Records: 1',
							'Campaigns Generated: 1',
							'Files Generated: 2',
						]
					],
					[$sema, $bulk]
				],
				"Case {$LITAge}-VES Available contact method LTI_EMAIL found" => [
					// input CSV
					$this->generate_csv(
						[
							'LTI_AGE' => $LITAge,
							'LTI_EMAIL' => 'transurban@phpunit',
							//	this LTI_ACCOUNT_ID will add the target
							'LTI_ACCOUNT_ID' => self::MOCK_TARGET_LTI_ACCOUNT_ID,
						]
					),
					// options
					[
						// campaigns returned by api_campaigns_list_all()
						'campaigns_list' => [1 => 'Previous-Campaign-Name'],
					],
					// expected console outputs
					[
						"Row 000001 |    {$LITAge}-VES | Added target for column 'LTI_EMAIL'.\n",
						"Finalising campaigns...\nTransurban-Linkt-{$date_jFy}-Email-Com11V (100)",
						'Campaign not activated.',
					],
						// expected email subject => [contents, contents ...]
					[
						'Transurban Data File Load Report' => [
							'We have received a data file and processing is now complete',
							'Total Records: 1',
							'Imported Records: 1',
							'Campaigns Generated: 1',
							'Files Generated: 0',
						]
					],
				],
				"Case {$LITAge}-VES NONE available contact method (SEMA)" => [
					// input CSV
					$this->generate_csv(
						[
							'LTI_AGE' => $LITAge,
							'R_PRIM_EMAIL' => 'transurban@phpunit',
						]
					),
					// options
					[
						// campaigns returned by api_campaigns_list_all()
						'campaigns_list' => [1 => 'Previous-Campaign-Name'],
					],
					// expected console outputs
					[
						"Row 000001 |    {$LITAge}-VES |",
						'Added in SEMA file ' . ($sema = "/tmp/LTIs-{$date_dmY}-L2-VES.csv"),
						'Added in Bulk file ' . ($bulk = "/tmp/Upload_MAIL_TPT_099_{$date_Ymd}.csv"),
						"Finalising campaigns...\nTransurban-Linkt-{$date_jFy}-Email-Com11V (100)",
						'Campaign not activated.',
						],
						// expected email subject => [contents, contents ...]
					[
						'Transurban Data File Load Report' => [
							'We have received a data file and processing is now complete',
							'Total Records: 1',
							'Imported Records: 1',
							'Campaigns Generated: 1',
							'Files Generated: 2',
						]
					],
					[$sema, $bulk]
				],
			];

			return $data;
		};

		// Abstract VESRETAIL function generator test
		$getVESRETAILTests = function($LITAge) use ($VESMobileLandline) {
			$data = [
				// failure tests
				"Case {$LITAge}-RETAIL Available contact method R_PRIM_EMAIL is not listed: Column not added." => [
					// input CSV
					$this->generate_csv(
						[
							'LTI_AGE' => $LITAge,
							'REPORT_TYPE' => 'RETAIL',
							'R_PRIM_EMAIL' => 'transurban@phpunit',
							//this LTI_ACCOUNT_ID will add the target
							'LTI_ACCOUNT_ID' => self::MOCK_TARGET_LTI_ACCOUNT_ID,
						]
					),
					// options
					[
						// campaigns returned by api_campaigns_list_all()
						'campaigns_list' => [1 => 'Previous-Campaign-Name'],
					],
					// expected console outputs
					[
						"Row 000001 | {$LITAge}-RETAIL | \n",
						],
						// expected email subject => [contents, contents ...]
					[
						'Transurban Data File Load Report' => [
							'We have received a data file and processing is now complete',
							'Total Records: 1',
							'Imported Records: 1',
							'Campaigns Generated: 1',
							'Files Generated: 0',
						]
					],
				],
			];

			// success tests
			foreach ($VESMobileLandline as $column) {
				$data["Case {$LITAge}-RETAIL Available contact method {$column} found"] = [
					// input CSV
					$this->generate_csv(
						[
						'LTI_AGE' => $LITAge,
						'REPORT_TYPE' => 'RETAIL',
						($column) => 'transurban@phpunit',
						//this LTI_ACCOUNT_ID will add the target
						'LTI_ACCOUNT_ID' => self::MOCK_TARGET_LTI_ACCOUNT_ID,
						]
					),
					// options
					[
					// campaigns returned by api_campaigns_list_all()
						'campaigns_list' => [1 => 'Previous-Campaign-Name'],
					],
					// expected console outputs
					[
						"Row 000001 | {$LITAge}-RETAIL | Added target for column '{$column}'.\n",
					],
					// expected email subject => [contents, contents ...]
					[
						'Transurban Data File Load Report' => [
							'We have received a data file and processing is now complete',
							'Total Records: 1',
							'Imported Records: 1',
							'Campaigns Generated: 1',
							'Files Generated: 0',
						]
					],
				];
			}

			return $data;
		};

		return array_merge(
			// FAILURES
			[
				'No action required for this age' => [
					// input CSV
					$this->generate_csv(['LTI_AGE' => 20]),
					// options
					[],
					// expected console outputs
					[
						'Row 000001 |     20-VES | No action defined, row ignored',
					],
					// expected email subject => [contents, contents ...]
					[
						'Transurban Data File Load Report' => [
							'We have received a data file and processing is now complete',
							'Total Records: 1',
							'Imported Records: 0',
							'Campaigns Generated: 0',
							'Files Generated: 0',
						]
					],
				],
				'No previous campaigns exists' => [
					// input CSV
					$this->generate_csv(['LTI_AGE' => 43]),
					// options
					[],
					// expected console outputs
					[
						'Row 000001 |     43-VES | Added in SEMA file',
						'Added in SEMA file ' . ($sema = "/tmp/LTIs-{$date_dmY}-L3-VES.csv"),
						'Added in Bulk file ' . ($bulk = "/tmp/Upload_MAIL_TPT_099_{$date_Ymd}.csv"),
					],
					// expected email subject => [contents, contents ...]
					[
						'Transurban Data File Load Report' => [
							'We have received a data file and processing is now complete',
							'Total Records: 1',
							'Imported Records: 1',
							'Campaigns Generated: 0',
							'Files Generated: 2',
						]
					],
					// expected SFTP local files regular expression (# delimiter)
					[$sema, $bulk],
				],
				'Previous campaigns groupowner does not match' => [
					// input CSV
					$this->generate_csv(['LTI_AGE' => 43]),
					// options
					[
						// campaigns returned by api_campaigns_list_all()
						'campaigns_list' => [1 => 'Previous-Campaign-Name'],
						// campaign groupowner
						'campaigns_groupowner' => 1,
					],
					// expected console outputs
					[
						'Row 000001 |     43-VES | Added in SEMA file',
						'Added in SEMA file ' . ($sema = "/tmp/LTIs-{$date_dmY}-L3-VES.csv"),
						'Added in Bulk file ' . ($bulk = "/tmp/Upload_MAIL_TPT_099_{$date_Ymd}.csv"),
					],
					// expected email subject => [contents, contents ...]
					[
						'Transurban Data File Load Report' => [
							'We have received a data file and processing is now complete',
							'Total Records: 1',
							'Imported Records: 1',
							'Campaigns Generated: 0',
							'Files Generated: 2',
						]
					],
					// expected SFTP local files regular expression (# delimiter)
					[$sema, $bulk],
				],
				'Create new campaign failed' => [
					// input CSV
					$this->generate_csv(['LTI_AGE' => 43]),
					// options
					[
						// campaigns returned by api_campaigns_list_all()
						'campaigns_list' => [1 => 'Previous-Campaign-Name'],
						// campaign groupowner
						'new_campaign_id' => false,
					],
					// expected console outputs
					[
						'Row 000001 |     43-VES | Added in SEMA file',
						'Added in SEMA file ' . ($sema = "/tmp/LTIs-{$date_dmY}-L3-VES.csv"),
						'Added in Bulk file ' . ($bulk = "/tmp/Upload_MAIL_TPT_099_{$date_Ymd}.csv"),
					],
					// expected email subject => [contents, contents ...]
					[
						'Transurban Data File Load Report' => [
							'We have received a data file and processing is now complete',
							'Total Records: 1',
							'Imported Records: 1',
							'Campaigns Generated: 0',
							'Files Generated: 2',
						]
					],
					// expected SFTP local files regular expression (# delimiter)
					[$sema, $bulk],
				],
				'when there is 43C along with another older age it should process both' => [
					// input CSV
					array_merge(
						$this->generate_csv(
							[
								'LTI_AGE' => '152',
								'REPORT_TYPE' => 'RETAIL',
								'R_PRIM_EMAIL' => 'transurban@phpunit',
								//this LTI_ACCOUNT_ID will add the target
								'LTI_ACCOUNT_ID' => self::MOCK_TARGET_LTI_ACCOUNT_ID,
							]
						),
						[
							$this->generate_csv(
								[
									'LTI_AGE' => '43C',
									'REPORT_TYPE' => 'RETAIL',
									'R_PRIM_EMAIL' => 'transurban@phpunit',
									//this LTI_ACCOUNT_ID will add the target
									'LTI_ACCOUNT_ID' => self::MOCK_TARGET_LTI_ACCOUNT_ID,
								]
							)[1]
						]
					),
					// options
					[
						// campaigns returned by api_campaigns_list_all()
						'campaigns_list' => [1 => 'Previous-Campaign-Name'],
					],
					// expected console outputs
					[
						"Row 000002 | 43C-RETAIL | Added target for column 'R_PRIM_EMAIL'.\n",
						"Row 000001 | 152-RETAIL | Added target for column 'R_PRIM_EMAIL'.\n",
					],
					// expected email subject => [contents, contents ...]
					[
						'Transurban Data File Load Report' => [
							'We have received a data file and processing is now complete',
							'Total Records: 2',
							'Imported Records: 2',
							'Campaigns Generated: 3',
							'Files Generated: 0',
						]
					],
				],
				'It should only process the oldest debt when there are multiple debts other than 43C' => [
					// input CSV
					array_merge(
						$this->generate_csv(
							[
								'LTI_AGE' => '75',
								'REPORT_TYPE' => 'VES.1',
								'LTI_EMAIL' => 'transurban@phpunit',
								//this LTI_ACCOUNT_ID will add the target
								'LTI_ACCOUNT_ID' => self::MOCK_TARGET_LTI_ACCOUNT_ID,
							]
						),
						[
							$this->generate_csv(
								[
									'LTI_AGE' => '89',
									'REPORT_TYPE' => 'VES.1',
									'LTI_EMAIL' => 'transurban@phpunit',
									//this LTI_ACCOUNT_ID will add the target
									'LTI_ACCOUNT_ID' => self::MOCK_TARGET_LTI_ACCOUNT_ID,
								]
							)[1]
						]
					),
					// options
					[
						// campaigns returned by api_campaigns_list_all()
						'campaigns_list' => [1 => 'Previous-Campaign-Name'],
					],
					// expected console outputs
					[
						"Row 000002 |     89-VES | Added target for column 'LTI_EMAIL'.\n",
					],
					// expected email subject => [contents, contents ...]
					[
						'Transurban Data File Load Report' => [
							'We have received a data file and processing is now complete',
							'Total Records: 2',
							'Imported Records: 2',
							'Campaigns Generated: 2',
							'Files Generated: 0',
						]
					],
				]
			],
			// test VES or RETAILtests for LTI Age 152
			$getVESorRETAILTests(152),
			// test all VESRETAIL tests for LTI Age 149
			$getVESRETAILTests(149)
		);
	}

	/**
	 * @dataProvider data_provider
	 * @param string $csv
	 * @param array  $options
	 * @param array  $expectedOutputs
	 * @param array  $expectedEmails
	 * @param array  $expectedSFTPFiles
	 * @return void
	 */
	public function test_script($csv, array $options, array $expectedOutputs, array $expectedEmails = [], array $expectedSFTPFiles = []) {
		$this->filename = sys_get_temp_dir() . '/' . uniqid('transurban_test_') . '.csv';
		$this->assertGreaterThan(0, api_csv_file($this->filename, $csv));

		// cron tags mocks
		$this->mock_function_value(
			'api_cron_tags_get',
			array_merge(
				[
					'filename' => $this->filename,
					'reporting-destination' => 'reporting@phpunit',
					// sftp
					'sftp-out-hostname' => 'FAKE_SFTP',
					'sftp-out-username' => 'FAKE_SFTP',
					'sftp-out-password' => 'FAKE_SFTP',
					'stp-failure-notification' => 'sftp_error@phpunit',
					'sftp-out-path' => 'FAKE_SFTP/',
				],
				(isset($options['tags']) && $options['tags'] ? $options['tags'] : [])
			)
		);
		$this->mock_function_value('api_cron_tags_delete', true);

		// sftp mocks (set sftp in global var to assert it later)
		$this->mock_function_value(
			'api_misc_sftp_put',
			sprintf('$GLOBALS["%s"][] = func_get_arg(0); return true;', self::GLOBAL_SFTP_KEY),
			true
		);

		// email mocks (set email in global var to assert it later)
		$this->mock_function_value(
			'api_email_template',
			sprintf('$GLOBALS["%s"][] = func_get_arg(0);', self::GLOBAL_EMAIL_KEY),
			true
		);

		// campaigns mocks
		$this->mock_function_param_value(
			'api_campaigns_list_all',
			[
			],
			(isset($options['campaigns_list']) && $options['campaigns_list'] ? $options['campaigns_list'] : [])
		);
		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				// campaign groupowner
				['params' => [1 => 'groupowner'], 'return' => (isset($options['campaigns_groupowner']) ? $options['campaigns_groupowner'] : 735)],
				// campaign status
				['params' => [1 => 'status'], 'return' => false],
			],
			false
		);
		$this->mock_function_value(
			'api_campaigns_checkorcreate',
			(isset($options['new_campaign_id']) ? $options['new_campaign_id'] : 100)
		);
		$this->mock_function_value(
			'api_data_target_status',
			[
				'READY' => (isset($options['targets_total']) ? $options['targets_total'] : 5),
				'TOTAL' => (isset($options['targets_total']) ? $options['targets_total'] : 5),
			]
		);
		$this->mock_function_value('api_campaigns_delete', true);

		// targets mock
		$this->mock_function_param_value(
			'api_targets_add_single',
			[
				// if LTI_ACCOUNT_ID = 333333, we add target
				[
					'params' => [2 => self::MOCK_TARGET_LTI_ACCOUNT_ID],
					'return' => self::MOCK_TARGET_LTI_ACCOUNT_ID
				],
			],
			false
		);

		// disable depup
		$this->mock_function_value('api_campaigns_nametoid', false);
		$this->mock_function_value('api_targets_dedupe', false);

		// catch script output
		ob_start();
		// isolate script
		$this->run_script();
		$output = ob_get_contents();
		ob_end_clean();

		// remove mocked functions for safety
		$this->remove_mocked_functions();

		// assert output
		foreach ($expectedOutputs as $expectedOutput) {
			$this->assertContains($expectedOutput, $output);
		}

		// assert email sent
		$emails = isset($GLOBALS[self::GLOBAL_EMAIL_KEY]) ? $GLOBALS[self::GLOBAL_EMAIL_KEY] : [];
		$this->assertCount(
			count($expectedEmails),
			$emails,
			'Email sent count:'
		);
		$i = 0;
		foreach ($expectedEmails as $subject => $contents) {
			$this->assertArrayHasKey('subject', $emails[$i], 'Email subject key not found:');
			$this->assertArrayHasKey('textcontent', $emails[$i], 'Email textcontent key not found:');

			$this->assertContains((string) $subject, $emails[$i]['subject'], 'Email subject match error:');

			foreach ((array) $contents as $content) {
				$this->assertContains((string) $content, $emails[$i]['textcontent'], 'Email textcontent match error:');
			}
			$i++;
		}

		// assert SFTP
		$sftp = isset($GLOBALS[self::GLOBAL_SFTP_KEY]) ? $GLOBALS[self::GLOBAL_SFTP_KEY] : [];
		$this->assertCount(
			count($expectedSFTPFiles),
			$sftp,
			'SFTP files count:'
		);
		foreach ($expectedSFTPFiles as $i => $expectedSFTPFile) {
			$this->assertArrayHasKey($i, $sftp, 'SFTP array key error:');
			$this->assertArrayHasKey('localfile', $sftp[$i], 'SFTP key localfile error:');
			// # regexp delimiter
			$this->assertRegExp("#{$expectedSFTPFile}#", $sftp[$i]['localfile'], 'SFTP localfile regexp match error:');
		}
	}

	/**
	 * @param array $overrides
	 * @return array
	 */
	private function generate_csv(array $overrides = []) {
		$data = array_merge(
			[
				'REPORT_TYPE' => 'VES.1',
				'LTI_AGE' => 43,
				'LTI_PER_ID' => 5,
				'R_PRIM_PERSON_ID' => 6,
				'LTI_ACCOUNT_ID' => 123456,
				'R_ACCOUNT_ID' => 789111,
				'LTI_FIRST_NAME' => 'VES Firstname',
				'R_PRIM_FIRST_NAME' => 'RETAIL Firstname',
				'LTI_LAST_NAME' => 'VES Lastname',
				'R_PRIM_LAST_NAME' => 'RETAIL Lastname',
				'LTI_CURR_BALANCE' => 120.32,
				'LTI_LPN' => 'lpn',
				'LTI_ADDRESS1' => '23 VES Street',
				'R_MAILING_ADDRESS1' => '23 RETAIL Street',
				'LTI_ADDRESS2' => '',
				'R_MAILING_ADDRESS2' => '',
				'LTI_CITY' => 'VES City',
				'R_MAILING_CITY' => 'RETAIL City',
				'LTI_STATE' => 'VES STATE',
				'R_MAILING_STATE' => 'RETAIL STATE',
				'LTI_POSTCODE' => 1234,
				'R_MAILING_POSTCODE' => 5678,
			],
			$overrides
		);

		return [array_keys($data), array_values($data)];
	}

	/**
	 * @return void
	 */
	private function run_script() {
		$argv[2] = 'local'; // 2nd arg hack
		include APP_ROOT_PATH . '/scripts/autoload/transurban-sftp.php';
	}
}
