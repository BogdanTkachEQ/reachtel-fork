<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models\Reports;

use MabeEnum\Enum;

/**
 * Class DataSeparator
 */
class DataSeparatorType extends Enum
{
    const COMMA = ',';
    const HYPHEN = '-';
    const PIPE = '|';
    const SPACE = ' ';
    const UNDER_SCORE = '_';
}
