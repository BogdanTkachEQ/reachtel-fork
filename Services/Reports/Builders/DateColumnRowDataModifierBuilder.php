<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports\Builders;

use Services\Reports\DateColumnRowDataModifier;
use Services\Reports\Interfaces\Builders\RowDataModifierBuilderInterface;
use Services\Reports\Interfaces\RowDataModifierInterface;

/**
 * Class DateColumnRowDataModifierBuilder
 */
class DateColumnRowDataModifierBuilder implements RowDataModifierBuilderInterface
{
    /**
     * @param array $data
     * @return RowDataModifierInterface
     */
    public function buildFromArray(array $data)
    {
        if (!isset($data['name'])) {
            throw new \RuntimeException('Data column modifier requires a header name to be set');
        }

        if (!isset($data['column'])) {
            throw new \RuntimeException('Data column modifier requires column to be set');
        }

        if (!isset($data['format'])) {
            throw new \RuntimeException('Data column modifier requires format to be set');
        }

        return new DateColumnRowDataModifier(
            $data['name'],
            $data['column'],
            $data['format']
        );
    }
}
