<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rest;

use Guzzle\Common\Collection;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Models\Rest\RequestEnvelop;
use Phake;
use Services\Rest\AbstractRestClient;
use Services\Rest\Adapter\MorpheusResponseAdapter;
use Services\Rest\Interfaces\MorpheusClientInterface;
use Services\Rest\Interfaces\RestActionsInterface;
use Services\Rest\SslVersionEnum;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class AbstractRestClientUnitTest
 */
abstract class AbstractRestClientUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @var RestActionsInterface | \Phake_IMock
	 */
	protected $actions;

	/**
	 * @var MorpheusClientInterface | \Phake_IMock
	 */
	protected $client;

	/**
	 * @var string
	 */
	protected $hostName;

	/**
	 * @param \Phake_IMock | RequestEnvelop $envelop
	 * @param string                        $action
	 * @param string                        $method
	 * @return array
	 */
	abstract protected function buildRequestOptions(\Phake_IMock $envelop, $action, $method);

	/**
	 * @param \Phake_IMock | RequestEnvelop $envelop
	 * @param string                        $action
	 * @param string                        $method
	 * @return array
	 */
	abstract protected function buildRequestHeader(\Phake_IMock $envelop, $action, $method);

	/**
	 * @param \Phake_IMock | RequestEnvelop $envelop
	 * @param string                        $action
	 * @param string                        $method
	 * @return string
	 */
	abstract protected function buildRequestBody(\Phake_IMock $envelop, $action, $method);

	/**
	 * @return AbstractRestClient
	 */
	abstract protected function getConcreteRestClient();

	/**
	 * @return void
	 */
	public function setUp() {
		$this->actions = Phake::mock(RestActionsInterface::class);
		$this->client = Phake::mock(MorpheusClientInterface::class);
		$this->hostName = 'http://test.com';
	}

	/**
	 * @param \Phake_IMock | RequestEnvelop $envelop
	 * @param string                        $action
	 * @param string                        $method
	 * @param SslVersionEnum                $sslVersionEnum
	 * @return void
	 */
	protected function assertSend(\Phake_IMock $envelop, $action, $method, SslVersionEnum $sslVersionEnum = null) {
		$response = Phake::mock(Response::class);
		$uri = 'test/123';
		Phake::when($envelop)->getAction()->thenReturn($action);
		Phake::when($envelop)->getUri($this->actions)->thenReturn($uri);
		Phake::when($this->actions)->getMethodByAction($action)->thenReturn($method);

		$this->mock_function_param_value(
			'defined',
			[
				['params' => ['PROXY_EXTERNAL'], 'return' => false]
			],
			true
		);

		$request = Phake::mock(RequestInterface::class);

		$curlOptions = Phake::mock(Collection::class);
		Phake::when($request)->getCurlOptions()->thenReturn($curlOptions);

		Phake::when($request)->send()->thenReturn($response);
		Phake::when($this->client)
			->createRequest(
				Phake::capture($methodReturned),
				Phake::capture($actualUri),
				Phake::capture($requestHeader),
				Phake::capture($requestBody),
				Phake::capture($options)
			)
			->thenReturn($request);

		/** @var MorpheusResponseAdapter $return */
		$return = $this->getConcreteRestClient()->send($envelop);
		$this->assertInstanceOf(MorpheusResponseAdapter::class, $return);
		$this->assertSameEquals($return->getResponse(), $response);

		$this->assertSameEquals($this->hostName . '/' . $uri, $actualUri);
		$expectedOptions = $this->buildRequestOptions($envelop, $action, $method);
		$this->assertSameEquals($expectedOptions, $options);

		$expectedHeader = $this->buildRequestHeader($envelop, $action, $method);
		$this->assertSameEquals($expectedHeader, $requestHeader);

		$expectedBody = $method === 'GET' ? null : $this->buildRequestBody($envelop, $action, $method);
		$this->assertSameEquals($expectedBody, $requestBody);

		if (!is_null($sslVersionEnum)) {
			Phake::verify($curlOptions)->set(CURLOPT_SSLVERSION, $sslVersionEnum->getValue());
		} else {
			Phake::verify($curlOptions, Phake::never())->set(CURLOPT_SSLVERSION, Phake::ignoreRemaining());
		}

		$this->remove_mocked_functions('defined');
	}
}
