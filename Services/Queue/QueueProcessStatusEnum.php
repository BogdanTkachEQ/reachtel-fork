<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Queue;

use MabeEnum\Enum;

/**
 * Class QueueProcessStatusEnum
 */
class QueueProcessStatusEnum extends Enum
{
    const SUCCESS = 1;
    const FAIL = -1;
}
