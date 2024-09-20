<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Suppliers\Sinch\Client;

use Models\Rest\RequestEnvelop;
use Phake;
use Services\Rest\AbstractRestClient;
use Services\Suppliers\Sinch\Client\RestClient;
use testing\unit\Services\Rest\AbstractRestClientUnitTest;

/**
 * Class RestClientUnitTest
 */
class RestClientUnitTest extends AbstractRestClientUnitTest
{

	/** @var string */
	private $apiToken;

	/** @var RestClient */
	private $restClient;

	/**
	 * @var array
	 */
	private $body;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		Phake::when($this->client)->setSslVerification(false)->thenReturn($this->client);
		$this->apiToken = '12312345674563534556563454';
		$this->restClient = new RestClient($this->client, $this->actions, $this->hostName, $this->apiToken);
		$this->body = ['id' => 123];
	}

	/**
	 * @param \Phake_IMock | RequestEnvelop $envelop
	 * @param string                        $action
	 * @param string                        $method
	 * @return array
	 */
	protected function buildRequestOptions(\Phake_IMock $envelop, $action, $method) {
		return [];
	}

	/**
	 * @param \Phake_IMock | RequestEnvelop $envelop
	 * @param string                        $action
	 * @param string                        $method
	 * @return array
	 */
	protected function buildRequestHeader(\Phake_IMock $envelop, $action, $method) {
		$expectedHeader = ['Authorization' => 'Bearer ' . $this->apiToken];
		if ($method === 'POST') {
			$expectedHeader['Content-Type'] = 'application/json';
		}

		return $expectedHeader;
	}

	/**
	 * @param \Phake_IMock | RequestEnvelop $envelop
	 * @param string                        $action
	 * @param string                        $method
	 * @return string
	 */
	protected function buildRequestBody(\Phake_IMock $envelop, $action, $method) {
		return json_encode($this->body);
	}

	/**
	 * @return AbstractRestClient
	 */
	protected function getConcreteRestClient() {
		return $this->restClient;
	}

	/**
	 * @return array
	 */
	public function sendDataProvider() {
		return [
			'when method is POST' => ['POST'],
			'when method is not POST' => ['GET']
		];
	}

	/**
	 * @dataProvider sendDataProvider
	 * @param string $method
	 * @return void
	 */
	public function testSend($method) {
		$action = 'test';
		$envelop = Phake::mock(RequestEnvelop::class);
		Phake::when($envelop)->getBody()->thenReturn($this->body);
		$this->assertSend($envelop, $action, $method);
	}
}
