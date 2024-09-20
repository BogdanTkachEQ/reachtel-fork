<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Models\Day;
use Morpheus\Schema\AbstractReachtelMigration;
use Morpheus\Schema\Types\DayNumberEnumType;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191129061013 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema
            ->createTable('timing_groups');

        $table
            ->addColumn('id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(11)
            ->setNotNull(true);

        $table
            ->addColumn('name',Type::STRING)
            ->setLength(25)
            ->setNotNull(false);

        $table->setPrimaryKey(['id']);

        $table = $schema
            ->createTable('timing_periods');

        $table
            ->addColumn('id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(11)
            ->setNotNull(true);

        $table
            ->addColumn('day_number', DayNumberEnumType::TYPE_NAME)
            ->setNotNull(true);

        $table
            ->addColumn('start', Type::TIME)
            ->setNotnull(true);

        $table
            ->addColumn('end', Type::TIME)
            ->setNotnull(true);

        $table
            ->addColumn('timing_group_id', Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(11)
            ->setNotNull(true);

        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint(
            'timing_groups',
            ['timing_group_id'],
            ['id']
        );
        $table->addIndex(['timing_group_id'], 'timing_periods_timing_group_id_idx');

        $table = $schema
            ->createTable('timing_group_regions');

        $table
            ->addColumn('timing_group_id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(11)
            ->setNotNull(true);

        $table
            ->addColumn('region_id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(11)
            ->setNotNull(true);

        $table->addIndex(['timing_group_id', 'region_id'], 'timing_groups_region_tgid_region_id_idx');
        $table->addIndex(['region_id'], 'timing_groups_region_region_id_idx');
        $table->addForeignKeyConstraint(
            'regions',
            ['region_id'],
            ['id']
        );

        $table->addForeignKeyConstraint(
            'timing_groups',
            ['timing_group_id'],
            ['id']
        );

        $table = $schema
            ->createTable('campaign_timing_rules');

        $table
            ->addColumn('id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(11)
            ->setNotNull(true);

        $table
            ->addColumn('timing_descriptor_id', Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(4)
            ->setNotNull(true);

        $table
            ->addColumn('campaign_classification_id', Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(4)
            ->setNotNull(true);

        $table
            ->addColumn('timing_group_id', Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(11)
            ->setNotNull(true);

        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint(
            'timing_descriptors',
            ['timing_descriptor_id'],
            ['id']
        );

        $table->addForeignKeyConstraint(
            'campaign_classifications',
            ['campaign_classification_id'],
            ['id']
        );

        $table->addForeignKeyConstraint(
            'timing_groups',
            ['timing_group_id'],
            ['id']
        );

        $table->addIndex(['timing_group_id'], 'campaign_timing_rules_timing_group_id_idx');
        $table->addIndex(
            [
                'timing_descriptor_id', 'campaign_classification_id'
            ],
            'campaign_timing_rules_descriptor_classification_idx'
        );
        $table->addIndex(['campaign_classification_id'], 'campaign_timing_rules_classification_id_idx');
        $table->addUniqueIndex(['timing_descriptor_id', 'campaign_classification_id', 'timing_group_id']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('campaign_timing_rules');
        $schema->dropTable('timing_group_regions');
        $schema->dropTable('timing_periods');
        $schema->dropTable('timing_groups');
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $timingGroups = [
            ['id' => 1, 'name' => 'acma telemarketing au'],
            ['id' => 2, 'name' => 'acma research au'],
        ];

        foreach ($timingGroups as $group) {
            $this->connection->insert('timing_groups', $group);
        }

        $timingPeriods = [
            ['id' => 1, 'day_number' => Day::MONDAY, 'start' => '09:00', 'end' => '20:00', 'timing_group_id' => 1],
            ['id' => 2, 'day_number' => Day::TUESDAY, 'start' => '09:00', 'end' => '20:00', 'timing_group_id' => 1],
            ['id' => 3, 'day_number' => Day::WEDNESDAY, 'start' => '09:00', 'end' => '20:00', 'timing_group_id' => 1],
            ['id' => 4, 'day_number' => Day::THURSDAY, 'start' => '09:00', 'end' => '20:00', 'timing_group_id' => 1],
            ['id' => 5, 'day_number' => Day::FRIDAY, 'start' => '09:00', 'end' => '20:00', 'timing_group_id' => 1],
            ['id' => 6, 'day_number' => Day::SATURDAY, 'start' => '09:00', 'end' => '17:00', 'timing_group_id' => 1],
            ['id' => 7, 'day_number' => Day::MONDAY, 'start' => '09:00', 'end' => '20:30', 'timing_group_id' => 2],
            ['id' => 8, 'day_number' => Day::TUESDAY, 'start' => '09:00', 'end' => '20:30', 'timing_group_id' => 2],
            ['id' => 9, 'day_number' => Day::WEDNESDAY, 'start' => '09:00', 'end' => '20:30', 'timing_group_id' => 2],
            ['id' => 10, 'day_number' => Day::THURSDAY, 'start' => '09:00', 'end' => '20:30', 'timing_group_id' => 2],
            ['id' => 11, 'day_number' => Day::FRIDAY, 'start' => '09:00', 'end' => '20:30', 'timing_group_id' => 2],
            ['id' => 12, 'day_number' => Day::SATURDAY, 'start' => '09:00', 'end' => '17:00', 'timing_group_id' => 2],
            ['id' => 13, 'day_number' => Day::SUNDAY, 'start' => '09:00', 'end' => '17:00', 'timing_group_id' => 2],
        ];

        foreach ($timingPeriods as $period) {
            $this->connection->insert('timing_periods', $period);
        }

        foreach ([1, 2] as $groupId) {
            for ($i = 1; $i <= 8; $i++) {
                $this
                    ->connection
                    ->insert('timing_group_regions', ['timing_group_id' => $groupId, 'region_id' => $i]);
            }
        }

        $campaignTimingRules = [
            ['id' => 1, 'timing_descriptor_id' => 1, 'campaign_classification_id' => 3, 'timing_group_id' => 1],
            ['id' => 2, 'timing_descriptor_id' => 1, 'campaign_classification_id' => 2, 'timing_group_id' => 2],
        ];

        foreach ($campaignTimingRules as $rule) {
            $this->connection->insert('campaign_timing_rules', $rule);
        }
    }
}
