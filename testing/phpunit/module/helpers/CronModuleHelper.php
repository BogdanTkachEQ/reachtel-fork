<?php
/**
 * CronModuleHelper
 * Helper to create crons
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait Helper for crons
 */
trait CronModuleHelper
{
	/**
	 * @return string
	 */
	protected static function get_cron_type() {
		return 'CRON';
	}

	/**
	 * @return string
	 */
	protected function get_expected_next_cron_id() {
		return $this->get_expected_next_id(self::get_cron_type());
	}

	/**
	 * @param string $cron_name
	 * @param mixed  $status
	 * @return integer
	 */
	protected function create_new_cron($cron_name = null, $status = false) {
		$expected_id = $this->get_expected_next_id(self::get_cron_type());
		$this->assertSameEquals($expected_id, api_cron_add($cron_name ? : uniqid('cron_')));

		if ($status !== false) {
			$this->assertTrue(api_cron_setting_set($expected_id, 'status', $status));
		}

		return $expected_id;
	}

	/**
	 * @return void
	 */
	protected function purge_all_crons() {
		$all_crons = api_cron_listall();
		$this->assertInternalType('array', $all_crons);
		foreach ($all_crons as $cron_id => $cron) {
			$this->assertTrue(api_cron_delete($cron_id));
		}
		$this->assertEmpty(api_cron_listall());
	}

	/**
	 * @codeCoverageIgnore
	 * @param mixed $cron
	 * @param array $settings
	 * @return void
	 */
	private function assert_cron($cron, array $settings = []) {
		$this->assertInternalType('array', $cron);
		$settings = array_merge($this->get_default_expected_values($this->get_cron_type()), $settings);
		foreach ($settings as $key => $expected_value) {
			$this->assertArrayHasKey($key, $cron);
			$this->assertSameEquals($expected_value, $cron[$key]);
		}
	}
}
