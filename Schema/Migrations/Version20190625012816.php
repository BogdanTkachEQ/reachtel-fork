<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;
use Morpheus\Schema\Types\IntervalEnumType;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190625012816 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema
            ->createTable('billing_products_config_phone');

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
            ->addColumn('interval', IntervalEnumType::TYPE_NAME)
            ->setNotnull(true);

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
            ->addUniqueIndex(['billing_product_id', 'interval', 'region_id', 'destination_type_id'])
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
        $schema->dropTable('billing_products_config_phone');
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $data = [
            ['billing_product_id' => 10, '`interval`' => 'first', 'region_id' => 1, 'destination_type_id' => 1], /* First Australia Mobile */
            ['billing_product_id' => 11, '`interval`' => 'next', 'region_id' => 1, 'destination_type_id' => 1], /* Next Australia Mobile */
            ['billing_product_id' => 12, '`interval`' => 'first', 'region_id' => 1, 'destination_type_id' => 2], /* First Australia Fixed Line */
            ['billing_product_id' => 13, '`interval`' => 'next', 'region_id' => 1, 'destination_type_id' => 2], /* Next Australia Fixed Line */
            ['billing_product_id' => 14, '`interval`' => 'first', 'region_id' => 1, 'destination_type_id' => 5], /* First Australia 13/1300 */
            ['billing_product_id' => 15, '`interval`' => 'next', 'region_id' => 1, 'destination_type_id' => 5], /* Next Australia 13/1300 */
            ['billing_product_id' => 16, '`interval`' => 'first', 'region_id' => 1, 'destination_type_id' => 3], /* First Australia 1800 */
            ['billing_product_id' => 17, '`interval`' => 'next', 'region_id' => 1, 'destination_type_id' => 3], /* Next Australia 1800 */
            ['billing_product_id' => 18, '`interval`' => 'first', 'region_id' => 2, 'destination_type_id' => 1], /* First New Zealand Mobile */
            ['billing_product_id' => 19, '`interval`' => 'next', 'region_id' => 2, 'destination_type_id' => 1], /* Next New Zealand Mobile */
            ['billing_product_id' => 20, '`interval`' => 'first', 'region_id' => 2, 'destination_type_id' => 2], /* First New Zealand Fixed Line */
            ['billing_product_id' => 21, '`interval`' => 'next', 'region_id' => 2, 'destination_type_id' => 2], /* Next New Zealand Fixed Line */
            ['billing_product_id' => 22, '`interval`' => 'first', 'region_id' => 2, 'destination_type_id' => 3], /* First New Zealand 0800 */
            ['billing_product_id' => 23, '`interval`' => 'next', 'region_id' => 2, 'destination_type_id' => 3], /* Next New Zealand 0800 */
        ];

        foreach ($data as $row) {
            $this->connection->insert('billing_products_config_phone', $row);
        }
    }
}
