<?php
/**
 * AbstractPhpunitModuleTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

/**
 * Abstract PHPUnit Module Test class
 */
abstract class AbstractPhpunitModuleTest extends AbstractDatabasePhpunitModuleTest
{
	/**
	 * SetUp run once before each test method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->remove_mocked_functions('api_email_template');
		$this->mock_function_value('api_email_template', true);
	}

	/**
	 * @param string $type
	 * @param string $location
	 * @param string $filename
	 * @return string
	 */
	protected function create_test_file($type, $location, $filename = null) {
		// assert location path exists and is writable
		$this->assertTrue(is_writable($location), "Tmp location '$location' not found or not writable.");

		$test_files_map = self::get_config('module.test_files_map');
		$this->assertArrayHasKey($type, $test_files_map, "Test file type '$type' does not exists.");
		$file_params = $test_files_map[$type];
		$this->assertArrayHasKey('data', $file_params, "No 'data' set for test file type '$type'.");
		$this->assertArrayHasKey('nb_bytes', $file_params, "No 'nb_bytes' set for test file type '$type'.");

		$file_path = $location . '/' . ($filename ? : uniqid() . "_{$type}_test_file.{$type}");
		$this->assertSameEquals(
			$file_params['nb_bytes'],
			file_put_contents(
				$file_path,
				base64_decode($file_params['data'])
                        )
	        );

		return $file_path;
	}

	/**
	 * @param string $type
	 * @return mixed
	 */
	protected function create_new_by_type($type) {
		$method = strtolower(sprintf('create_new_%s', $this->type_plural($type, false)));
		$this->assertTrue(method_exists($this, $method), "Method $method() does not exists. Is the helper trait used?");
		$new_type_id = $this->$method();

		// asset returns an array, and we want just the id
		if (is_array($new_type_id) && isset($new_type_id['id'])) {
			$new_type_id = $new_type_id['id'];
		}

		return $new_type_id;
	}

	/**
	 * @param string $type
	 * @return mixed
	 */
	protected function purge_by_type($type) {
		$method = strtolower(sprintf('purge_all_%s', $this->type_plural($type, true)));
		$this->assertTrue(method_exists($this, $method), "Method $method() does not exists. Is the helper trait used?");
		return $this->$method();
	}

	/**
	 * @param string $type
	 * @return mixed
	 */
	protected function get_default_expected_values($type) {
		$type = strtolower($this->type_plural($type, false));
		return self::get_config("helpers.{$type}.default_expected_values");
	}

	/**
	 * @param string  $type
	 * @param boolean $plural
	 * @return string
	 */
	protected function type_plural($type, $plural) {
		$type_has_plural = strcasecmp('S', substr($type, -1)) === 0;
		if ($plural && !$type_has_plural) {
			return $type . 'S';
		} elseif (!$plural && $type_has_plural) {
			return substr($type, 0, -1);
		}

		return $type;
	}
}
