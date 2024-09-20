<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200904014828 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {

        $sql = 'INSERT INTO `key_store` VALUES (:tp, :id, :item, :val)';

        $items = [
            [
                'tp' => 'SETTINGS',
                'id' => 0,
                'item' => 'EMAIL_DEFAULT_DOMAIN',
                'val' => 'reachtel.com.au'
            ]
        ];
        foreach ($items as $item) {
            $this->addSql($sql, $item);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DELETE from key_store 
                            WHERE type='SETTINGS' 
                            AND item = 'EMAIL_DEFAULT_DOMAIN'");
    }
}
