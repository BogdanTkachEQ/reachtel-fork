<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190625015212 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema
            ->createTable('billing_products_config_wash');

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
            ->addColumn('destination_type_id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(4)
            ->setNotNull(true);

        $table
            ->setPrimaryKey(['id'])
            ->addUniqueIndex(['billing_product_id', 'region_id', 'destination_type_id'])
            ->addForeignKeyConstraint(
                'billing_products',
                ['billing_product_id'],
                ['id']
            )
            ->addForeignKeyConstraint(
                'billing_products_region',
                ['region_id'],
                ['id']
            )
            ->addForeignKeyConstraint(
                'billing_products_destination_type',
                ['destination_type_id'],
                ['id']
            );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('billing_products_config_wash');
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $data = [
            ['billing_product_id' => 1, 'region_id' => 1, 'destination_type_id' => 2], /* Australia - Landline */
            ['billing_product_id' => 2, 'region_id' => 2, 'destination_type_id' => 2], /* New Zealand - Landline */
            ['billing_product_id' => 3, 'region_id' => 6, 'destination_type_id' => 2], /* Other - Landline */
            ['billing_product_id' => 4, 'region_id' => 1, 'destination_type_id' => 1], /* Australia - Mobile */
            ['billing_product_id' => 5, 'region_id' => 2, 'destination_type_id' => 1], /* New Zealand - Mobile */
            ['billing_product_id' => 6, 'region_id' => 3, 'destination_type_id' => 1], /* Singapore - Mobile */
            ['billing_product_id' => 7, 'region_id' => 4, 'destination_type_id' => 1], /* Great Britain - Mobile */
            ['billing_product_id' => 8, 'region_id' => 6, 'destination_type_id' => 1], /* Other - Mobile */
        ];

        foreach ($data as $row) {
            $this->connection->insert('billing_products_config_wash', $row);
        }
    }
}
