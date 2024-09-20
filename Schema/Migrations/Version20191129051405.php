<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;
use Morpheus\Schema\Types\CampaignClassificationEnumType;
use Services\Campaign\Classification\CampaignClassificationEnum;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191129051405 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema
            ->createTable('timing_descriptors');

        $table
            ->addColumn('id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(4)
            ->setNotNull(true);

        $table
            ->addColumn('name', Type::STRING)
            ->setLength(30)
            ->setNotnull(true);

        $table->setPrimaryKey(['id']);

        $table = $schema
            ->createTable('countries');

        $table
            ->addColumn('id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(4)
            ->setNotNull(true);

        $table
            ->addColumn('name', Type::STRING)
            ->setLength(30)
            ->setNotnull(true);

        $table
            ->addColumn('short_name', Type::STRING)
            ->setLength(5)
            ->setNotnull(true);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['name'], 'countries_name_idx');
        $table->addIndex(['short_name'], 'countries_short_name_idx');

        $table = $schema
            ->createTable('regions');

        $table
            ->addColumn('id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(11)
            ->setNotNull(true);

        $table
            ->addColumn('name', Type::STRING)
            ->setLength(30)
            ->setNotnull(true);

        $table
            ->addColumn('country_id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(4)
            ->setNotNull(true);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['country_id'], 'regions_country_id_idx');
        $table->addIndex(['name'], 'regions_name_idx');
        $table->addForeignKeyConstraint(
            'countries',
            ['country_id'],
            ['id']
        );

        $table = $schema
            ->createTable('campaign_classifications');

        $table
            ->addColumn('id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(4)
            ->setNotNull(true);

        $table
            ->addColumn('name', CampaignClassificationEnumType::TYPE_NAME)
            ->setNotnull(true);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['name'], 'campaign_classifications_name_idx');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('campaign_classifications');
        $schema->dropTable('regions');
        $schema->dropTable('countries');
        $schema->dropTable('timing_descriptors');
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $this->connection->insert('timing_descriptors', ['id' => 1, 'name' => 'ACMA']);
        $this->connection->insert('countries', ['id' => 1, 'name' => 'Australia', 'short_name' => 'AU']);

        $regions = [
            ['id' => 1,  'country_id' => 1, 'name' => 'Queensland'],
            ['id' => 2,  'country_id' => 1, 'name' => 'New South Wales'],
            ['id' => 3,  'country_id' => 1, 'name' => 'Victoria'],
            ['id' => 4,  'country_id' => 1, 'name' => 'South Australia'],
            ['id' => 5,  'country_id' => 1, 'name' => 'Australian Capital Territory'],
            ['id' => 6,  'country_id' => 1, 'name' => 'Western Australia'],
            ['id' => 7,  'country_id' => 1, 'name' => 'Northern Territory'],
            ['id' => 8,  'country_id' => 1, 'name' => 'Tasmania'],
        ];

        foreach ($regions as $region) {
            $this->connection->insert('regions', $region);
        }

        $classifications = [
            ['id' => 1, 'name' => CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT],
            ['id' => 2, 'name' => CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH],
            ['id' => 3, 'name' => CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING]
        ];

        foreach ($classifications as $classification) {
            $this->connection->insert('campaign_classifications', $classification);
        }
    }
}
