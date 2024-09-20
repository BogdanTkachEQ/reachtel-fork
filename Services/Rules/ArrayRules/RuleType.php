<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\ArrayRules;

use MabeEnum\Enum;

/**
 * Class RuleType
 */
class RuleType extends Enum
{
    const EQUALTO = 'equal';
    const NOTEQUALTO = 'notequal';
    const LIKE = 'like';
    const NOTLIKE = 'notlike';
}
