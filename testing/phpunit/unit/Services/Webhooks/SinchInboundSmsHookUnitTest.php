<?php
/**
 * @author       rohith.mohan@equifax.com
 * @copyright    ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Webhooks;

use Models\Sms;
use Phake;
use Services\Http\Request;
use Services\Sms\InboundSmsProcessor;
use Services\Webhooks\SinchInboundSmsHook;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SinchInboundSmsHookUnitTest
 */
class SinchInboundSmsHookUnitTest extends AbstractPhpunitUnitTest
{
	/** @var InboundSmsProcessor | \Phake_IMock*/
	private $processor;

	/** @var SinchInboundSmsHook */
	private $hook;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->processor = Phake::mock(InboundSmsProcessor::class);
		$this->hook = new SinchInboundSmsHook($this->processor);
	}

	/**
	 * @return void
	 */
	public function testRunQueuedJob() {
		$attributes = [
			'from' => '61423456789',
			'to' => '61412345679',
			'body' => 'test body',
			'received_at' => '2019-10-05T23:59:59.000Z'
		];

		Phake::when($this->processor)->saveSms(Phake::capture($sms))->thenReturn(true);
		$this->assertTrue($this->hook->runQueuedJob($attributes));
		$this->assertInstanceOf(Sms::class, $sms);
		$this->assertSameEquals($attributes['from'], $sms->getFrom());
		$this->assertSameEquals($attributes['to'], $sms->getTo());
		$this->assertSameEquals($attributes['body'], $sms->getContent());
		$this->assertEquals(
			\DateTime::createFromFormat(
				'Y-m-d\TH:i:s.uO',
				$attributes['received_at']
			),
			$sms->getStatusUpdateTime()
		);
	}

	/**
	 * @return void
	 */
	public function testGetHookAttributesForQueueing() {
		$attributes = [
			'from' => '61423456789',
			'to' => '61412345679',
			'body' => 'test body',
			'received_at' => '2019-10-05T23:59:59.000Z'
		];

		$request = Phake::mock(Request::class);
		Phake::when($request)->getContent()->thenReturn(json_encode($attributes));
		$this->assertSameEquals($attributes, $this->hook->getHookAttributesForQueueing($request));
	}
}
