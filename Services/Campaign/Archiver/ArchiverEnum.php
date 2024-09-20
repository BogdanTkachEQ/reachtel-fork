<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Archiver;

use MabeEnum\Enum;

/**
 * Class ArchiverEnum
 */
class ArchiverEnum extends Enum
{
    const MANUAL = "manual";
    const SYSTEM = "system";
}
