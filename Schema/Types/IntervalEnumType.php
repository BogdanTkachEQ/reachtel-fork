<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Morpheus\Schema\Types;

/**
 * Class IntervalEnumType
 */
class IntervalEnumType extends AbstractEnumType
{
    const TYPE_NAME = 'enumtypeinterval';

    /**
     * @return array
     */
    protected function getValues()
    {
        return ['first', 'next'];
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
