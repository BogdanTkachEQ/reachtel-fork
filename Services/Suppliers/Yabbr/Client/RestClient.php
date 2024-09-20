<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Suppliers\Yabbr\Client;

use Models\Rest\RequestEnvelop;
use Services\Rest\AbstractRestClient;
use Services\Rest\Interfaces\MorpheusClientInterface;
use Services\Rest\Interfaces\MorpheusRequestInterface;
use Services\Rest\Interfaces\RestActionsInterface;
use Services\Rest\SslVersionEnum;

/**
 * Class RestClient
 */
class RestClient extends AbstractRestClient
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * RestClient constructor.
     * @param MorpheusClientInterface $client
     * @param RestActionsInterface    $actions
     * @param string                  $hostName
     * @param string                  $apiKey
     */
    public function __construct(
        MorpheusClientInterface $client,
        RestActionsInterface $actions,
        $hostName,
        $apiKey
    ) {
        $this->apiKey = $apiKey;
        parent::__construct($client, $actions, $hostName, SslVersionEnum::TLS1_2());
    }

    /**
     * @param RequestEnvelop $requestEnvelop
     * @return array
     */
    protected function buildRequestHeader(RequestEnvelop $requestEnvelop)
    {
        $header = [
            'x-api-key' => $this->apiKey,
        ];
        $method = $this->actions->getMethodByAction($requestEnvelop->getAction());

        if ($method === MorpheusRequestInterface::POST) {
            $header['Content-Type'] = 'application/json';
        }

        return $header;
    }

    /**
     * @param RequestEnvelop $requestEnvelop
     * @return mixed
     */
    protected function buildRequestBody(RequestEnvelop $requestEnvelop)
    {
        return json_encode($requestEnvelop->getBody());
    }

    /**
     * @param RequestEnvelop $requestEnvelop
     * @return array
     */
    protected function buildRequestOptions(RequestEnvelop $requestEnvelop)
    {
        return [];
    }
}
