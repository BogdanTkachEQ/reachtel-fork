<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rest\Interfaces;

/**
 * Interface RestActionsInterface
 */
interface RestActionsInterface
{
    /**
     * @param string $action
     * @return string
     */
    public function getEndpointByAction($action);

    /**
     * @param string $action
     * @return string
     */
    public function getMethodByAction($action);
}
