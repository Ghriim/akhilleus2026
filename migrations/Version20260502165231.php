<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260502165231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add workout.name (auto-derived "Day Morning|Afternoon" label) and backfill existing rows.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE workout ADD name VARCHAR(100) NOT NULL DEFAULT ''");
        // Backfill existing rows from the most representative date — same precedence as
        // WorkoutPersister::deriveName() (plannedAt → dateStart → created_at fallback).
        $this->addSql(
            'UPDATE workout SET name = CONCAT('
            .'DAYNAME(COALESCE(planned_at, date_start, created_at)), '
            ."' ', "
            ."IF(HOUR(COALESCE(planned_at, date_start, created_at)) < 12, 'Morning', 'Afternoon')"
            .") WHERE name = ''"
        );
        // Drop the empty-string default — new rows must carry a real name (set by the persister).
        $this->addSql('ALTER TABLE workout ALTER COLUMN name DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workout DROP name');
    }
}
