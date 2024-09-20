<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Suppliers\Sinch\Client;

use Models\Rest\RequestEnvelop;
use Services\Rest\AbstractRestClient;
use Services\Rest\Interfaces\MorpheusClientInterface;
use Services\Rest\Interfaces\MorpheusRequestInterface;
use Services\Rest\Interfaces\RestActionsInterface;

/**
 * Class RestClient
 * @package Services\Suppliers\Sinch\Client
 */
class RestClient extends AbstractRestClient
{
    /**
     * @var string
     */
    private $apiToken;

    public function __construct(
        MorpheusClientInterface $client,
        RestActionsInterface $actions,
        $hostName,
        $apiToken
    ) {
        $this->apiToken = $apiToken;
        $client->setSslVerification(false);
        parent::__construct($client, $actions, $hostName);
    }

    /**
     * @param RequestEnvelop $requestEnvelop
     * @return array
     */
    protected function buildRequestHeader(RequestEnvelop $requestEnvelop)
    {
        $method = $this->actions->getMethodByAction($requestEnvelop->getAction());
        $header = [
            'Authorization' => 'Bearer ' . $this->apiToken,
        ];

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
