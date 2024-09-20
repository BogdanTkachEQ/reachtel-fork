<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports;

use Models\Reports\RowDataModifierType;
use Services\Reports\Builders\DateColumnRowDataModifierBuilder;
use Services\Reports\Builders\DefaultValueColumnRowDataModifierBuilder;
use Services\Reports\Builders\DispositionColumnRowDataModifierBuilder;
use Services\Reports\Builders\TextFormatterRowDataModifierBuilder;
use Services\Reports\Exceptions\RowDataModifierFactoryException;
use Services\Reports\Interfaces\RowDataModifierInterface;

/**
 * Class RowDataModifierFactory
 */
class RowDataModifierFactory
{
    /**
     * @param RowDataModifierType $type
     * @param array               $data
     * @return RowDataModifierInterface
     */
    public function create(RowDataModifierType $type, array $data)
    {
        switch ($type) {
            case RowDataModifierType::DISPOSITION():
                $builder = new DispositionColumnRowDataModifierBuilder();
                break;

            case RowDataModifierType::DEFAULTVALUE():
                $builder = new DefaultValueColumnRowDataModifierBuilder();
                break;

            case RowDataModifierType::DATEFORMATTER():
                $builder = new DateColumnRowDataModifierBuilder();
                break;

            case RowDataModifierType::TEXTFORMATTER():
                $builder = new TextFormatterRowDataModifierBuilder();
                break;

            default:
                throw new RowDataModifierFactoryException(
                    'No implementation available for the modifier type ' . $type->getValue()
                );
        }

        return $builder->buildFromArray($data);
    }
}
