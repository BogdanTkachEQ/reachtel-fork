<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models\Entities;

use Models\Entities\TimingDescriptor;
use testing\unit\AbstractModelTestCase;

/**
 * Class TimingDescriptorUnitTest
 */
class TimingDescriptorUnitTest extends AbstractModelTestCase
{
	/**
	 * @return array
	 */
	protected function getData() {
		return [
			'id' => 567,
			'name' => 'test descriptor'
		];
	}

	/**
	 * @return mixed
	 */
	protected function getObject() {
		return new TimingDescriptor();
	}
}
