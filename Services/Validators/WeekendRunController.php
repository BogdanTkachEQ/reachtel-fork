<?php

namespace Services\Validators;

/**
 * Class WeekendRunController
 * @package Services\Validators
 */
class WeekendRunController extends DailyRunController
{
    /**
     * @return boolean
     */
    public function stopRun()
    {
        $this
            ->setStopDay(self::SATURDAY)
            ->setStopDay(self::SUNDAY)
            ->setStopReason('Weekend');
        return parent::stopRun();
    }
}
