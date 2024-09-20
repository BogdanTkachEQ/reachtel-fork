<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190624235719 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable('billing_products');

        $table
            ->addColumn('id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(10)
            ->setNotNull(true)
            ->setAutoIncrement(true);

        $table
            ->addColumn('status', Type::INTEGER)
            ->setLength(1)
            ->setNotnull(true)
            ->setDefault(1);

        $table
            ->addColumn('billing_type_id', Type::INTEGER)
            ->setLength(4)
            ->setUnsigned(true)
            ->setNotnull(true);

        $table
            ->addColumn('name', Type::STRING)
            ->setLength(50)
            ->setNotnull(true);

        $table
            ->addColumn('code', Type::STRING)
            ->setLength(4)
            ->setNotnull(false);

        $table
            ->addColumn('created', Type::DATETIME)
            ->setNotnull(true)
            ->setDefault(
                $this->connection->getDatabasePlatform()->getCurrentTimestampSQL()
            );

        $table
            ->addColumn(
                'updated',
                Type::DATETIME
            )
            // On update current_timestamp is not handled in migrations as it is usually done in doctrine entities.
            // There is no other option but to add column definition but it makes it platform dependant.
            ->setColumnDefinition('timestamp null default null on update current_timestamp');

        $table
            ->setPrimaryKey(['id'])
            ->addUniqueIndex(['name'])
            ->addUniqueIndex(['code'])
            ->addForeignKeyConstraint(
                'billing_types',
                ['billing_type_id'],
                ['id']
            );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('billing_products');
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $data = [
            ['id' => 1, 'name' => 'Number Wash - Fixed Line - Australia', 'code' => 'RT01', 'billing_type_id' => 1],
            ['id' => 2, 'name' => 'Number Wash - Fixed Line - New Zealand', 'code' => 'RT02', 'billing_type_id' => 1],
            ['id' => 3, 'name' => 'Number Wash - Fixed Line - Other', 'code' => 'RT03', 'billing_type_id' => 1],
            ['id' => 4, 'name' => 'Number Wash - Mobile - Australia', 'code' => 'RT04', 'billing_type_id' => 1],
            ['id' => 5, 'name' => 'Number Wash - Mobile - New Zealand', 'code' => 'RT05', 'billing_type_id' => 1],
            ['id' => 6, 'name' => 'Number Wash - Mobile - Singapore ', 'code' => 'RT06', 'billing_type_id' => 1],
            ['id' => 7, 'name' => 'Number Wash - Mobile - Great Britain', 'code' => 'RT07', 'billing_type_id' => 1],
            ['id' => 8, 'name' => 'Number Wash - Mobile - Other', 'code' => 'RT08', 'billing_type_id' => 1],
            ['id' => 9, 'name' => 'Email - Email Unit', 'code' => 'RT09', 'billing_type_id' => 1],
            ['id' => 10, 'name' => 'Phone - First Australia Mobile', 'code' => 'RT10', 'billing_type_id' => 1],
            ['id' => 11, 'name' => 'Phone - Next Australia Mobile', 'code' => 'RT11', 'billing_type_id' => 1],
            ['id' => 12, 'name' => 'Phone - First Australia Fixed Line', 'code' => 'RT12', 'billing_type_id' => 1],
            ['id' => 13, 'name' => 'Phone - Next Australia Fixed Line', 'code' => 'RT13', 'billing_type_id' => 1],
            ['id' => 14, 'name' => 'Phone - First Australia 13/1300', 'code' => 'RT14', 'billing_type_id' => 1],
            ['id' => 15, 'name' => 'Phone - Next Australia 13/1300', 'code' => 'RT15', 'billing_type_id' => 1],
            ['id' => 16, 'name' => 'Phone - First Australia 1800', 'code' => 'RT16', 'billing_type_id' => 1],
            ['id' => 17, 'name' => 'Phone - Next Australia 1800', 'code' => 'RT17', 'billing_type_id' => 1],
            ['id' => 18, 'name' => 'Phone - First New Zealand Mobile', 'code' => 'RT18', 'billing_type_id' => 1],
            ['id' => 19, 'name' => 'Phone - Next New Zealand Mobile', 'code' => 'RT19', 'billing_type_id' => 1],
            ['id' => 20, 'name' => 'Phone - First New Zealand Fixed Line', 'code' => 'RT20', 'billing_type_id' => 1],
            ['id' => 21, 'name' => 'Phone - Next New Zealand Fixed Line', 'code' => 'RT21', 'billing_type_id' => 1],
            ['id' => 22, 'name' => 'Phone - First New Zealand 0800', 'code' => 'RT22', 'billing_type_id' => 1],
            ['id' => 23, 'name' => 'Phone - Next New Zealand 0800', 'code' => 'RT23', 'billing_type_id' => 1],
            ['id' => 24, 'name' => 'SMS - Australia Mobile', 'code' => 'RT24', 'billing_type_id' => 1],
            ['id' => 25, 'name' => 'SMS - New Zealand Mobile', 'code' => 'RT25', 'billing_type_id' => 1],
            ['id' => 26, 'name' => 'SMS - Singapore Mobile', 'code' => 'RT26', 'billing_type_id' => 1],
            ['id' => 27, 'name' => 'SMS - Great Britain Mobile', 'code' => 'RT27', 'billing_type_id' => 1],
            ['id' => 28, 'name' => 'SMS - Philippines', 'code' => 'RT28', 'billing_type_id' => 1],
            ['id' => 29, 'name' => 'SMS - Other', 'code' => 'RT29', 'billing_type_id' => 1],
            ['id' => 30, 'name' => 'Regional Poll Sample 600', 'code' => 'RT30', 'billing_type_id' => 2],
            ['id' => 31, 'name' => 'State Poll Sample 1500', 'code' => 'RT31', 'billing_type_id' => 2],
            ['id' => 32, 'name' => 'National Poll Sample 2000', 'code' => 'RT32', 'billing_type_id' => 2],
        ];

        foreach ($data as $row) {
            $this->connection->insert('billing_products', $row);
        }
    }
}
