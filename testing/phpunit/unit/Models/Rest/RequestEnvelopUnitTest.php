<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models\Rest;

use Models\Rest\RequestEnvelop;
use Phake;
use Services\Rest\Interfaces\RestActionsInterface;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class RequestEnvelopUnitTest
 */
class RequestEnvelopUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return RequestEnvelop
	 */
	public function testBodyIsEmpty() {
		$request = new RequestEnvelop();
		$this->assertEmpty($request->getBody());
		return $request;
	}

	/**
	 * @depends testBodyIsEmpty
	 * @param RequestEnvelop $request
	 * @return RequestEnvelop
	 */
	public function testParametersIsEmpty(RequestEnvelop $request) {
		$this->assertEmpty($request->getParameters());
		return $request;
	}

	/**
	 * @depends testParametersIsEmpty
	 * @param RequestEnvelop $request
	 * @return array
	 */
	public function testSetBody(RequestEnvelop $request) {
		$body = ['test' => 'test body'];
		$this->assertSameEquals($request, $request->setBody($body));
		return $body;
	}

	/**
	 * @depends testSetBody
	 * @depends testParametersIsEmpty
	 * @param array          $body
	 * @param RequestEnvelop $request
	 * @return RequestEnvelop
	 */
	public function testGetBody(array $body, RequestEnvelop $request) {
		$this->assertSameEquals($body, $request->getBody());
		return $request;
	}

	/**
	 * @depends testGetBody
	 * @param RequestEnvelop $request
	 * @return array
	 */
	public function testSetParameters(RequestEnvelop $request) {
		$params = ['a' => 'param1', 'b' => 'param2'];
		$this->assertSameEquals($request, $request->setParameters($params));
		return $params;
	}

	/**
	 * @depends testSetParameters
	 * @depends testGetBody
	 * @param array          $params
	 * @param RequestEnvelop $request
	 * @return RequestEnvelop
	 */
	public function testGetParameters(array $params, RequestEnvelop $request) {
		$this->assertSameEquals($params, $request->getParameters());
		return $request;
	}

	/**
	 * @depends testGetParameters
	 * @param RequestEnvelop $request
	 * @return mixed
	 */
	public function testSetAction(RequestEnvelop $request) {
		$action = 123;
		$this->assertSameEquals($request, $request->setAction($action));
		return $action;
	}

	/**
	 * @depends testSetAction
	 * @depends testGetParameters
	 * @param mixed          $action
	 * @param RequestEnvelop $request
	 * @return void
	 */
	public function testGetAction($action, RequestEnvelop $request) {
		$this->assertSameEquals($action, $request->getAction());
	}

	/**
	 * @return void
	 */
	public function testGetUri() {
		$actions = Phake::mock(RestActionsInterface::class);
		$action = 1;
		$endpoint = 'test/{id}/update/{name}';
		Phake::when($actions)->getEndpointByAction($action)->thenReturn($endpoint);
		$envelop = new RequestEnvelop();
		$envelop->setAction($action);
		$envelop->setBody(['id' => 12378, 'name' => 'test-name']);
		$this->assertSameEquals('test/12378/update/test-name', $envelop->getUri($actions));
	}

	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Action undefined and so Uri can not be generated
	 * @return void
	 */
	public function testGetUriThrowsException() {
		$envelop = new RequestEnvelop();
		$actions = Phake::mock(RestActionsInterface::class);
		$envelop->getUri($actions);
	}
}
