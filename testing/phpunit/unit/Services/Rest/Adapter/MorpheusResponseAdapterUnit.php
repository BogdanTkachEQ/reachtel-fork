<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rest\Adapter;

use Guzzle\Http\Message\Response;
use Phake;
use Services\Rest\Adapter\MorpheusResponseAdapter;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class MorpheusResponseAdapter
 */
class MorpheusResponseAdapterUnitTest extends AbstractPhpunitUnitTest
{
	/** @var Response | \Phake_IMock */
	private $response;

	/** @var MorpheusResponseAdapter */
	private $adapter;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->response = Phake::mock(Response::class);
		$this->adapter = new MorpheusResponseAdapter($this->response);
	}

	/**
	 * @return void
	 */
	public function testGetResponse() {
		$this->assertSameEquals($this->response, $this->adapter->getResponse());
	}

	/**
	 * @return void
	 */
	public function testGetBody() {
		$body = 'test body';
		Phake::when($this->response)->getBody()->thenReturn($body);
		$this->assertSameEquals($body, $this->adapter->getBody());
	}

	/**
	 * @return void
	 */
	public function testGetHeaders() {
		$headers = ['header' => 'value'];
		Phake::when($this->response)->getHeaders()->thenReturn($headers);
		$this->assertSameEquals($headers, $this->adapter->getHeaders());
	}

	/**
	 * @return void
	 */
	public function testGetStatusCode() {
		$statuscode = 200;
		Phake::when($this->response)->getStatusCode()->thenReturn($statuscode);
		$this->assertSameEquals($statuscode, $this->adapter->getStatusCode());
	}

	/**
	 * @return void
	 */
	public function testGetMessage() {
		$message = 'test message';
		Phake::when($this->response)->getMessage()->thenReturn($message);
		$this->assertSameEquals($message, $this->adapter->getMessage());
	}
}
