<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190625045415 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = 'UPDATE wash_out SET billing_products_region_id = :id WHERE billingtype = :bt';

        $this->addSql($sql, ['bt' => 'washaufixedline', 'id' => 1]);
        $this->addSql($sql, ['bt' => 'washaumobile', 'id' => 1]);
        $this->addSql($sql, ['bt' => 'washnzfixedline', 'id' => 2]);
        $this->addSql($sql, ['bt' => 'washnzmobile', 'id' => 2]);
        $this->addSql($sql, ['bt' => 'washother', 'id' => 6]);
        $this->addSql($sql, ['bt' => 'washsgfixedline', 'id' => 3]);
        $this->addSql($sql, ['bt' => 'washsgmobile', 'id' => 3]);
        $this->addSql($sql, ['bt' => 'washgbmobile', 'id' => 4]);
        $this->addSql($sql, ['bt' => 'washgbfixedline', 'id' => 4]);

        $sql = 'UPDATE wash_out SET billing_products_destination_type_id = :did WHERE billingtype = :bt';

        $this->addSql($sql, ['bt' => 'washaufixedline', 'did' => 2]);
        $this->addSql($sql, ['bt' => 'washaumobile', 'did' => 1]);
        $this->addSql($sql, ['bt' => 'washnzfixedline', 'did' => 2]);
        $this->addSql($sql, ['bt' => 'washnzmobile', 'did' => 1]);
        $this->addSql($sql, ['bt' => 'washother', 'did' => 13]);
        $this->addSql($sql, ['bt' => 'washsgfixedline', 'did' => 2]);
        $this->addSql($sql, ['bt' => 'washsgmobile', 'did' => 1]);
        $this->addSql($sql, ['bt' => 'washgbmobile', 'did' => 1]);
        $this->addSql($sql, ['bt' => 'washgbfixedline', 'did' => 2]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $sql = 'UPDATE wash_out SET billing_products_region_id = :id';
        $this->addSql($sql, ['id' => 1]);

        $sql = 'UPDATE wash_out SET billing_products_destination_type_id = :id';
        $this->addSql($sql, ['id' => 1]);
    }
}
