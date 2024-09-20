<?php
/**
 * VoiceSupplierModuleHelperTrait
 * Helper to create voice suppliers
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait Helper for Voice Supplier
 */
trait VoiceSupplierModuleHelper
{
	/**
	 * @return string
	 */
	protected static function get_voicesupplier_type() {
		return 'VOICESUPPLIER';
	}

	/**
	 * @return string
	 */
	protected function get_expected_next_voicesupplier_id() {
		return $this->get_expected_next_id(self::get_voicesupplier_type());
	}

	/**
	 * @param string $supplier_name
	 * @param string $type
	 * @return integer
	 */
	protected function create_new_voicesupplier($supplier_name = null, $type = 'SIP') {
		$id = api_voice_supplier_add($supplier_name ? : ('test' . substr(md5(rand()), 0, 26)), $type);
		$this->assertInternalType('int', $id);
		return (int) $id;
	}
}
