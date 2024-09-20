<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rest;

use Services\Rest\AbstractRestActions;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class AbstractRestActionsUnitTest
 */
abstract class AbstractRestActionsUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return AbstractRestActions
	 */
	abstract protected function getActions();

	/**
	 * @return array
	 */
	abstract public function getEndPointByActionDataProvider();

	/**
	 * @return array
	 */
	abstract public function getMethodByActionDataProvider();

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Invalid argument passed as action
	 * @return void
	 */
	public function testGetEndPointByActionThrowsInvalidArgumentException() {
		$this->getActions()->getEndpointByAction('invalid action');
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Invalid argument passed as action
	 * @return void
	 */
	public function testMethodByActionThrowsInvalidArgumentException() {
		$this->getActions()->getMethodByAction('invalid action');
	}

	/**
	 * @dataProvider getEndPointByActionDataProvider
	 * @param mixed  $action
	 * @param string $expected
	 * @return void
	 */
	public function testGetEndPointByAction($action, $expected) {
		$endpoint = $this->getActions()->getEndpointByAction($action);
		$this->assertSameEquals($expected, $endpoint);
	}

	/**
	 * @dataProvider getMethodByActionDataProvider
	 * @param mixed  $action
	 * @param string $expected
	 * @return void
	 */
	public function testGetMethodByAction($action, $expected) {
		$method = $this->getActions()->getMethodByAction($action);
		$this->assertSameEquals($expected, $method);
	}
}
