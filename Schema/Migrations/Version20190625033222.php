<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190625033222 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = 'UPDATE sms_out SET billing_products_region_id = :id WHERE billingtype = :bt';

        $this->addSql($sql, ['bt' => 'smsaumobile', 'id' => 1]);
        $this->addSql($sql, ['bt' => 'smsnzmobile', 'id' => 2]);
        $this->addSql($sql, ['bt' => 'smssgmobile', 'id' => 3]);
        $this->addSql($sql, ['bt' => 'smsgbmobile', 'id' => 4]);
        $this->addSql($sql, ['bt' => 'smsphmobile', 'id' => 5]);
        $this->addSql($sql, ['bt' => 'smsothermobile', 'id' => 6]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $sql = 'UPDATE sms_out SET billing_products_region_id = :id';
        $this->addSql($sql, ['id' => 1]);
    }
}
