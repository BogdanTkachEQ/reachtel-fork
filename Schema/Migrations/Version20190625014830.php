<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190625014830 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema
            ->createTable('billing_products_config_sms');

        $table
            ->addColumn('id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(10)
            ->setNotNull(true)
            ->setAutoIncrement(true);

        $table
            ->addColumn('billing_product_id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(10)
            ->setNotNull(true);

        $table
            ->addColumn('region_id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(4)
            ->setNotNull(true);

        $table
            ->setPrimaryKey(['id'])
            ->addUniqueIndex(['billing_product_id', 'region_id'])
            ->addForeignKeyConstraint(
                'billing_products',
                ['billing_product_id'],
                ['id']
            )
            ->addForeignKeyConstraint(
                'billing_products_region',
                ['region_id'],
                ['id']
            );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('billing_products_config_sms');
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $data = [
            ['billing_product_id' => 24, 'region_id' => 1], /* SMS Australia */
            ['billing_product_id' => 25, 'region_id' => 2], /* SMS New Zealand */
            ['billing_product_id' => 26, 'region_id' => 3], /* SMS Singapore */
            ['billing_product_id' => 27, 'region_id' => 4], /* SMS Great Britain */
            ['billing_product_id' => 28, 'region_id' => 5],/* SMS Philippines */
            ['billing_product_id' => 29, 'region_id' => 6],/* SMS Other */
        ];

        foreach ($data as $row) {
            $this->connection->insert('billing_products_config_sms', $row);
        }
    }
}
