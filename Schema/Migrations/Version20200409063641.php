<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200409063641 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema
            ->createTable('fixed_width_file_identifier');

        $table
            ->addColumn('id',Type::INTEGER, ['autoincrement' => true])
            ->setUnsigned(true)
            ->setLength(11)
            ->setNotNull(true);

        $table
            ->addColumn('name', Type::STRING)
            ->setLength(30)
            ->setNotNull(true);

        $table->setPrimaryKey(["id"]);
        $table->addIndex(["name"], 'fixed_width_file_identifier_name_idx');

        $table = $schema
            ->createTable('fixed_width_file_specification');

        $table
            ->addColumn('id', Type::INTEGER, ['autoincrement' => true])
            ->setUnsigned(true)
            ->setLength(11)
            ->setNotNull(true);

        $table
            ->addColumn('fixed_width_file_id', Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(10)
            ->setNotNull(true);

        $table
            ->addColumn('column_name', Type::STRING)
            ->setLength(50)
            ->setNotnull(true);

        $table
            ->addColumn('start', Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(5)
            ->setNotNull(true);

        $table
            ->addColumn('length', Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(5)
            ->setNotNull(true);

        $table
            ->setPrimaryKey(['id'])
            ->addUniqueIndex(['fixed_width_file_id', 'column_name'], 'fixed_width_file_specification_file_id_col_name_idx')
            ->addIndex(['column_name'], 'fixed_width_file_specification_col_name_idx')
            ->addForeignKeyConstraint(
                'fixed_width_file_identifier',
                ['fixed_width_file_id'],
                ['id']
            );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('fixed_width_file_specification');
        $schema->dropTable('fixed_width_file_identifier');
    }

    /**
     * @param Schema $schema
     * @throws SkipMigrationException
     */
    public function postUp(Schema $schema)
    {
        $data = [
            [
                'id' => 1,
                'name' => 'westpac_tcs_file'
            ],
            [
                'id' => 2,
                'name' => 'westpac_tm_file'
            ],
            [
                'id' => 3,
                'name' => 'westpac_b2k_file'
            ]
        ];

        foreach ($data as $row) {
            $this->connection->insert(
                'fixed_width_file_identifier',
                $row
            );
        }

        $this->loadSpecs(1, __DIR__ . '/../../scripts/autoload/wbcvrs/wbc-input-tcs-specification.csv');
        $this->loadSpecs(2, __DIR__ . '/../../scripts/autoload/wbcvrs/wbc-input-tm-specification.csv');
        $this->loadSpecs(3, __DIR__ . '/../../scripts/autoload/wbcvrs/wbc-input-b2k-specification.csv');
    }

    /**
     * @param integer $fileId
     * @param string $fileName
     * @throws SkipMigrationException
     */
    private function loadSpecs($fileId, $fileName)
    {
        $handle = fopen($fileName, "r");

        if(!$handle) {
            throw new SkipMigrationException('Can not read file ' . $fileName);
        }

        $row = 0;
        while(($line = fgetcsv($handle, 4096)) !== false) {
            $row++;
            if($row === 1){
                continue;
            }

            $this->connection->insert(
                'fixed_width_file_specification',
                ['fixed_width_file_id' => $fileId, 'column_name' => $line[0], 'start' => $line[2], 'length' => $line[1]]
            );
        }
    }
}
