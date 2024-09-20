<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Morpheus\Schema\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Services\Queue\QueueProcessTypeEnum;

class QueueProcessTypeEnumType extends AbstractEnumType
{

    const TYPE_NAME = 'process_type';

    /**
     * @return array
     */
    protected function getValues()
    {
        return QueueProcessTypeEnum::getValues() ;
    }

    /**
     * Gets the name of this type.
     *
     * @return string
     *
     * @todo Needed?
     */
    public function getName()
    {
        return static::TYPE_NAME;
    }


    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     * @return mixed
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return QueueProcessTypeEnum::byValue($value);
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     * @return mixed
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!($value instanceof QueueProcessTypeEnum)) {
            throw new \InvalidArgumentException('Invalid value for process type received');
        }

        return $value->getValue();
    }
}