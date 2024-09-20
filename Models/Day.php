<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models;

use MabeEnum\Enum;

/**
 * Class Day
 */
class Day extends Enum
{
    const MONDAY = 0;
    const TUESDAY = 1;
    const WEDNESDAY = 2;
    const THURSDAY = 3;
    const FRIDAY = 4;
    const SATURDAY = 5;
    const SUNDAY = 6;

    /**
     * @param \DateTime $dateTime
     * @return Day
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public static function byDateTime(\DateTime $dateTime)
    {
        $dayName = $dateTime->format('l');
        return static::byName(strtoupper($dayName));
    }

    /**
     * @return string
     */
    public function getDateTimeDayName()
    {
        return $this->getName();
    }
}
