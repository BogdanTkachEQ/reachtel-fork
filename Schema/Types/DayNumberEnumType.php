<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Morpheus\Schema\Types;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Models\Day;

/**
 * Class DayNumberEnumTypes
 */
class DayNumberEnumType extends AbstractEnumType
{
    const TYPE_NAME = 'enumtypedaynumber';

    /**
     * @return array
     */
    protected function getValues()
    {
        return [
            Day::MONDAY,
            Day::TUESDAY,
            Day::WEDNESDAY,
            Day::THURSDAY,
            Day::FRIDAY,
            Day::SATURDAY,
            Day::SUNDAY
        ];
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
     * @return mixed|static
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return Day::byValue((int) $value);
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     * @return array|bool|float|int|mixed|null|string
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!($value instanceof Day)) {
            throw new \InvalidArgumentException('Invalid value for day number received');
        }

        return $value->getValue();
    }
}
