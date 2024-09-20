<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Morpheus\Schema\AbstractReachtelMigration;
use Services\Suppliers\SmsServiceFactory;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190822045352 extends AbstractReachtelMigration
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
                'id' => SmsServiceFactory::SMS_SUPPLIER_SINCH_ID,
                'item' => 'name',
                'val' => 'Sinch API'
            ],
            [
                'tp' => 'SMSSUPPLIER',
                'id' => SmsServiceFactory::SMS_SUPPLIER_SINCH_ID,
                'item' => 'priority',
                'val' => 13
            ],
            [
                'tp' => 'SMSSUPPLIER',
                'id' => SmsServiceFactory::SMS_SUPPLIER_SINCH_ID,
                'item' => 'status',
                'val' => 'DISABLED'
            ],
            [
                'tp' => 'SMSSUPPLIER',
                'id' => SmsServiceFactory::SMS_SUPPLIER_SINCH_ID,
                'item' => 'counter',
                'val' => 0
            ],
            [
                'tp' => 'SMSSUPPLIER',
                'id' => SmsServiceFactory::SMS_SUPPLIER_SINCH_ID,
                'item' => 'smspersecond',
                'val' => 10
            ],
            [
                'tp' => 'SMSSUPPLIER',
                'id' => SmsServiceFactory::SMS_SUPPLIER_SINCH_ID,
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
        $this->addSql($sql, ['id' => SmsServiceFactory::SMS_SUPPLIER_SINCH_ID]);
    }
}
