<?php
/**
 * SmsServerSupplierModuleHelperTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Sms Server Module Helper Test
 */
class SmsSupplierModuleHelperTest extends AbstractModuleHelperTest
{
	use SmsSupplierModuleHelper;

	const EXPECTED_TYPE = 'SMSSUPPLIER';
	const FUNCTION_TYPE_NAME = 'sms_supplier';

	/**
	 * @group create_new_smssupplier
	 * @return void
	 */
	public function test_create_new_smssupplier() {
		$this->do_test_create_new(true);
	}
}
