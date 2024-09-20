<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rest\Adapter;

use Guzzle\Http\Message\Response;
use Services\Rest\Interfaces\MorpheusResponseInterface;

/**
 * Class MorpheusResponseAdapter
 */
class MorpheusResponseAdapter implements MorpheusResponseInterface
{
    /**
     * @var Response
     */
    private $response;

    /**
     * MorpheusResponseAdapter constructor.
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->response->getBody();
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->response->getHeaders();
    }

    /**
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->response->getMessage();
    }
}
