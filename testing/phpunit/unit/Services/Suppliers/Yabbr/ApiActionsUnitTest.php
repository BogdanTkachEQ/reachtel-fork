<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Suppliers\Yabbr;

use Services\Suppliers\Yabbr\ApiActions as YabbrApiActions;
use testing\unit\Services\Rest\AbstractRestActionsUnitTest;

/**
 * Class ApiActionsUnitTest
 */
class ApiActionsUnitTest extends AbstractRestActionsUnitTest
{
	/**
	 * @return YabbrApiActions
	 */
	protected function getActions() {
		return new YabbrApiActions();
	}

	/**
	 * @return array
	 */
	public function getEndPointByActionDataProvider() {
		return [
			'send messages action' => [1, 'messages'],
			'retrieve message action' => [2, 'messages/{id}'],
			'retrieve messages action' => [3, 'messages']
		];
	}

	/**
	 * @return array
	 */
	public function getMethodByActionDataProvider() {
		return [
			'send messages action' => [1, 'POST'],
			'retrieve message action' => [2, 'GET'],
			'retrieve messages action' => [3, 'GET']
		];
	}
}
