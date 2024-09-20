<?php
/**
 * HlrSupplierModuleHelperTrait
 * Helper to create hlr suppliers
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait Helper for Hlr Supplier
 */
trait HlrSupplierModuleHelper
{
	/**
	 * @return string
	 */
	protected static function get_hlrsupplier_type() {
		return 'HLRSUPPLIER';
	}

	/**
	 * @return string
	 */
	protected function get_expected_next_hlrsupplier_id() {
		return $this->get_expected_next_id(self::get_hlrsupplier_type());
	}

	/**
	 * @param string $hlr_supplier_name
	 * @return integer
	 */
	protected function create_new_hlrsupplier($hlr_supplier_name = null) {
		$id = api_hlr_supplier_add($hlr_supplier_name ? : ('test' . substr(md5(rand()), 0, 26)));
		$this->assertInternalType('int', $id);
		return (int) $id;
	}
}
