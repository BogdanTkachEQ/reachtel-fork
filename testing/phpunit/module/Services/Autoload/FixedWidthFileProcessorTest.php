<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\Autoload;

use Doctrine\Common\Collections\ArrayCollection;
use Models\Interfaces\FixedWidthFieldSpecificationInterface;
use Phake;
use Services\Autoload\FixedWidthFileProcessor;
use testing\module\AbstractPhpunitModuleTest;

/**
 * Class FixedWidthFileProcessorTest
 */
class FixedWidthFileProcessorTest extends AbstractPhpunitModuleTest
{
	/**
	 * @return void
	 */
	public function testConvertFileToArray() {
		$file = tempnam('/tmp', 'testspec');
		$data = "test-name     123     0412345678  1 street, suburb          \ntest-name1    124     0412347778  2 a very lengthy street name, suburb";

		file_put_contents($file, $data);
		$specs = new ArrayCollection(
			[
				$this->getNewSpecification('Name', 1, 14),
				$this->getNewSpecification('id', 15, 8),
				$this->getNewSpecification('phone', 23, 10),
				$this->getNewSpecification('address', 33, 26),
			]
		);

		$processor = new FixedWidthFileProcessor($specs);

		$expected = [
			[
				'Name' => 'test-name',
				'id' => '123',
				'phone' => '0412345678',
				'address' => '1 street, suburb'
			],
			[
				'Name' => 'test-name1',
				'id' => '124',
				'phone' => '0412347778',
				'address' => '2 a very lengthy street'
			]
		];
		$actual = $processor->convertFileToArray($file);
		unlink($file);

		$this->assertSameEquals($expected, $actual);
	}

	/**
	 * @param string  $name
	 * @param integer $start
	 * @param integer $length
	 * @return mixed
	 */
	private function getNewSpecification($name, $start, $length) {
		$spec = Phake::mock(FixedWidthFieldSpecificationInterface::class);
		Phake::when($spec)->getFieldName()->thenReturn($name);
		Phake::when($spec)->getStartPosition()->thenReturn($start);
		Phake::when($spec)->getLength()->thenReturn($length);
		return $spec;
	}
}
