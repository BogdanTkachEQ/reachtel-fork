<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rest;

use Guzzle\Http\Message\RequestInterface;
use Models\Rest\RequestEnvelop;
use Services\Rest\Adapter\MorpheusResponseAdapter;
use Services\Rest\Interfaces\MorpheusClientInterface;
use Services\Rest\Interfaces\MorpheusRequestInterface;
use Services\Rest\Interfaces\MorpheusResponseInterface;
use Services\Rest\Interfaces\RestActionsInterface;

/**
 * Class AbstractRestClient
 */
abstract class AbstractRestClient
{
    /**
     * @var MorpheusClientInterface
     */
    protected $client;

    /**
     * @var RestActionsInterface
     */
    protected $actions;

    /**
     * @var string
     */
    protected $hostName;

    /**
     * @var SslVersionEnum
     */
    private $enforcedSslVersion;

    /**
     * @param RequestEnvelop $requestEnvelop
     * @return array
     */
    abstract protected function buildRequestHeader(RequestEnvelop $requestEnvelop);

    /**
     * @param RequestEnvelop $requestEnvelop
     * @return mixed
     */
    abstract protected function buildRequestBody(RequestEnvelop $requestEnvelop);

    /**
     * @param RequestEnvelop $requestEnvelop
     * @return array
     */
    abstract protected function buildRequestOptions(RequestEnvelop $requestEnvelop);

    /**
     * AbstractRestClient constructor.
     * @param MorpheusClientInterface $client
     * @param RestActionsInterface    $actions
     * @param String                  $hostName
     * @param SslVersionEnum          $enforcedSslVersion
     */
    public function __construct(
        MorpheusClientInterface $client,
        RestActionsInterface $actions,
        $hostName,
        SslVersionEnum $enforcedSslVersion = null
    ) {
        $this->client = $client;
        $this->actions = $actions;
        $this->hostName = $hostName;
        $this->enforcedSslVersion = $enforcedSslVersion;
    }

    /**
     * @param RequestEnvelop $requestEnvelop
     * @return MorpheusResponseInterface
     */
    public function send(RequestEnvelop $requestEnvelop)
    {
        $request = $this->buildRequest($requestEnvelop);

        if (!is_null($this->enforcedSslVersion)) {
            $request
                ->getCurlOptions()
                ->set(CURLOPT_SSLVERSION, $this->enforcedSslVersion->getValue());
        }

        $response = $request->send();

        return new MorpheusResponseAdapter($response);
    }

    /**
     * @param RequestEnvelop $requestEnvelop
     * @return RequestInterface
     */
    protected function buildRequest(RequestEnvelop $requestEnvelop)
    {
        $options = $this->buildRequestOptions($requestEnvelop);
        if (defined('PROXY_EXTERNAL')) {
            $options = array_merge($options, ['proxy' => PROXY_EXTERNAL]);
        }

        $method = $this->actions->getMethodByAction($requestEnvelop->getAction());

        return $this
            ->client
            ->createRequest(
                $method,
                $this->hostName . '/' . $requestEnvelop->getUri($this->actions),
                $this->buildRequestHeader($requestEnvelop),
                ($method !== MorpheusRequestInterface::GET) ? $this->buildRequestBody($requestEnvelop) : null,
                $options
            );
    }
}
