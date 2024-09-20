<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Suppliers\Yabbr;

use Services\Rest\AbstractRestActions;
use Services\Rest\Interfaces\MorpheusRequestInterface;

/**
 * Class ApiActions
 */
class ApiActions extends AbstractRestActions
{
    const SEND_MESSAGES_ACTION = 1;
    const RETRIEVE_MESSAGE_ACTION = 2;
    const RETRIEVE_MESSAGES_ACTION = 3;
    const POST_NUMBER_VALIDATION = 4;
    const RETRIEVE_NUMBER_VALIDATION = 5;

    /**
     * @return array
     */
    protected function getActionEndpointMap()
    {
        return [
            self::SEND_MESSAGES_ACTION => 'messages',
            self::RETRIEVE_MESSAGE_ACTION => 'messages/{id}',
            self::RETRIEVE_MESSAGES_ACTION => 'messages',
            self::POST_NUMBER_VALIDATION => 'validations',
            self::RETRIEVE_NUMBER_VALIDATION => 'validations/{id}'
        ];
    }

    /**
     * @return array
     */
    protected function getActionMethodMap()
    {
        return [
            self::SEND_MESSAGES_ACTION => MorpheusRequestInterface::POST,
            self::RETRIEVE_MESSAGE_ACTION => MorpheusRequestInterface::GET,
            self::RETRIEVE_MESSAGES_ACTION => MorpheusRequestInterface::GET,
            self::POST_NUMBER_VALIDATION => MorpheusRequestInterface::POST,
            self::RETRIEVE_NUMBER_VALIDATION => MorpheusRequestInterface::GET
        ];
    }
}
