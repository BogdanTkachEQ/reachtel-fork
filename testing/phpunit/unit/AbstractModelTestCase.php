<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Class AbstractModelTestCase
 */
abstract class AbstractModelTestCase extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	abstract protected function getData();

	/**
	 * @return mixed
	 */
	abstract protected function getObject();

	/**
	 * @return void
	 */
	public function test() {
		$object = $this->getObject();
		foreach ($this->getData() as $property => $value) {
			$getterPrefix = is_bool($value) ? 'is' : 'get';

			$setter = 'set' . ucfirst($property);
			$getter = $getterPrefix . ucfirst($property);
			if (!method_exists($object, $setter) || !method_exists($object, $getter)) {
				$this->fail('Getter or setter missing');
			}

			$this->assertInstanceOf(get_class($object), $object->{$setter}($value));
			$this->assertEquals(
				$value,
				$object->{$getter}(),
				'Failed assertions for ' . $property
			);
		}
	}
}
