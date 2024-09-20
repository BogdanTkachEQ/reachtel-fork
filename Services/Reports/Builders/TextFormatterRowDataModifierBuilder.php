<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports\Builders;

use Services\Reports\Interfaces\Builders\RowDataModifierBuilderInterface;
use Services\Reports\TextFormatterRowDataModifier;

/**
 * Class TextFormatterRowDataModifierBuilder
 */
class TextFormatterRowDataModifierBuilder implements RowDataModifierBuilderInterface
{

    /**
     * @param array $data
     * @return TextFormatterRowDataModifier
     */
    public function buildFromArray(array $data)
    {
        if (!isset($data['name'])) {
            throw new \RuntimeException('Text formatter modifier requires a header name to be set');
        }

        if (!isset($data['column'])) {
            throw new \RuntimeException('Text formatter modifier requires column to be set');
        }

        $modifier = new TextFormatterRowDataModifier(
            $data['name'],
            $data['column']
        );

        if (isset($data['replacelinefeedby'])) {
            $modifier->setLineFeedReplace($data['replacelinefeedby']);
        }

        if (isset($data['maxlength'])) {
            $modifier->setMaxLength($data['maxlength']);
        }

        if (isset($data['useellipsis']) && $data['useellipsis']) {
            $modifier->addEllipsis(true);
        }

        return $modifier;
    }
}
