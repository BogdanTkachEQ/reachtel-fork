<?php
/**
 * MethodAbstractionTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Method Abstraction Unit Test
 */
class MethodAbstractionTest extends AbstractPhpunitUnitTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function mock_ado_records_data() {
		return [
			[[], 0],
			[[1], 1],
			[[1 => 'val1', 2 => 'val2', 3 => 'val3'], 3],
		];
	}

	/**
	 * @dataProvider mock_ado_records_data
	 * @param array   $records
	 * @param integer $count_records
	 * @return void
	 */
	public function test_mock_ado_records(array $records, $count_records) {
		$mock = $this->mock_ado_records($records);
		$this->assertInternalType('object', $mock);
		$this->assertSameEquals($count_records, $mock->RecordCount());
		$this->assertSameEquals($records, $mock->GetAssoc());
	}

	/**
	 * @return void
	 */
	public function test_mock_ado_records_fields() {
		$mock = $this->mock_ado_records(['field1' => 'val1', 'field2'  => 'val2']);
		$this->assertInternalType('object', $mock);
		$this->assertSameEquals('val1', $mock->Fields('field1'));
		$this->assertSameEquals('val2', $mock->Fields('field2'));
		$this->assertNull($mock->Fields('field3'));
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function mock_ado_records_fetch_row_data() {
		return [
			[[]],
			[['val1', 'val2']],
			[['field1' => 'val1', 'field2'  => 'val2', 'field3'  => 'val3']],
			[[['array'], new \DateTime(), 12.24, 'string']],
		];
	}

	/**
	 * @dataProvider mock_ado_records_fetch_row_data
	 * @param array $values
	 * @return void
	 */
	public function test_mock_ado_records_fetch_row(array $values) {
		$mock = $this->mock_ado_records($values);
		$this->assertInternalType('object', $mock);

		$cpt = 0;
		reset($values);
		while ($row = $mock->FetchRow()) {
			$this->assertSameEquals(current($values), $row);
			next($values);
			$cpt++;
		}

		$this->assertSameEquals(count($values), $cpt);
	}
}
