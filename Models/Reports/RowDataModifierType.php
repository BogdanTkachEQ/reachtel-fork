<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models\Reports;

use MabeEnum\Enum;

/**
 * Class RowDataModifierType
 */
class RowDataModifierType extends Enum
{
    const DISPOSITION = 'disposition';
    const DEFAULTVALUE = 'defaultvalue';
    const DATEFORMATTER = 'dateformatter';
    const TEXTFORMATTER = 'textformatter';
}
