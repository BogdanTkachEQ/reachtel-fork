<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Limits\SendRate;

use MabeEnum\Enum;

/**
 * Class SendRateCalculatorEnum
 */
class SendRateCalculatorEnum extends Enum
{
    const TIME_REMAINING = 'TR';
    const PERCENT_BOOST_TIME_REMAINING = 'PBTR';
}
