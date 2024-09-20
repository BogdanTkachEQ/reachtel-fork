<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190820023042 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = 'INSERT INTO `billing_products` (id, billing_type_id, status, `name`, code) VALUES(:id, :bid, :status, :name, :code)';
        $this->addSql($sql, ['id' => 33, 'bid' => 1, 'status' => 1, 'name' => 'Number Wash - Other', 'code' => 'RT33']);

        $sql = 'INSERT INTO `billing_products_config_wash` (`billing_product_id`, `region_id`, `destination_type_id`) VALUES (:pid, :rid, :did)';
        $this->addSql($sql, ['pid' => 33, 'rid' => 6, 'did' => 13]);

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $sql = 'DELETE FROM `billing_products_config_wash` WHERE `billing_product_id` = :pid AND `region_id` = :rid AND `destination_type_id` = :did';
        $this->addSql($sql, ['pid' => 33, 'rid' => 6, 'did' => 13]);

        $sql = 'DELETE FROM `billing_products` WHERE `id` = :id';
        $this->addSql($sql, ['id' => 33]);
    }
}
