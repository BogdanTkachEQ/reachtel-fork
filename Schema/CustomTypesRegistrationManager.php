<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Morpheus\Schema;

use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\Types\CampaignClassificationEnumType;
use Morpheus\Schema\Types\DayNumberEnumType;
use Morpheus\Schema\Types\IntervalEnumType;
use Morpheus\Schema\Types\QueueProcessTypeEnumType;
use Morpheus\Schema\Types\YesNoEnumType;

/**
 * Class CustomTypesRegistrationManager
 */
class CustomTypesRegistrationManager
{
    /**
     * All new custom types need to be added to this array
     * @var array
     */
    protected static $customTypesMap = [
        YesNoEnumType::TYPE_NAME => YesNoEnumType::class,
        IntervalEnumType::TYPE_NAME => IntervalEnumType::class,
        DayNumberEnumType::TYPE_NAME => DayNumberEnumType::class,
        CampaignClassificationEnumType::TYPE_NAME => CampaignClassificationEnumType::class,
        QueueProcessTypeEnumType::TYPE_NAME => QueueProcessTypeEnumType::class
    ];

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @return void
     */
    public static function registerTypes()
    {
        foreach (static::$customTypesMap as $type => $class) {
            static::registerType($type, $class);
        }
    }

    /**
     * @param $type
     * @param $class
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function registerType($type, $class)
    {
        if (!Type::hasType($type)) {
            Type::addType($type, $class);
        }
    }
}
