<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Morpheus\Schema;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Migrations\Version;

/**
 * Class AbstractReachtelMigrations
 */
abstract class AbstractReachtelMigration extends AbstractMigration
{
    /**
     * AbstractReachtelMigration constructor.
     * @param Version $version
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __construct(Version $version)
    {
        // Doctrine does not handle enum and since the legacy tables in morpheus has lot of enums, this is the
        // way to handle it my mapping it to string.
        $version
            ->getConfiguration()
            ->getConnection()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');

        parent::__construct($version);
    }
}
