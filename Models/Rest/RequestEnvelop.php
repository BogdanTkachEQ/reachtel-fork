<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Models\Rest;

use Services\Rest\Interfaces\RestActionsInterface;

/**
 * Class Request
 * @package Models\Rest
 */
class RequestEnvelop
{
    /**
     * @var array
     */
    private $body = [];

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var integer
     */
    private $action;

    /**
     * @return array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param array $body
     * @return $this
     */
    public function setBody(array $body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @return integer
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param integer $action
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @param RestActionsInterface $actions
     * @return string
     * @throws \RuntimeException
     */
    public function getUri(RestActionsInterface $actions) {
        if (is_null($this->getAction())) {
            throw new \RuntimeException('Action undefined and so Uri can not be generated');
        }
        $endpoint = $actions->getEndpointByAction($this->action);

        if ($this->getBody()) {
            $placeHolders = array_filter($this->getBody(), 'is_scalar');
            $keys = array_map(function($key) {
                return '{' . $key . '}';
            }, array_keys($placeHolders));

            $endpoint = str_ireplace($keys, array_values($placeHolders), $endpoint);
        }

        return $endpoint;
    }
}
