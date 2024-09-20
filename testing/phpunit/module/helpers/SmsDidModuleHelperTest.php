<?php
/**
 * SmsDidModuleHelperTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Sms did Module Helper Test
 */
class SmsDidModuleHelperTest extends AbstractModuleHelperTest
{
	use SmsDidModuleHelper;

	const EXPECTED_TYPE = 'SMSDIDS';

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function create_new_smsdids_data() {
		return [
			[],
			['0400111333'],
			['CUSTOMDID'],
			[rand(1111, 9999)],
		];
	}

	/**
	 * @group create_new_smsdids
	 * @dataProvider create_new_smsdids_data
	 * @param mixed $did
	 * @return void
	 */
	public function test_create_new_smsdids($did = null) {
		$this->purge_all_smsdid();
		$expected_id = $this->get_expected_next_smsdid_id();
		$this->assertEquals(
			$expected_id,
			$this->create_new_smsdid($did)
		);
	}
}
