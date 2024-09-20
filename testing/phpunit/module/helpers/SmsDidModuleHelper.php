<?php
/**
 * SmsDidModuleHelper
 * Helper to create sms dids
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait Helper for sms dids
 */
trait SmsDidModuleHelper
{
	/**
	 * @return string
	 */
	protected static function get_smsdid_type() {
		return 'SMSDIDS';
	}

	/**
	 * @return string
	 */
	protected function get_expected_next_smsdid_id() {
		return $this->get_expected_next_id(self::get_smsdid_type());
	}

	/**
	 * @param string $did
	 * @return integer
	 */
	protected function create_new_smsdid($did = null) {
		$expected_id = $this->get_expected_next_id(self::get_smsdid_type());
		$this->assertSameEquals($expected_id, api_sms_dids_add($did ? : '0412' . rand(100000, 999999)));

		return $expected_id;
	}

	/**
	 * @return void
	 */
	protected function purge_all_smsdid() {
		$all_dids = api_sms_dids_listall();
		$this->assertInternalType('array', $all_dids);
		foreach ($all_dids as $did) {
			$this->assertTrue(api_sms_dids_delete_bydid($did));
		}
		$this->assertEmpty(api_sms_dids_listall());
	}
}
