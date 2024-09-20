<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Queue;

use MabeEnum\Enum;

/**
 * Class QueueProcessTypeEnum
 */
class QueueProcessTypeEnum extends Enum
{
    const FILEUPLOAD = "fileupload";
}
