<?php
/**
 * SmsInboundScriptsTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\script;

/**
 * SmsInboundScriptsTest Test class
 */
class SmsInboundScriptsTest extends AbstractPhpunitScriptTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function sms_inbound_script_function_data() {
		$data = [];
		$files = $this->get_files('SMS Scripts');
		foreach ($files as $file) {
			$data['SMS Scripts ' . basename($file)] = [
				$file,
				basename($file, '.php')
			];
		}

		return $data;
	}

	/**
	 * @dataProvider sms_inbound_script_function_data
	 * @param string $file
	 * @param string $function
	 * @return void
	 */
	public function test_sms_inbound_script_function($file, $function) {
		// assert function does not already exists
		$this->assertFalse(
			function_exists($function),
			sprintf('Function %s() does not exists', $function)
		);

		require_once($file);
		// assert function name
		$this->assertTrue(
			function_exists($function),
			sprintf('Expects function %s() in %s', $function, basename($file))
		);
		// assert function callable
		$this->assertTrue(
			is_callable($function),
			sprintf('Function %s() is callable', $function)
		);
	}
}
