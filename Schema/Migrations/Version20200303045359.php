<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200303045359 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = 'INSERT INTO `key_store` VALUES (:tp, :id, :item, :val)';

        $items = [
            [
                'tp' => 'SMSSUPPLIER',
                'id' => 23,
                'item' => 'name',
                'val' => 'Sinch SMPP'
            ],
            [
                'tp' => 'SMSSUPPLIER',
                'id' => 23,
                'item' => 'priority',
                'val' => 1
            ],
            [
                'tp' => 'SMSSUPPLIER',
                'id' => 23,
                'item' => 'status',
                'val' => 'DISABLED'
            ],
            [
                'tp' => 'SMSSUPPLIER',
                'id' => 23,
                'item' => 'counter',
                'val' => 0
            ],
            [
                'tp' => 'SMSSUPPLIER',
                'id' => 23,
                'item' => 'smspersecond',
                'val' => 10
            ],
            [
                'tp' => 'SMSSUPPLIER',
                'id' => 23,
                'item' => 'capabilities',
                'val' => serialize([])
            ],
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
        $sql = 'DELETE from `key_store` WHERE `type`="SMSSUPPLIER" AND id=:id';
        $this->addSql($sql, ['id' => 23]);
    }
}
