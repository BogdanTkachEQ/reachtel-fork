<?php
/**
 * ApiCsvTest
 * Unit test for api_csv.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Api CSV Unit Test class
 */
class ApiCsvUnitTest extends AbstractPhpunitUnitTest
{

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_csv_line_data() {
		return [
			['' . "\n", []],
			// default params
			['1,2,3' . "\n", [1, 2, 3]],
			['4,"foo,bar"' . "\n", [4, 'foo,bar']],
			['4,"foo,space   "' . "\n", [4, 'foo,space   ']],
			// delimiter param
			['4-5' . "\n", [4, 5], '-'],
			['one"two' . "\n", ['one', 'two'], '"'],
			['one""  s p a c e s "' . "\n", ['one', '  s p a c e s '], '"'],
			// delimiter enclosure
			['0,1' . "\n", [0, 1], ',', "-"],
			['0,-comm,a-' . "\n", [0, 'comm,a'], ',', "-"],
			['-das--h-,1' . "\n", ['das-h', 1], ',', "-"],
			['-da, s -- h-,-co--mm,a-' . "\n", ['da, s - h', 'co-mm,a'], ',', "-"],
			//end of line params
			['0,1' . "\r\n", [0, 1], ',', '"', "\\", "\r\n"],
			['0,1random', [0, 1], ',', '"', "\\", "random"],
		];
	}

