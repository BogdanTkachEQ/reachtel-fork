<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils;

use MabeEnum\Enum;

/**
 * Class OperatorsEnum
 * Standard operators
 */
class OperatorsEnum extends Enum
{
    const GT = ">";
    const LT = "<";
    const GTE = ">=";
    const LTE = "<=";
    const EQ = "=";
}
