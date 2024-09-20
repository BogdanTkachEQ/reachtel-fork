<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models;

use Models\Interfaces\CampaignTimingRangeInterface;
use Services\Exceptions\Validators\InvalidSpecificTimeException;

/**
 * Class CampaignSpecificTime
 */
class CampaignSpecificTime implements CampaignTimingRangeInterface
{
    const STATUS_CURRENT = 0;
    const STATUS_PAST = -1;
    const STATUS_FUTURE = 1;

    /** @var \DateTime */
    private $startDateTime;

    /** @var \DateTime */
    private $endDateTime;

    /** @var integer */
    private $status;

    /**
     * @return \DateTime
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * @param \DateTime $startDateTime
     * @return $this
     */
    public function setStartDateTime($startDateTime)
    {
        $this->startDateTime = $startDateTime;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndDateTime()
    {
        return $this->endDateTime;
    }

    /**
     * @param \DateTime $endDateTime
     * @return $this
     */
    public function setEndDateTime($endDateTime)
    {
        $this->endDateTime = $endDateTime;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * TODO: Refactor function api_restrictions_time_specific_listall and the specifictime director so that the status is determined here
     * and remove this setter
     * @param int $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return boolean
     * @throws InvalidSpecificTimeException
     */
    public function validate()
    {
        if (is_null($this->startDateTime) || is_null($this->endDateTime)) {
            throw new InvalidSpecificTimeException('Start and end date can not be empty');
        }

        if ($this->startDateTime->diff($this->endDateTime)->format('%a')) {
            throw new InvalidSpecificTimeException('Start & end dates are not on the same calendar day');
        }

        if ($this->startDateTime >= $this->endDateTime) {
            throw new InvalidSpecificTimeException('Start date can not be greater than or equal to end date');
        }

        return true;
    }

    /**
     * @param \DateTime $dateTime
     * @return boolean
     * @throws InvalidSpecificTimeException
     */
    public function isValidDateTime(\DateTime $dateTime)
    {
        // Not running validate here to be backwards compatible with old function in api_restrictions.
        // There can be bad data that should not be affected with this check.
        return ($this->startDateTime <= $dateTime && $this->endDateTime > $dateTime);
    }
}
