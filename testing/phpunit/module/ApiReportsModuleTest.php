<?php
/**
 * ApiReportsModuleTest
 * Module tests for Activity Logger
 *
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use testing\module\helpers\BillingModuleHelper;

/**
 * Class ApiReportsModuleTest
 */
class ApiReportsModuleTest extends AbstractPhpunitModuleTest
{
	use BillingModuleHelper;

	/**
	 * @return void
	 */
	public function test_api_reports_rest_api_sms_report() {
		$username1 = 'restsmsreporttest1';
		$username2 = 'restsmsreporttest2';
		$userid1 = $this->create_new_user($username1);
		$userid2 = $this->create_new_user($username2);
		$sms_out1 = $this->create_new_sms_out(2, $userid1);
		$sms_out2 = $this->create_new_sms_out(3, $userid2);

		$result = api_reports_rest_api_sms_report(
			[$userid1, $userid2],
			new \DateTime('today 00:00:00'),
			new \DateTime('today 23:59:59')
		);

		$this->assertSameEquals(5, count($result));
		foreach ($result as $data) {
			$this->assertArrayHasKey('username', $data);
			$this->assertArrayHasKey('timestamp', $data);
			$this->assertArrayHasKey('message', $data);
			$this->assertArrayHasKey('status', $data);
			$this->assertArrayHasKey('destination', $data);

			$this->assertSameEquals('SENT', $data['status']);
			$this->assertContains($data['username'], [$username1, $username2]);
		}

		$sms_out = array_merge($sms_out1, $sms_out2);
		foreach ($sms_out as $out) {
			$this->create_new_sms_out_status($out['id'], date('Y-m-d H:i:s'));
		}

		$result_status = api_reports_rest_api_sms_report(
			[$userid1, $userid2],
			new \DateTime('today 00:00:00'),
			new \DateTime('today 23:59:59'),
			true,
			new \DateTime('today 00:00:00'),
			new \DateTime('today 23:59:59')
		);

		foreach ($result as $data) {
			$this->assertArrayHasKey('username', $data);
			$this->assertArrayHasKey('timestamp', $data);
			$this->assertArrayHasKey('status_timestamp', $data);
			$this->assertArrayHasKey('message', $data);
			$this->assertArrayHasKey('status', $data);
			$this->assertArrayHasKey('destination', $data);

			$this->assertSameEquals('SENT', $data['status']);
			$this->assertContains($data['username'], [$username1, $username2]);
		}
	}
}
