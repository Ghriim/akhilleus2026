<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Seeds the LevelingConfig singleton (Phase 3.5, pulled forward from 3.10): the single well-known
 * row read by `LevelingConfigProviderGateway::getSingleton()`. Seeding via migration — like the 3.3
 * bracket seed — puts it in the committed state shared by dev, the migrated test DB, and production,
 * so `FinishWorkoutUseCase` (3.5) and the admin edit (3.7) resolve it everywhere. The fixed id keeps
 * the singleton addressable; `xp_per_workout_minute` starts at the column default 50.
 */
final class Version20260613130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed the LevelingConfig singleton (fixed id, xp_per_workout_minute=50).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'INSERT INTO leveling_config (id, xp_per_workout_minute, created_at, updated_at)'
            .' VALUES (:id, 25, :now, :now)',
            ['id' => '01000000000000000000000000', 'now' => '2026-06-13 00:00:00'],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM leveling_config WHERE id = '01000000000000000000000000'");
    }
}
