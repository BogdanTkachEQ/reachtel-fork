<?php

namespace Models;

use Services\Utils\Sms\AbstractSmsReceiptStatus;

/**
 * Class Sms
 */
class Sms
{
    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $to;

    /**
     * @var string
     */
    private $content;

    /**
     * @var string|integer
     */
    private $id;

    /** @var SmsDeliveryReceipt */
    private $deliveryReceipt;

    public function __construct()
    {
        $this->deliveryReceipt = new SmsDeliveryReceipt();
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param string $from
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param string $to
     * @return $this
     */
    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        $this->deliveryReceipt->setSmsId($id);
        return $this;
    }

    /**
     * @return AbstractSmsReceiptStatus
     */
    public function getStatus()
    {
        return $this->deliveryReceipt->getStatus();
    }

    /**
     * @param AbstractSmsReceiptStatus $status
     * @return $this
     */
    public function setStatus(AbstractSmsReceiptStatus $status)
    {
        $this->deliveryReceipt->setStatus($status);
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStatusUpdateTime()
    {
        return $this->deliveryReceipt->getStatusUpdateDateTime();
    }

    /**
     * @param \DateTime $statusUpdateTime
     * @return $this
     */
    public function setStatusUpdateTime(\DateTime $statusUpdateTime = null)
    {
        $this->deliveryReceipt->setStatusUpdateDateTime($statusUpdateTime);
        return $this;
    }

    /**
     * @return int
     */
    public function getSupplierId()
    {
        return $this->deliveryReceipt->getSupplierId();
    }

    /**
     * @param int $supplierId
     * @return $this
     */
    public function setSupplierId($supplierId)
    {
        $this->deliveryReceipt->setSupplierId($supplierId);
        return $this;
    }

    /**
     * @return SmsDeliveryReceipt
     */
    public function getDeliveryReceipt() {
        return $this->deliveryReceipt;
    }
}
