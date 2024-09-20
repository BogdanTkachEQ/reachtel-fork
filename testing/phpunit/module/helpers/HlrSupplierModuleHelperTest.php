<?php
/**
 * HlrServerSupplierModuleHelperTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Hlr Server Module Helper Test
 */
class HlrSupplierModuleHelperTest extends AbstractModuleHelperTest
{
	use HlrSupplierModuleHelper;

	const EXPECTED_TYPE = 'HLRSUPPLIER';
	const FUNCTION_TYPE_NAME = 'hlr_supplier';

	/**
	 * @group create_new_hlrsupplier
	 * @return void
	 */
	public function test_create_new_hlrsupplier() {
		$this->do_test_create_new(true);
	}
}
