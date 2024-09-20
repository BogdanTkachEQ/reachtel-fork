<?php
/**
 * ApiQueueTest
 * Unit test for api_queue.php
 *
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

use testing\AbstractPhpunitTest;

/**
 * Class ApiQueueUnitTest
 */
class ApiQueueUnitTest extends AbstractPhpunitTest
{
	/**
	 * @group api_queue_get_isolated_queue_items
	 * @return void
	 */
	public function test_api_queue_get_isolated_queue_items() {
		$this->assertSameEquals(['kml_export', 'bulk_export'], api_queue_get_isolated_queue_items());
	}

	/**
	 * @group api_queue_get_valid_queue_items
	 * @return void
	 */
	public function test_api_queue_get_valid_queue_items() {
		$this
			->assertSameEquals(
				[
					"sms",
					"sms_out",
					"cron",
					"email",
					"report",
					"postback",
					"restpostback",
					"addtarget",
					"wash",
					"wash_out",
					"pbxcomms",
					"smsdr",
					"filesync",
					"disable_all_users_from_group",
					"delete_all_rest_tokens_from_group",
					"delete_all_records_from_group",
					"webhook",
					"fileupload",
					"wash_out_result",
					"kml_export",
					"bulk_export",
				],
				api_queue_get_valid_queue_items()
			);
	}
}
