<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models\Entities;

use Doctrine\Common\Collections\Collection;
use Models\Entities\FixedWidthFile;
use testing\unit\AbstractModelTestCase;

/**
 * Class FixedWidthFileUnitTest
 */
class FixedWidthFileUnitTest extends AbstractModelTestCase
{
	/**
	 * @return array
	 */
	protected function getData() {
		return [
			'id' => 454,
			'name' => 'test-name',
			'specifications' => \Phake::mock(Collection::class)
		];
	}

	/**
	 * @return mixed
	 */
	protected function getObject() {
		return new FixedWidthFile();
	}
}
