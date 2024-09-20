<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200206155818 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = 'INSERT INTO `key_store` (`type`, `id`, `item`, `value`) VALUES (:t, :id, :i, :v) ON DUPLICATE KEY UPDATE `value` = :v';
        $this->addSql($sql, ['t' => 'SETTINGS', 'id' => 0, 'i' => 'FILEPROCESS_TMP_LOCATION', 'v' => "/tmp"]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql(
            'DELETE FROM `key_store` WHERE `type`=:t AND item=:i',
            ['t' => 'SETTINGS', 'i' => 'FILEPROCESS_TMP_LOCATION']
        );

    }
}
