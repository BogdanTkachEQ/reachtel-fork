<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models;

use Models\SmsDeliveryReceipt;
use Services\Utils\Sms\AbstractSmsReceiptStatus;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SmsDeliveryReceiptUnitTest
 */
class SmsDeliveryReceiptUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return SmsDeliveryReceipt
	 */
	public function testDefaults() {
		$deliveryReceipt = new SmsDeliveryReceipt();
		$this->assertNull($deliveryReceipt->getSupplierId());
		$this->assertNull($deliveryReceipt->getSmsId());
		$this->assertNull($deliveryReceipt->getStatus());
		$this->assertNull($deliveryReceipt->getStatusUpdateDateTime());
		$this->assertNull($deliveryReceipt->getErrorCode());
		return $deliveryReceipt;
	}

	/**
	 * @depends testDefaults
	 * @param SmsDeliveryReceipt $deliveryReceipt
	 * @return integer
	 */
	public function testSetSupplierId(SmsDeliveryReceipt $deliveryReceipt) {
		$supplierId = 1234;
		$this->assertSameEquals($deliveryReceipt, $deliveryReceipt->setSupplierId($supplierId));
		return $supplierId;
	}

	/**
	 * @depends testDefaults
	 * @depends testSetSupplierId
	 * @param SmsDeliveryReceipt $deliveryReceipt
	 * @param integer            $supplierId
	 * @return SmsDeliveryReceipt
	 */
	public function testGetSupplierId(SmsDeliveryReceipt $deliveryReceipt, $supplierId) {
		$this->assertSameEquals($supplierId, $deliveryReceipt->getSupplierId());
		return $deliveryReceipt;
	}

	/**
	 * @depends testGetSupplierId
	 * @param SmsDeliveryReceipt $deliveryReceipt
	 * @return AbstractSmsReceiptStatus
	 */
	public function testSetStatus(SmsDeliveryReceipt $deliveryReceipt) {
		$status = \Phake::mock(AbstractSmsReceiptStatus::class);
		$this->assertSameEquals($deliveryReceipt, $deliveryReceipt->setStatus($status));
		return $status;
	}

	/**
	 * @depends testSetStatus
	 * @depends testGetSupplierId
	 * @param AbstractSmsReceiptStatus $status
	 * @param SmsDeliveryReceipt       $deliveryReceipt
	 * @return SmsDeliveryReceipt
	 */
	public function testGetStatus(AbstractSmsReceiptStatus $status, SmsDeliveryReceipt $deliveryReceipt) {
		$this->assertSameEquals($status, $deliveryReceipt->getStatus());
		return $deliveryReceipt;
	}

	/**
	 * @depends testGetStatus
	 * @param SmsDeliveryReceipt $deliveryReceipt
	 * @return integer
	 */
	public function testSetSmsId(SmsDeliveryReceipt $deliveryReceipt) {
		$smsId = 4334;
		$this->assertSameEquals($deliveryReceipt, $deliveryReceipt->setSmsId($smsId));
		return $smsId;
	}

	/**
	 * @depends testGetStatus
	 * @depends testSetSmsId
	 * @param SmsDeliveryReceipt $deliveryReceipt
	 * @param integer            $smsId
	 * @return SmsDeliveryReceipt
	 */
	public function testGetSmsId(SmsDeliveryReceipt $deliveryReceipt, $smsId) {
		$this->assertSameEquals($smsId, $deliveryReceipt->getSmsId());
		return $deliveryReceipt;
	}

	/**
	 * @depends testGetSmsId
	 * @param SmsDeliveryReceipt $deliveryReceipt
	 * @return \DateTime
	 */
	public function testSetStatusUpdateDateTime(SmsDeliveryReceipt $deliveryReceipt) {
		$dateTime = new \DateTime();
		$this->assertSameEquals($deliveryReceipt, $deliveryReceipt->setStatusUpdateDateTime($dateTime));
		return $dateTime;
	}

	/**
	 * @depends testGetSmsId
	 * @depends testSetStatusUpdateDateTime
	 * @param SmsDeliveryReceipt $deliveryReceipt
	 * @param \DateTime          $dateTime
	 * @return void
	 */
	public function testGetStatusUpdateDateTime(SmsDeliveryReceipt $deliveryReceipt, \DateTime $dateTime) {
		$this->assertSameEquals($dateTime, $deliveryReceipt->getStatusUpdateDateTime());
	}
}
