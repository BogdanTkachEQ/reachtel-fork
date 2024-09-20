<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Morpheus\Schema\Types;

/**
 * Class YesNoEnumType
 */
class YesNoEnumType extends AbstractEnumType
{
    const TYPE_NAME = 'enumyesno';

    /**
     * @return array
     */
    protected function getValues()
    {
        return ['y', 'n'];
    }

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return static::TYPE_NAME;
    }
}
