<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Utils;

use Services\Utils\StringFunctions;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class StringFunctionsUnitTest
 */
class StringFunctionsUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	public function parseDateTimeDataProvider() {
		return [
			['test-20191108-string', 'test-[YYYYMMDD]-string', \DateTime::createFromFormat('dmY', '08112019')],
			['teststring2019-11-08', 'teststring[YYYY-MM-DD]', \DateTime::createFromFormat('dmY', '08112019')],
			['test-2019/11/08-string', 'test-[YYYY/MM/DD]-string', \DateTime::createFromFormat('dmY', '08112019')],
			['test-[ymdhis]-string', 'test-[ymdhis]-string', \DateTime::createFromFormat('dmY', '08112019')],
			['test-string', 'test-string', \DateTime::createFromFormat('dmY', '08112019')],
		];
	}

	/**
	 * @dataProvider parseDateTimeDataProvider
	 * @param string    $expected
	 * @param string    $string
	 * @param \DateTime $dateTime
	 * @return void
	 */
	public function testParseDateTime($expected, $string, \DateTime $dateTime) {
		$this->assertSameEquals($expected, StringFunctions::parseDateTime($string, $dateTime));
	}
}
