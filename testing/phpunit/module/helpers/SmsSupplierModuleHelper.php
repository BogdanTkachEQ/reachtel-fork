<?php
/**
 * SmsSupplierModuleHelperTrait
 * Helper to create sms suppliers
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait Helper for Sms Supplier
 */
trait SmsSupplierModuleHelper
{
	/**
	 * @return string
	 */
	protected static function get_smssupplier_type() {
		return 'SMSSUPPLIER';
	}

	/**
	 * @return string
	 */
	protected function get_expected_next_smssupplier_id() {
		return $this->get_expected_next_id(self::get_smssupplier_type());
	}

	/**
	 * @param string  $sms_supplier_name
	 * @param boolean $status
	 * @param mixed   $capabilities
	 * @param integer $priority
	 *
	 * @return integer
	 */
	protected function create_new_smssupplier($sms_supplier_name = null, $status = null, $capabilities = null, $priority = 1) {
		$id = api_sms_supplier_add($sms_supplier_name ? : ('test' . substr(md5(rand()), 0, 26)));
		$this->assertInternalType('int', $id);
		$this->assertTrue(
			api_sms_supplier_setting_set($id, "priority", $priority)
		);

		if (!is_null($status)) {
			$this->assertTrue(
				api_sms_supplier_setting_set($id, "status", ($status ? 'ACTIVE' : 'DISABLED'))
			);
		}

		if (is_array($capabilities) && $capabilities) {
			$this->assertTrue(
				api_sms_supplier_setting_set($id, "capabilities", serialize($capabilities))
			);
		}

		return (int) $id;
	}
}
