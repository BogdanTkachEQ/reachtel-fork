<?php
/**
 * AbstractPhpunitUnitTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

use testing\AbstractPhpunitTest;

/**
 * Abstract Unit Test class
 */
abstract class AbstractPhpunitUnitTest extends AbstractPhpunitTest
{
	/**
	 * SetUp run once before each test method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		// set listeners
		$this->listen_mocked_function('passthru'); // listen passthru function to check the executed command

		/* mock global functions used in the API codebase */
		$this->mock_function_prevent('api_db_query_read');
		$this->mock_function_prevent('api_db_query_write');
		$this->mock_function_value('api_error_raise', false);
		$this->mock_function_value('passthru', null);
	}

	/**
	 * Return a mocked a class of ADORecordSet_mysqli
	 *
	 * @param array $records
	 * @return mixed
	 */
	public function mock_ado_records(array $records = []) {
		// re-mock api_db_query_read
		$ADORecordSet = $this->getMockBuilder('ADORecordSet_mysqli')
			->disableOriginalConstructor()
			->getMock();

		$ADORecordSet->__records = $records;
		reset($ADORecordSet->__records);

		$ADORecordSet->method('RecordCount')
			->willReturn(count($records));

		$callback = function($key) use($ADORecordSet) {
			return array_key_exists($key, $ADORecordSet->__records) ? $ADORecordSet->__records[$key] : null;
		};

		$ADORecordSet->method('Fields')
			->will($this->returnCallback($callback));

		$ADORecordSet->method('GetAssoc')
			->willReturn($ADORecordSet->__records);

		$callback = function($nbRecords = null) use(&$ADORecordSet) {
			if ($nbRecords > 0) {
				$ADORecordSet->__start = isset($ADORecordSet->__start) ? $ADORecordSet->__start : 0;
				$results = array_slice(
					$ADORecordSet->__records,
					$ADORecordSet->__start,
					$nbRecords,
					true
				);
				$ADORecordSet->__start = $ADORecordSet->__start + $nbRecords;

				return $results;
			}
			return $ADORecordSet->__records;
		};
		$ADORecordSet->method('GetArray')
			->will($this->returnCallback($callback));

		$callback = function() use($ADORecordSet) {
			$key = key($ADORecordSet->__records);
			next($ADORecordSet->__records);

			return (array_key_exists($key, $ADORecordSet->__records) ? $ADORecordSet->__records[$key] : false);
		};

		$ADORecordSet->method('FetchRow')
			->will($this->returnCallback($callback));

		return $ADORecordSet;
	}
}
