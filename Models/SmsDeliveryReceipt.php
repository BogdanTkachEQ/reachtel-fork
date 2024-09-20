<?php

namespace Models;

use Services\Utils\Sms\AbstractSmsReceiptStatus;

/**
 * Class SmsDeliveryReceipt
 */
class SmsDeliveryReceipt
{
    /** @var string */
    private $supplierId;

    /** @var integer */
    private $smsId;

    /** @var AbstractSmsReceiptStatus */
    private $status;

    /** @var  \DateTime */
    private $statusUpdateDateTime;

    /** @var string */
    private $errorCode;

    /**
     * @return string
     */
    public function getSupplierId()
    {
        return $this->supplierId;
    }

    /**
     * @param string $supplierId
     * @return $this
     */
    public function setSupplierId($supplierId)
    {
        $this->supplierId = $supplierId;
        return $this;
    }

    /**
     * @return int
     */
    public function getSmsId()
    {
        return $this->smsId;
    }

    /**
     * @param int $smsId
     * @return $this
     */
    public function setSmsId($smsId)
    {
        $this->smsId = $smsId;
        return $this;
    }

    /**
     * @return AbstractSmsReceiptStatus
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param AbstractSmsReceiptStatus $status
     * @return $this
     */
    public function setStatus(AbstractSmsReceiptStatus $status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStatusUpdateDateTime()
    {
        return $this->statusUpdateDateTime;
    }

    /**
     * @param \DateTime $statusUpdateDateTime
     * @return $this
     */
    public function setStatusUpdateDateTime(\DateTime $statusUpdateDateTime = null)
    {
        $this->statusUpdateDateTime = $statusUpdateDateTime;
        return $this;
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param string $errorCode
     * @return $this
     */
    public function setErrorCode($errorCode = null)
    {
        $this->errorCode = $errorCode;
        return $this;
    }
}
