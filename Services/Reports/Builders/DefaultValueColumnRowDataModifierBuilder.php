<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports\Builders;

use Services\Reports\DefaultValueColumnRowDataModifier;
use Services\Reports\Interfaces\Builders\RowDataModifierBuilderInterface;
use Services\Reports\Interfaces\RowDataModifierInterface;

/**
 * Class DefaultValueColumnRowDataModifier
 */
class DefaultValueColumnRowDataModifierBuilder implements RowDataModifierBuilderInterface
{
    /**
     * @param array $data
     * @return RowDataModifierInterface
     */
    public function buildFromArray(array $data)
    {
        if (!isset($data['name'])) {
            throw new \RuntimeException('Default value modifier requires name to be set');
        }

        if (!isset($data['value'])) {
            throw new \RuntimeException('Default value modifier requires value to be set');
        }

        return new DefaultValueColumnRowDataModifier(
            $data['name'],
            $data['value'],
            (isset($data['valuecolumn']) ? $data['valuecolumn'] : null)
        );
    }
}
