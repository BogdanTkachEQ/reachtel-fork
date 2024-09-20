<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models;

use Models\Sms;
use Models\SmsDeliveryReceipt;
use Services\Utils\Sms\AbstractSmsReceiptStatus;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SmsUnitTest
 */
class SmsUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return Sms
	 */
	public function testSmsDefaults() {
		$sms = new Sms();
		$this->assertNull($sms->getContent());
		$this->assertNull($sms->getFrom());
		$this->assertNull($sms->getTo());
		$this->assertNull($sms->getId());
		$this->assertNull($sms->getStatus());
		$this->assertNull($sms->getStatusUpdateTime());
		$smsDr = $sms->getDeliveryReceipt();
		$this->assertInstanceOf(SmsDeliveryReceipt::class, $smsDr);
		return $sms;
	}

	/**
	 * @depends testSmsDefaults
	 * @param Sms $sms
	 * @return string
	 */
	public function testSetFrom(Sms $sms) {
		$from = '61412345678';
		$this->assertSameEquals($sms, $sms->setFrom($from));
		return $from;
	}

	/**
	 * @depends testSmsDefaults
	 * @depends testSetFrom
	 * @param Sms    $sms
	 * @param string $from
	 * @return Sms
	 */
	public function testGetFrom(Sms $sms, $from) {
		$this->assertSameEquals($from, $sms->getFrom());
		return $sms;
	}

	/**
	 * @depends testGetFrom
	 * @param Sms $sms
	 * @return string
	 */
	public function testSetTo(Sms $sms) {
		$to = '61412345679';
		$this->assertSameEquals($sms, $sms->setTo($to));
		return $to;
	}

	/**
	 * @depends testGetFrom
	 * @depends testSetTo
	 * @param Sms    $sms
	 * @param string $to
	 * @return Sms
	 */
	public function testGetTo(Sms $sms, $to) {
		$this->assertSameEquals($to, $sms->getTo());
		return $sms;
	}

	/**
	 * @depends testGetTo
	 * @param Sms $sms
	 * @return string
	 */
	public function testSetContent(Sms $sms) {
		$content = 'test content';
		$this->assertSameEquals($sms, $sms->setContent($content));
		return $content;
	}

	/**
	 * @depends testGetTo
	 * @depends testSetContent
	 * @param Sms    $sms
	 * @param string $content
	 * @return Sms
	 */
	public function testGetContent(Sms $sms, $content) {
		$this->assertSameEquals($content, $sms->getContent());
		return $sms;
	}

	/**
	 * @depends testGetContent
	 * @param Sms $sms
	 * @return integer
	 */
	public function testSetId(Sms $sms) {
		$id = 123;
		$this->assertSameEquals($sms, $sms->setId($id));
		return $id;
	}

	/**
	 * @depends testGetContent
	 * @depends testSetId
	 * @param Sms     $sms
	 * @param integer $id
	 * @return Sms
	 */
	public function testGetId(Sms $sms, $id) {
		$this->assertSameEquals($id, $sms->getId());
		$this->assertSameEquals($id, $sms->getDeliveryReceipt()->getSmsId());
		return $sms;
	}

	/**
	 * @depends testGetId
	 * @param Sms $sms
	 * @return \DateTime
	 */
	public function testSetStatusUpdateTime(Sms $sms) {
		$dateTime = \DateTime::createFromFormat('d-m-Y H:i:s', '20-09-2019 10:00:06');
		$this->assertSameEquals($sms, $sms->setStatusUpdateTime($dateTime));
		return $dateTime;
	}

	/**
	 * @depends testGetId
	 * @depends testSetStatusUpdateTime
	 * @param Sms       $sms
	 * @param \DateTime $dateTime
	 * @return Sms
	 */
	public function testGetStatusUpdateTime(Sms $sms, \DateTime $dateTime) {
		$this->assertSameEquals($dateTime, $sms->getStatusUpdateTime());
		$this->assertSameEquals($dateTime, $sms->getDeliveryReceipt()->getStatusUpdateDateTime());
		return $sms;
	}

	/**
	 * @depends testGetStatusUpdateTime
	 * @param Sms $sms
	 * @return AbstractSmsReceiptStatus
	 */
	public function testSetStatus(Sms $sms) {
		$status = \Phake::mock(AbstractSmsReceiptStatus::class);
		$this->assertSameEquals($sms, $sms->setStatus($status));
		return $status;
	}

	/**
	 * @depends testGetStatusUpdateTime
	 * @depends testSetStatus
	 * @param Sms                      $sms
	 * @param AbstractSmsReceiptStatus $status
	 * @return Sms
	 */
	public function testGetStatus(Sms $sms, AbstractSmsReceiptStatus $status) {
		$this->assertSameEquals($status, $sms->getStatus());
		return $sms;
	}

	/**
	 * @depends testGetStatus
	 * @param Sms $sms
	 * @return integer
	 */
	public function testSetSupplierId(Sms $sms) {
		$supplierId = 123;
		$this->assertSameEquals($sms, $sms->setSupplierId($supplierId));
		return $supplierId;
	}

	/**
	 * @depends testSetSupplierId
	 * @depends testGetStatus
	 * @param integer $supplierId
	 * @param Sms     $sms
	 * @return void
	 */
	public function testGetSupplierId($supplierId, Sms $sms) {
		$this->assertSameEquals($supplierId, $sms->getSupplierId());
		$this->assertSameEquals($supplierId, $sms->getDeliveryReceipt()->getSupplierId());
	}
}
