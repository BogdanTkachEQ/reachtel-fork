<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rest;

use Services\Rest\Interfaces\RestActionsInterface;

/**
 * Class AbstractRestActions
 */
abstract class AbstractRestActions implements RestActionsInterface
{
    /**
     * @param string $action
     * @return string
     */
    public function getEndpointByAction($action)
    {
        $actionEndpointMap = $this->getActionEndpointMap();
        if (!isset($actionEndpointMap[$action])) {
            throw new \InvalidArgumentException('Invalid argument passed as action');
        }

        return $actionEndpointMap[$action];
    }

    /**
     * @param string $action
     * @return string
     */
    public function getMethodByAction($action)
    {
        $actionMethodMap = $this->getActionMethodMap();
        if (!isset($actionMethodMap[$action])) {
            throw new \InvalidArgumentException('Invalid argument passed as action');
        }

        return $actionMethodMap[$action];
    }

    /**
     * @return array
     */
    abstract protected function getActionEndpointMap();

    /**
     * @return array
     */
    abstract protected function getActionMethodMap();
}
