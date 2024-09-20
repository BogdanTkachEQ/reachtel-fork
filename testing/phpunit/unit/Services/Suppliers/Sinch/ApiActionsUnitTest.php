<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Suppliers\Sinch;

use Services\Suppliers\Sinch\ApiActions as SinchApiActions;
use testing\unit\Services\Rest\AbstractRestActionsUnitTest;

/**
 * Class ApiActionsUnitTest
 */
class ApiActionsUnitTest extends AbstractRestActionsUnitTest
{
	const SERVICE_PLAN_ID = 123;
	/**
	 * @return SinchApiActions
	 */
	protected function getActions() {
		return new SinchApiActions(self::SERVICE_PLAN_ID);
	}

	/**
	 * @return array
	 */
	public function getEndPointByActionDataProvider() {
		return [
			'batch message send action' => [1, '123/batches']
		];
	}

	/**
	 * @return array
	 */
	public function getMethodByActionDataProvider() {
		return [
			'batch message send action' => [1, 'POST']
		];
	}
}
