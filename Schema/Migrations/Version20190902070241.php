<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190902070241 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = 'DELETE FROM `key_store` WHERE `type`=:t AND `item`=:i AND id=:id';
        $this->addSql($sql, ['t' => 'SETTINGS', 'i' => 'AZURE_SPEECHSYNTHESIS_KEY', 'id' => 0]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $sql = 'INSERT INTO `key_store` (`type`, `item`, `id`, `value`) VALUES (:t, :i, :id, :val)';
        $this->addSql($sql, ['t' => 'SETTINGS', 'i' => 'AZURE_SPEECHSYNTHESIS_KEY', 'id' => 0, 'val' => '']);
    }
}
