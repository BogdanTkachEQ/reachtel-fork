<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Services\Suppliers\SmsServiceFactory;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200309031815 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = 'INSERT INTO `key_store` VALUES (:tp, :id, :item, :val)';

        $this->addSql($sql,  [
            'tp' => 'SETTINGS',
            'id' => 0,
            'item' => 'FILE_UPLOAD_QUEUE_MAX_ATTEMPTS',
            'val' => '10'
        ]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $sql = 'DELETE from `key_store` WHERE `type`="SETTINGS" AND item=:item';
        $this->addSql($sql, ['item' => "FILE_UPLOAD_QUEUE_MAX_ATTEMPTS"]);
    }
}