	/**
	 * @group api_csv_line
	 * @dataProvider api_csv_line_data
	 * @param mixed  $expected_value
	 * @param array  $row
	 * @param string $delimiter
	 * @param string $enclosure
	 * @param string $escape_char
	 * @param string $eol
	 * @return void
	 */
	public function test_api_csv_line($expected_value, array $row, $delimiter = ',', $enclosure = '"', $escape_char = "\\", $eol = "\n") {
		$this->assertSameEquals(
			$expected_value,
			api_csv_line($row, $delimiter, $enclosure, $escape_char, $eol)
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_csv_handle_data() {
		return [
			// failure handle
			[false, null],
			[false, false],
			[false, 123456],
			[false, 'not_an_handle'],
			[false, ['not_an_handle']],
			[false, new \stdClass()],
			// success
			[
				function($res) {
					$filename = sys_get_temp_dir() . '/api_csv_handle_data_1';
					$this->assertSameEquals(0, $res);

					$this->assertFileExists($filename);

					$this->assertSameEquals(
						'',
						file_get_contents($filename)
					);

					@unlink($filename);
				},
				fopen(sys_get_temp_dir() . '/api_csv_handle_data_1', 'w'),
				[]
			],
			[
				function($res, $handle) {
					$filename = sys_get_temp_dir() . '/api_csv_handle_data_2';
					$this->assertSameEquals(8, $res);

					$this->assertFileExists($filename);

					$this->assertSameEquals(
						"a,b\nc,d\n",
						file_get_contents($filename)
					);

					@unlink($filename);
				},
				fopen(sys_get_temp_dir() . '/api_csv_handle_data_2', 'w'),
				[['a', 'b'], ['c', 'd']]
			],
			[
				function($res, $handle) {
					$filename = sys_get_temp_dir() . '/api_csv_handle_data_3';
					$this->assertSameEquals(45, $res);

					$this->assertFileExists($filename);

					$this->assertSameEquals(
						"a,b\nthis_string_should_be_converted_as_array\n",
						file_get_contents($filename)
					);

					@unlink($filename);
				},
				fopen(sys_get_temp_dir() . '/api_csv_handle_data_3', 'w'),
				[['a', 'b'], 'this_string_should_be_converted_as_array']
			],
			[
				function($res, $handle) {
					$filename = sys_get_temp_dir() . '/api_csv_handle_data_4';
					$this->assertSameEquals(45, $res);

					$this->assertFileExists($filename);

					$this->assertSameEquals(
						"a,b\r\nthis_should_add_the_line_ending_passed\r\n",
						file_get_contents($filename)
					);

					@unlink($filename);
				},
				fopen(sys_get_temp_dir() . '/api_csv_handle_data_4', 'w'),
				[['a', 'b'], 'this_should_add_the_line_ending_passed'],
				"\r\n"
			],
		];
	}

	/**
	 * @group api_csv_handle
	 * @dataProvider api_csv_handle_data
	 * @param mixed  $expected_value
	 * @param mixed  $handle
	 * @param array  $data
	 * @param string $eol
	 * @return void
	 */
	public function test_api_csv_handle($expected_value, $handle, array $data = [], $eol = "\n") {
		$res = api_csv_handle($handle, $data, ",", '"', "\\", $eol);
		if (is_callable($expected_value)) {
			$expected_value($res, $handle);
		} else {
			$this->assertSameEquals(
				$expected_value,
				$res
			);
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_csv_file_data() {
		return [
			// success
			['', []],
			['1,2' . "\n", [[1, 2]]],
			['1,2' . "\n" . '3,4' . "\n", [[1, 2], [3, 4]]],
			['5,6' . "**" . '7,8' . "**", [[5, 6], [7, 8]], "**"]
		];
	}

	/**
	 * @group api_csv_file
	 * @dataProvider api_csv_file_data
	 * @param mixed  $expected_value
	 * @param array  $data
	 * @param string $eol
	 * @return void
	 */
	public function test_api_csv_file($expected_value, array $data, $eol = "\n") {
		$filename = uniqid(sys_get_temp_dir() . '/test_api_csv_file_');

		$res = api_csv_file($filename, $data, ",", '"', "\\", false, $eol);
		if (strlen($expected_value)) {
			$this->assertGreaterThan(0, $res);
		} else {
			$this->assertSameEquals(0, $res);
		}

		$this->assertFileExists($filename);

		$this->assertSameEquals(
			$expected_value,
			file_get_contents($filename)
		);

		if ($expected_value) {
			// test append
			$res = api_csv_file($filename, [['append', 'data'],['append1','data1']], ",", '"', "\\", true, $eol);
			if (strlen($expected_value)) {
				$this->assertGreaterThan(0, $res);
			} else {
				$this->assertSameEquals(0, $res);
			}

			$this->assertFileExists($filename);

			$this->assertSameEquals(
				"{$expected_value}append,data" . $eol . "append1,data1" . $eol,
				file_get_contents($filename)
			);
		}

		@unlink($filename);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_csv_string_data() {
		return [
			// success
			['', []],
			["1\n2", [[1],[2]]],
			[
				'header1,header2,header3' . "\n" .
				'r1_c1,r1_c2,r1_c3' . "\n" .
				'r2_c1,r2_c2' . "\n" .
				'r3_c1',
				[
					['header1', 'header2', 'header3'],
					['r1_c1', 'r1_c2', 'r1_c3'],
					['r2_c1', 'r2_c2'],
					['r3_c1'],
				]
			],
			[
				'1|2' . "\n" .
				'3|4',
				[
					['1', '2'],
					['3', '4'],
				],
				'|'
			],
			[
				'1|2' . "\n" .
				'"enclosure|expected"|4',
				[
					['1', '2'],
					['enclosure|expected', '4'],
				],
				'|'
			],
			[
				'1|2' . "\n" .
				'\'quote|enclosure|expected\'|4',
				[
					['1', '2'],
					['quote|enclosure|expected', '4'],
				],
				'|',
				"'"
			],
			[
				'"comma,double quote,enclosure,expected","double""quote,enclosure & doubled""expected"' . "\n" .
				'"quote,enclosure,expected","A space enclosure expected"' . "\n" .
				"\"new\nline\nenclosure\nexpected\",\"space comma, quote' double quote\"\" new line\n: enclosure & doubled\"\"\"\"expected\"",
				[
					['comma,double quote,enclosure,expected', 'double"quote,enclosure & doubled"expected'],
					['quote,enclosure,expected', "A space enclosure expected"],
					["new\nline\nenclosure\nexpected", "space comma, quote' double quote\" new line\n: enclosure & doubled\"\"expected"],
				],
			],
			[
				'1|2' . "\r\n\r\n" .
				'\'quote|enclosure|expected\'|4',
				[
					['1', '2'],
					['quote|enclosure|expected', '4'],
				],
				'|',
				"'",
				"\r\n\r\n"
			],
		];
	}

	/**
	 * @group api_csv_string
	 * @dataProvider api_csv_string_data
	 * @param mixed  $expected_value
	 * @param array  $data
	 * @param string $delimiter
	 * @param string $enclosure
	 * @param string $eol
	 * @return void
	 */
	public function test_api_csv_string($expected_value, array $data, $delimiter = ',', $enclosure = '"', $eol = "\n") {
		$this->assertSameEquals(
			$expected_value,
			api_csv_string($data, $delimiter, $enclosure, "\\", $eol)
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_csv_add_row_keys_data() {
		return [
			// no data
			[[], []],
			// just headers
			[
				[['header3', 'header1', 'header2']],
				[['header3', 'header1', 'header2']],
			],
			[
				[
					['c', 'a', 'b'],
					['c' => 3, 'a' => 1, 'b' => 2],
					['c' => 33, 'a' => 11, 'b' => 22],
				],
				[
					['c', 'a', 'b'],
					[3, 1, 2],
					[33, 11, 22],
				],
			],
			[
				[
					['2', 3.3, 2],
					['2' => 'string', 3.3 => 'float', 2 => 'int'],
				],
				[
					['2', 3.3, 2],
					['string', 'float', 'int'],
				],
			],
			// invalid sort parameter
			[
				[['header3', 'header1', 'header2']],
				[['header3', 'header1', 'header2']],
				'whatever'
			],
			[
				[
					['c', 'a', 'b'],
					['c' => 3, 'a' => 1, 'b' => 2],
					['c' => 33, 'a' => 11, 'b' => 22],
				],
				[
					['c', 'a', 'b'],
					[3, 1, 2],
					[33, 11, 22],
				],
				'invalid'
			],
			// sort ASC uppercase
			[
				[['header1', 'header2', 'header3']],
				[['header3', 'header1', 'header2']],
				'ASC'
			],
			[
				[
					['a', 'b', 'c'],
					['a' => 1, 'b' => 2, 'c' => 3],
					['a' => 11, 'b' => 22, 'c' => 33],
				],
				[
					['c', 'a', 'b'],
					[3, 1, 2],
					[33, 11, 22],
				],
				'ASC'
			],
			[
				[
					[1, '2', 2.1],
					[1 => 'int', '2' => 'string', 2.1 => 'float'],
				],
				[
					['2', 2.1, 1],
					['string', 'float', 'int'],
				],
				'ASC'
			],
			// sort asc lowercase
			[
				[['header1', 'header2', 'header3']],
				[['header3', 'header1', 'header2']],
				'asc'
			],
			[
				[
					['a', 'b', 'c'],
					['a' => 1, 'b' => 2, 'c' => 3],
					['a' => 11, 'b' => 22, 'c' => 33],
				],
				[
					['c', 'a', 'b'],
					[3, 1, 2],
					[33, 11, 22],
				],
				'asc'
			],
			[
				[
					[1, '2', 2.1],
					[1 => 'int', '2' => 'string', 2.1 => 'float'],
				],
				[
					['2', 2.1, 1],
					['string', 'float', 'int'],
				],
				'asc'
			],
			// sort DESC uppercase
			[
				[['header3', 'header2', 'header1']],
				[['header3', 'header1', 'header2']],
				'DESC'
			],
			[
				[
					['c', 'b', 'a'],
					['c' => 3, 'b' => 2, 'a' => 1],
				],
				[
					['c', 'a', 'b'],
					[3, 1, 2],
				],
				'DESC'
			],
			[
				[
					[2.1, '2', 1],
					[2.1 => 'float', '2' => 'string', 1 => 'int'],
				],
				[
					['2', 2.1, 1],
					['string', 'float', 'int'],
				],
				'DESC'
			],
			// sort desc lowercase
			[
				[['header3', 'header2', 'header1']],
				[['header3', 'header1', 'header2']],
				'desc'
			],
			[
				[
					['c', 'b', 'a'],
					['c' => 3, 'b' => 2, 'a' => 1],
				],
				[
					['c', 'a', 'b'],
					[3, 1, 2],
				],
				'desc'
			],

			[
				[
					[2.1, '2', 1],
					[2.1 => 'float', '2' => 'string', 1 => 'int'],
				],
				[
					['2', 2.1, 1],
					['string', 'float', 'int'],
				],
				'desc'
			],
		];
	}

	/**
	 * @group api_csv_add_row_keys
	 * @dataProvider api_csv_add_row_keys_data
	 * @param mixed  $expected_value
	 * @param array  $data
	 * @param string $sort
	 * @return void
	 */
	public function test_api_csv_add_row_keys($expected_value, array $data, $sort = null) {
		$this->assertSameEquals(
			$expected_value,
			api_csv_add_row_keys($data, $sort)
		);
	}

	/**
	 * @return array
	 */
	public function api_csv_fputcsv_eol_data() {
		return [
			'without any data' => [
				"\n",
				1,
				[]
			],
			'without data and with custom eol' => [
				"\r\n",
				2,
				[],
				"\r\n"
			],
			'with default eol' => [
				"a,b\n",
				4,
				['a', 'b'],
			],

			'with custom eol' => [
				"c,d\r\n\r\n",
				7,
				['c', 'd'],
				"\r\n\r\n"
			]
		];
	}

	/**
	 * @dataProvider api_csv_fputcsv_eol_data
	 * @param string  $expected_data
	 * @param integer $expected_result
	 * @param array   $data
	 * @param string  $eol
	 * @return void
	 */
	public function test_api_csv_fputcsv_eol($expected_data, $expected_result, array $data, $eol = null) {
		$filename = sys_get_temp_dir() . '/api_csv_handle_data_1';
		$handle = fopen($filename, 'w');

		if (!$eol) {
			// test default eol
			$result = api_csv_fputcsv_eol($handle, $data, ',', '"', "\\");
		} else {
			$result = api_csv_fputcsv_eol($handle, $data, ',', '"', "\\", $eol);
		}

		$this->assertSameEquals($expected_result, $result);
		$this->assertSameEquals($expected_data, file_get_contents($filename));
		unlink($filename);
	}
}
