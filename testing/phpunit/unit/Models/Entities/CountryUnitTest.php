<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Models\Entities\Country;
use testing\unit\AbstractModelTestCase;

/**
 * Class CountryUnitTest
 */
class CountryUnitTest extends AbstractModelTestCase
{
	/**
	 * @return array
	 */
	protected function getData() {
		return [
			'id' => 454,
			'name' => 'Australia',
			'shortName' => 'AU',
			'regions' => \Phake::mock(ArrayCollection::class)
		];
	}

	/**
	 * @return mixed
	 */
	protected function getObject() {
		return new Country();
	}
}
