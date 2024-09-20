<?php
/**
 * @author      phillip.berry@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\File\CSV;

use ParseCsv\Csv;
use Services\File\CSV\BasicCSV;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class BasicCSVUnitTest
 *
 */
class BasicCSVUnitTest extends AbstractPhpunitUnitTest {

	/**
	 * @return void
	 */
	public function testParser() {
		$data = 'CUSTOMER_ID,ACCOUNT_ID,CUSTOMER_NAME,C1_FNAME,C1_LNAME,C1_PHONE1,C1_PHONE2,C1_PHONE3,PREFER TIME
1,111,"CN1
test\",CNF1,CNL1,411111111,411111112,411111113,8AM - 12PM
2,222,"test, \'CN2, XYZ\'",CNF2,CNL2,411111121,411111122,,8AM - 12PM
3,333,CN3,CNF3,CNL3,411111131,,,8AM - 12PM
4,444,CN4,CNF4,CNL4,411111141,,,12PM - 3PM
4,444,CN4,CNF4,CNL4,411111141,,,12PM - 3PM';

		$csv = new BasicCSV(new Csv());
		$this->assertTrue($csv->parseString($data));

		$this->assertCount(5, $csv->getRowData());

		$csvData = $csv->getRowData();
		$this->assertEquals("CN1\ntest\\", $csvData[0]['CUSTOMER_NAME']);
		$this->assertEquals("test, 'CN2, XYZ'", $csvData[1]['CUSTOMER_NAME']);
	}

	/**
	 * @return void
	 */
	public function testGetHeader() {
		$data = "c1,c2,c3\n'aa,ab,ac',\"b\",'c'";
		$csv = new BasicCSV(new Csv());
		$this->assertTrue($csv->parseString($data));
		$this->assertEquals(["c1", "c2", "c3"], $csv->getHeader());
	}

	/**
	 * @return void
	 */
	public function testNoHeader() {
		$data = "1,2,3\na,b,c";
		$csv = new BasicCSV(new Csv());
		$csv->setHasHeader(false);
		$this->assertTrue($csv->parseString($data));
		$this->assertCount(2, $csv->getRowData());
		$this->assertEquals(["1","2","3"], $csv->getRowData()[0]);
		$this->assertEquals(["a","b","c"], $csv->getRowData()[1]);
	}

	/**
	 * @return void
	 */
	public function testDelim() {
		$data = "c1@c2@c3\na@b@c";
		$csv = new BasicCSV(new Csv());
		$csv->setDelimiter("@");
		$this->assertTrue($csv->parseString($data));
		$this->assertCount(1, $csv->getRowData());
		$this->assertEquals(["c1" => "a","c2" => "b","c3" => "c"], $csv->getRowData()[0]);
	}

	/**
	 * @return void
	 */
	public function testQuote() {
		$data = "c1,c2,c3\n'aa,ab,ac',\"b\",'c'";
		$csv = new BasicCSV(new Csv());
		$csv->setQuoteChar("'");
		$this->assertTrue($csv->parseString($data));
		$this->assertCount(1, $csv->getRowData());
		$this->assertEquals(["c1" => "aa,ab,ac","c2" => '"b"',"c3" => "c"], $csv->getRowData()[0]);
	}

	/**
	 * @return void
	 */
	public function testMultiline() {
		$data = "c1,c2,c3\n'aa\n,ab\n,ac',b,'c'";
		$csv = new BasicCSV(new Csv());
		$csv->setQuoteChar("'");
		$this->assertTrue($csv->parseString($data));
		$this->assertCount(1, $csv->getRowData());
		$this->assertEquals(["c1" => "aa\n,ab\n,ac","c2" => 'b',"c3" => "c"], $csv->getRowData()[0]);
	}

	/**
	 * @return void
	 */
	public function testNewLineChar() {
		$data = "c1,c2,c3\n'aa\r\n,ab\r,ac',b,'c'";
		$parser = new Csv();

		$csv = new BasicCSV($parser);
		$csv->setQuoteChar("'");
		$this->assertTrue($csv->parseString($data));

		$this->assertCount(1, $csv->getRowData());
		$this->assertEquals(["c1" => "aa\r\n,ab\r,ac","c2" => 'b',"c3" => "c"], $csv->getRowData()[0]);
	}

	/**
	 * @return void
	 */
	public function testUnparse() {
		$data = [
			['col1', 'col2', 'col3'],
			['row11', 'row12', 'row13'],
			['row21', 'row22', 'row23']
		];

		$parser = new Csv();
		$csv = new BasicCSV($parser);
		$csv
			->setDelimiter('|')
			->setNewLineChar("\n");
		$csvString = $csv->unparse($data);

		$expected = "col1|col2|col3\nrow11|row12|row13\nrow21|row22|row23\n";

		$this->assertSameEquals($expected, $csvString);

		$csv
			->setDelimiter(',')
			->setNewLineChar("|");
		$csvString = $csv->unparse($data);

		$expected = "col1,col2,col3|row11,row12,row13|row21,row22,row23|";

		$this->assertSameEquals($expected, $csvString);
	}
}
