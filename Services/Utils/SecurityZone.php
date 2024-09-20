<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils;

use MabeEnum\Enum;

/**
 * Class SecurityZones
 */
class SecurityZone extends Enum
{
    const SINCH_INBOUND_SMS_SECURITY_ZONE = 184;
    const SINCH_SMS_DR_SECURITY_ZONE = 185;
}
