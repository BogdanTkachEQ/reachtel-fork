<?php
/**
 * CronModuleHelperTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

use testing\helpers\MethodParametersHelper;

/**
 * Cron Module Helper Test
 */
class CronModuleHelperTest extends AbstractModuleHelperTest
{
	use CronModuleHelper;
	use MethodParametersHelper;

	const EXPECTED_TYPE = 'CRON';

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function create_new_cron_data() {
		return $this->get_test_data_from_parameters_combinations(
			[$this, 'create_new_cron'],
			[
				'cron_name' => $this->add_parameter_possibilities([null, (function() {return uniqid('create_new_cron');})]),
				'status' => $this->add_parameter_possibilities([false, 'DISABLED', 'ACTIVE']),
			]
		);
	}

	/**
	 * @group create_new_cron
	 * @dataProvider create_new_cron_data
	 * @param mixed $cron_name
	 * @param mixed $status
	 * @return void
	 */
	public function test_create_new_cron($cron_name = null, $status = false) {
		if (is_object($cron_name) && $cron_name instanceof \Closure) {
			$cron_name = $cron_name();
		}

		$expected_id = $this->get_expected_next_cron_id();
		$this->assertEquals(
			$expected_id,
			$this->create_new_cron($cron_name, $status)
		);
	}
}
