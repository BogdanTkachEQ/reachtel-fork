<?php

namespace Services\Validators;

use Services\Validators\Interfaces\RunControllerInterface;

/**
 * Class DailyRunController
 */
class DailyRunController implements RunControllerInterface
{
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;
    const SUNDAY = 7;

    /**
     * @var array
     */
    private $stopDays = [];

    /**
     * @var \DateTime
     */
    private $dateTime;

    /**
     * @var string
     */
    private $stopReason;

    public function __construct(\DateTime $dateTime = null)
    {
        $this->dateTime = $dateTime ? : new \DateTime();
    }

    public function setStopDay($day)
    {
        $this->stopDays[] = $day;
        return $this;
    }

    /**
     * @return boolean
     */
    public function stopRun()
    {
        $today = $this->dateTime->format('w');

        foreach ($this->stopDays as $day) {
            if ($today === $day) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getStopReason()
    {
        return $this->stopReason;
    }

    /**
     * @param string $stopReason
     * @return DailyRunController
     */
    public function setStopReason($stopReason)
    {
        $this->stopReason = $stopReason;
        return $this;
    }
}
