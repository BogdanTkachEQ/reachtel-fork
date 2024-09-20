<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Suppliers\Sinch;

use Services\Rest\AbstractRestActions;
use Services\Rest\Interfaces\MorpheusRequestInterface;

/**
 * Class ApiActions
 */
class ApiActions extends AbstractRestActions
{
    const BATCH_MESSAGE_SEND_ACTION = 1;

    /**
     * @var integer
     */
    private $servicePlanId;

    /**
     * ApiActions constructor.
     * @param $servicePlanId
     */
    public function __construct($servicePlanId)
    {
        $this->servicePlanId = $servicePlanId;
    }

    /**
     * @return array
     */
    protected function getActionEndpointMap()
    {
        return [
            self::BATCH_MESSAGE_SEND_ACTION => $this->servicePlanId . '/batches'
        ];
    }

    /**
     * @return array
     */
    protected function getActionMethodMap()
    {
        return [
            self::BATCH_MESSAGE_SEND_ACTION => MorpheusRequestInterface::POST
        ];
    }
}
