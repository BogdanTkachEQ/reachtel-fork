<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models\Entities;

use Models\Entities\Country;
use Models\Entities\Region;
use Phake;
use testing\unit\AbstractModelTestCase;

/**
 * Class RegionUnitTest
 */
class RegionUnitTest extends AbstractModelTestCase
{
	/**
	 * @return array
	 */
	protected function getData() {
		return [
			'id' => 4343,
			'name' => 'test region',
			'country' => Phake::mock(Country::class)
		];
	}

	/**
	 * @return mixed
	 */
	protected function getObject() {
		return new Region();
	}
}
