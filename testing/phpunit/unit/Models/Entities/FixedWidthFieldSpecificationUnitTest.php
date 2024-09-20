<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models\Entities;

use Models\Entities\FixedWidthFieldSpecification;
use Models\Entities\FixedWidthFile;
use testing\unit\AbstractModelTestCase;

/**
 * Class FixedWidthFieldSpecificationUnitTest
 */
class FixedWidthFieldSpecificationUnitTest extends AbstractModelTestCase
{
	/**
	 * @return array
	 */
	protected function getData() {
		return [
			'id' => 123123,
			'fieldName' => 'test-field-name',
			'startPosition' => 19,
			'length' => 45,
			'fixedWidthFile' => \Phake::mock(FixedWidthFile::class)
		];
	}

	/**
	 * @return mixed
	 */
	protected function getObject() {
		return new FixedWidthFieldSpecification();
	}
}
