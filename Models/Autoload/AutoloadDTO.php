<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models\Autoload;

/**
 * Class AutoloadDTO
 */
class AutoloadDTO
{
    /** @var string */
    private $destinationColumnName;

    /** @var string */
    private $targetKeyColumnName;

    /** @var string */
    private $callDateColumnName;

    /** @var string */
    private $nextAttemptTime = '';

    /** @var integer */
    private $sendRateHourBuffer = 1;

    /** @var array */
    private $alternativeDestinationColumnNames = [];

    /**
     * @return string
     */
    public function getDestinationColumnName()
    {
        return $this->destinationColumnName;
    }

    /**
     * @param string $destinationColumnName
     * @return $this
     */
    public function setDestinationColumnName($destinationColumnName)
    {
        $this->destinationColumnName = $destinationColumnName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTargetKeyColumnName()
    {
        return $this->targetKeyColumnName;
    }

    /**
     * @param string $targetKeyColumnName
     * @return $this
     */
    public function setTargetKeyColumnName($targetKeyColumnName)
    {
        $this->targetKeyColumnName = $targetKeyColumnName;
        return $this;
    }

    /**
     * @return string
     */
    public function getCallDateColumnName()
    {
        return $this->callDateColumnName;
    }

    /**
     * @param string $callDateColumnName
     * @return $this
     */
    public function setCallDateColumnName($callDateColumnName)
    {
        $this->callDateColumnName = $callDateColumnName;
        return $this;
    }

    /**
     * @return string
     */
    public function getNextAttemptTime()
    {
        return $this->nextAttemptTime;
    }

    /**
     * @param string $nextAttemptTime
     * @return $this
     */
    public function setNextAttemptTime($nextAttemptTime)
    {
        $this->nextAttemptTime = $nextAttemptTime;
        return $this;
    }

    /**
     * @return integer
     */
    public function getSendRateHourBuffer()
    {
        return $this->sendRateHourBuffer;
    }

    /**
     * @param integer $sendRateHourBuffer
     * @return $this
     */
    public function setSendRateHourBuffer($sendRateHourBuffer)
    {
        $this->sendRateHourBuffer = $sendRateHourBuffer;
        return $this;
    }

    /**
     * @return array
     */
    public function getAlternativeDestinationColumnNames()
    {
        return $this->alternativeDestinationColumnNames;
    }

    /**
     * @param array $alternativeDestinationColumnNames
     * @return $this
     */
    public function setAlternativeDestinationColumnNames($alternativeDestinationColumnNames)
    {
        $this->alternativeDestinationColumnNames = $alternativeDestinationColumnNames;
        return $this;
    }
}
