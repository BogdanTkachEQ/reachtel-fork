<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Models\Day;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200122033623 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('DELETE FROM `timing_periods`');

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('DELETE FROM `timing_periods`');
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
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
    }

    /**
     * @param Schema $schema
     */
    public function postDown(Schema $schema)
    {
        $timingPeriods = [
            ['id' => 1, 'day_number' => 1, 'start' => '09:00', 'end' => '20:00', 'timing_group_id' => 1],
            ['id' => 2, 'day_number' => 2, 'start' => '09:00', 'end' => '20:00', 'timing_group_id' => 1],
            ['id' => 3, 'day_number' => 3, 'start' => '09:00', 'end' => '20:00', 'timing_group_id' => 1],
            ['id' => 4, 'day_number' => 4, 'start' => '09:00', 'end' => '20:00', 'timing_group_id' => 1],
            ['id' => 5, 'day_number' => 5, 'start' => '09:00', 'end' => '20:00', 'timing_group_id' => 1],
            ['id' => 6, 'day_number' => 6, 'start' => '09:00', 'end' => '17:00', 'timing_group_id' => 1],
            ['id' => 7, 'day_number' => 1, 'start' => '09:00', 'end' => '20:30', 'timing_group_id' => 2],
            ['id' => 8, 'day_number' => 2, 'start' => '09:00', 'end' => '20:30', 'timing_group_id' => 2],
            ['id' => 9, 'day_number' => 3, 'start' => '09:00', 'end' => '20:30', 'timing_group_id' => 2],
            ['id' => 10, 'day_number' => 4, 'start' => '09:00', 'end' => '20:30', 'timing_group_id' => 2],
            ['id' => 11, 'day_number' => 5, 'start' => '09:00', 'end' => '20:30', 'timing_group_id' => 2],
            ['id' => 12, 'day_number' => 6, 'start' => '09:00', 'end' => '17:00', 'timing_group_id' => 2],
            ['id' => 13, 'day_number' => 0, 'start' => '09:00', 'end' => '17:00', 'timing_group_id' => 2],
        ];

        foreach ($timingPeriods as $period) {
            $this->connection->insert('timing_periods', $period);
        }
    }
}
