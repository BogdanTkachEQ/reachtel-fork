<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191008235326 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable('activity_logs');
        $table
            ->getColumn('value')
            ->setOptions(['type' => Type::getType(Type::TEXT), 'length' => 16777215]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable('activity_logs');
        $table
            ->getColumn('value')
            ->setOptions(['type' => Type::getType(Type::STRING), 'length' => 1024]);
    }
}
