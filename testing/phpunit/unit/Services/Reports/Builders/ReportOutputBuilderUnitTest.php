<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports\Builders;

use Phake;
use Services\Exceptions\Rules\RulesException;
use Services\File\Interfaces\EncryptorInterface;
use Services\Reports\ArrayRulesEngineDecorator;
use Services\Reports\Builders\ReportOutputBuilder;
use Services\Reports\Interfaces\ArrayToFileConverterInterface;
use Services\Reports\Interfaces\RowDataModifierInterface;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class ReportOutputBuilderUnitTest
 */
class ReportOutputBuilderUnitTest extends AbstractPhpunitUnitTest
{
	/** @var ArrayToFileConverterInterface | \Phake_IMock */
	private $converter;

	/** @var ReportOutputBuilder */
	private $builder;

	/** @var EncryptorInterface | \Phake_IMock */
	private $encryptor;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->converter = Phake::mock(ArrayToFileConverterInterface::class);
		$this->encryptor = Phake::mock(EncryptorInterface::class);
		$this->builder = new ReportOutputBuilder(
			$this->converter,
			$this->encryptor
		);
	}

	/**
	 * @expectedException Services\Reports\Exceptions\NoDataGeneratedException
	 * @expectedExceptionMessage No data generated
	 * @return void
	 */
	public function testBuildThrowsExceptionIfNoData() {
		$this->builder->setData([]);
		$this->builder->build();
	}

	/**
	 * @expectedException Services\Reports\Exceptions\ReportOutputBuilderException
	 * @expectedExceptionMessage An error occurred while converting to file
	 * @return void
	 */
	public function testBuildThrowsExceptionWhenConverterFails() {
		$data = [
			['column1' => 'value1', 'column2' => 'value2', 'column3' => 'value3'],
			['column1' => 'value4', 'column2' => 'value5', 'column3' => 'value6'],
		];

		$this->builder->setData($data);

		Phake::when($this->converter)->convertArrayToFile(Phake::anyParameters())->thenReturn(false);
		$this->builder->build();
	}

	/**
	 * @return array
	 */
	public function buildDataProvider() {
		$data = [
			[
				'column1' => 'value1',
				'column2' => 'value2',
				'column3' => 'value3',
				'column4' => 'value7',
				'column5' => 'value8',
				'column6' => 'value9'
			],
			[
				'column1' => 'value4',
				'column2' => 'value5',
				'column3' => 'value6',
				'column4' => 'value10',
				'column5' => 'value11',
				'column6' => 'value12'
			],
		];

		$expected = [
			[
				'column1' => 'value1',
				'column2' => 'value2',
				'modified-header-name' => 'modified-data',
				'column5' => 'value8'
			],
			[
				'column1' => 'value4',
				'column2' => 'value5',
				'modified-header-name' => 'modified-data',
				'column5' => 'value11'
			]
		];

		$header = ['column1', 'column2', 'modified-header-name', 'column5'];

		return [
			'when header is hidden' => [
				true,
				$data,
				$expected
			],

			'when header is not hidden' => [
				false,
				$data,
				array_merge([$header], $expected)
			],
			'when filter is applied' => [
				false,
				$data,
				array_merge([$header], [$expected[0]]),
				true
			],
		];
	}

	/**
	 * @dataProvider buildDataProvider
	 * @param boolean $hideHeader
	 * @param array   $data
	 * @param array   $expected
	 * @param boolean $applyFilter
	 * @return void
	 */
	public function testBuild($hideHeader, array $data, array $expected, $applyFilter = false) {
		$modifier = Phake::mock(RowDataModifierInterface::class);
		Phake::when($modifier)->getHeaderName()->thenReturn('modified-header-name');
		Phake::when($modifier)->getModifiedData()->thenReturn('modified-data');

		$outputCol = ['column1', 'column2', $modifier, 'column5'];
		$this
			->builder
			->setOutputColumns($outputCol)
			->setHeaderMap(['column1:map1, column5:map2'])
			->hideHeader($hideHeader);

		if ($applyFilter) {
			$filterRulesEngine = Phake::mock(ArrayRulesEngineDecorator::class);
			$filter = Phake::mock(ArrayRulesEngineDecorator::class);
			Phake::when($filter)->runRules()->thenReturn(false);
			Phake::when($filterRulesEngine)->setData($data[0])->thenThrow(new RulesException());
			Phake::when($filterRulesEngine)->setData($data[1])->thenReturn($filter);
			$this->builder->setFilterRulesEngine($filterRulesEngine);
		}

		Phake::when($this->converter)->convertArrayToFile(Phake::capture($finalData), Phake::capture($filename))->thenReturn(true);
		Phake::when($this->encryptor)->setFile(Phake::capture($fileToEncrypt))->thenReturnSelf();
		$encryptedData = 'encrypted-data';
		Phake::when($this->encryptor)->encrypt()->thenReturn($encryptedData);
		$file = $this->builder->setData($data)->build();
		$this->assertSameEquals($encryptedData, file_get_contents($file));
		unlink($file);

		if ($applyFilter) {
			Phake::verify($filterRulesEngine, Phake::times(2))->setData(Phake::captureAll($items));
			$this->assertSameEquals($data, $items);
		}

		$this->assertSameEquals($filename, $file);
		$this->assertSameEquals($expected, $finalData);
	}
}
