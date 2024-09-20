<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Doctrine;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Morpheus\Schema\CustomTypesRegistrationManager;
use Morpheus\Schema\Types\CampaignClassificationEnumType;
use Morpheus\Schema\Types\DayNumberEnumType;
use Morpheus\Schema\Types\QueueProcessTypeEnumType;
use Services\Doctrine\Driver\MysqliCustomDriver;
use Services\Queue\QueueProcessTypeEnum;

/**
 * Class EntityManagerAccessor
 */
class EntityManagerAccessor
{
    /** @var string */
    private $pathToOrm;

    /** @var boolean */
    private $isDevEnvironment;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * EntityManagerAccessor constructor.
     * @param string  $pathToOrm
     * @param boolean $isDevEnvironment
     */
    public function __construct($pathToOrm, $isDevEnvironment = false)
    {
        $this->pathToOrm = $pathToOrm;
        $this->isDevEnvironment = $isDevEnvironment;
    }

    /**
     * @return EntityManager
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getEntityManager()
    {
        if (is_null($this->entityManager)) {
            // Any new doctrine type has to be registered here. Move this to a function when it gets bigger.
            CustomTypesRegistrationManager::registerType(
                DayNumberEnumType::TYPE_NAME,
                DayNumberEnumType::class
            );

            CustomTypesRegistrationManager::registerType(
                CampaignClassificationEnumType::TYPE_NAME,
                CampaignClassificationEnumType::class
            );

            CustomTypesRegistrationManager::registerType(
                QueueProcessTypeEnumType::TYPE_NAME,
                QueueProcessTypeEnumType::class
            );

            $config = Setup::createYAMLMetadataConfiguration(
                [$this->pathToOrm],
                $this->isDevEnvironment,
                null,
                new ArrayCache()
            );

            if (!$this->isDevEnvironment) {
                $config->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);
            }

            $this->entityManager = EntityManager::create([
                'driverClass' => MysqliCustomDriver::class,
                'host' => DB_MYSQL_WRITE_HOST
            ], $config);
        }

        return $this->entityManager;
    }

    public function reset()
    {
        $this->entityManager = null;
    }
}
