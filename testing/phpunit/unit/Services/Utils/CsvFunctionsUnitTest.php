<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Utils;

use Services\Utils\CsvFunctions;
use testing\AbstractPhpunitTest;

/**
 * Class CsvFunctionsUnitTest
 */
class CsvFunctionsUnitTest extends AbstractPhpunitTest
{
	/**
	 * @return void
	 */
	public function testCsvToArray() {
		$data = "headerA,headerB,headerC\nrow1A,row1B,row1C\nrow2A,,row2C";
		$file = 'test.csv';
		$this
			->mock_function_param_value(
				'file_get_contents',
				[
					[
						'params' => $file,
						'return' => $data
					]
				],
				false
			);

		$this->assertSameEquals(
			[
				[
					'headerA' => 'row1A',
					'headerB' => 'row1B',
					'headerC' => 'row1C'
				],
				[
					'headerA' => 'row2A',
					'headerB' => '',
					'headerC' => 'row2C'
				]
			],
			CsvFunctions::csvToArray($file)
		);

		$this->assertFalse(CsvFunctions::csvToArray('invalidFilename.csv'));
	}
}
