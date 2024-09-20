<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports\Builders;

use Models\Reports\DataSeparatorType;
use Services\Reports\DispositionColumnRowDataModifier;
use Services\Reports\Interfaces\Builders\RowDataModifierBuilderInterface;
use Services\Reports\Interfaces\RowDataModifierInterface;

/**
 * Class DispositionColumnRowDataModifierBuilder
 */
class DispositionColumnRowDataModifierBuilder implements RowDataModifierBuilderInterface
{
    /**
     * @param array $data
     * @return RowDataModifierInterface
     */
    public function buildFromArray(array $data)
    {
        if (!isset($data['columns'])) {
            throw new \RuntimeException('Disposition modifier requires columns to be set');
        }

        if (!isset($data['name'])) {
            throw new \RuntimeException('Disposition modifier requires a header name to be set');
        }

        $modifier = new DispositionColumnRowDataModifier();
        return $modifier
            ->setHeaderName($data['name'])
            ->setColumns($data['columns'])
            ->setSeparator(
                (isset($data['separator']) && $data['separator']) ?
                    DataSeparatorType::byName(strtoupper($data['separator'])) :
                    null
            );
    }
}
