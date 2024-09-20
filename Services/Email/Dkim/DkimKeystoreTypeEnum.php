<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Email\Dkim;

use MabeEnum\Enum;

/**
 * Class DkimKeyTypeEnum
 * @package Services\Email\Dkim
 */
class DkimKeystoreTypeEnum extends Enum
{
    const GROUP = "group";
    const SYSTEM = "system";
}
