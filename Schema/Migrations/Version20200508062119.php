<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200508062119 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = 'INSERT INTO `key_store` VALUES (:tp, :id, :item, :val)';

        $items = [
            [
                'tp' => 'SMSDIDS',
                'id' => 52,
                'item' => 'enablecallme',
                'val' => 'on'
            ],
            [
                'tp' => 'SMSDIDS',
                'id' => 565,
                'item' => 'enablecallme',
                'val' => 'on'
            ],
            [
                'tp' => 'SMSDIDS',
                'id' => 748,
                'item' => 'enablecallme',
                'val' => 'on'
            ],
            [
                'tp' => 'SMSDIDS',
                'id' => 750,
                'item' => 'enablecallme',
                'val' => 'on'
            ],
            [
                'tp' => 'SMSDIDS',
                'id' => 759,
                'item' => 'enablecallme',
                'val' => 'on'
            ],
            [
                'tp' => 'SMSDIDS',
                'id' => 762,
                'item' => 'enablecallme',
                'val' => 'on'
            ],
            [
                'tp' => 'SMSDIDS',
                'id' => 767,
                'item' => 'enablecallme',
                'val' => 'on'
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
        $sql = 'DELETE from `key_store` WHERE `type`="SMSDIDS" and `item`="enablecallme" AND id in (:ids)';
        $this->addSql($sql, ['ids' => [52, 565, 748, 750, 759, 762, 767]], ['ids' => Connection::PARAM_INT_ARRAY]);
    }
}
