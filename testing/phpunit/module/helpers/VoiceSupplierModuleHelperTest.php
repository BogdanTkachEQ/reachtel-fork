<?php
/**
 * VoiceServerSupplierModuleHelperTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Voice Server Module Helper Test
 */
class VoiceSupplierModuleHelperTest extends AbstractModuleHelperTest
{
	use VoiceSupplierModuleHelper;

	const EXPECTED_TYPE = 'VOICESUPPLIER';
	const FUNCTION_TYPE_NAME = 'voice_supplier';

	/**
	 * @group create_new_voicesupplier
	 * @return void
	 */
	public function test_create_new_voicesupplier() {
		$this->do_test_create_new(true);
	}
}
