<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports\Interfaces\Builders;

use Services\Reports\Interfaces\RowDataModifierInterface;

/**
 * interface RowDataModifierBuilder
 */
interface RowDataModifierBuilderInterface
{
    /**
     * @param array $data
     * @return RowDataModifierInterface
     */
    public function buildFromArray(array $data);
}
