<?php

namespace Services\Validators;

use Services\Validators\Interfaces\RunControllerInterface;

/**
 * Class PublicHolidayRunController
 */
class PublicHolidayRunController implements RunControllerInterface
{
    /**
     * @var string
     */
    private $region;

    /**
     * @var \DateTime
     */
    private $dateTime;

    /**
     * PublicHolidayRunController constructor.
     * @param \DateTime|null $dateTime
     * @param string         $region
     */
    public function __construct(\DateTime $dateTime = null, $region = 'AU')
    {
        $this->region = $region;
        $this->dateTime = $dateTime ? : new \DateTime();
    }

    /**
     * @return boolean
     */
    public function stopRun()
    {
        return api_misc_ispublicholiday($this->region, $this->dateTime->getTimestamp());
    }

    /**
     * @return string
     */
    public function getStopReason()
    {
        return 'Public Holiday';
    }
}
