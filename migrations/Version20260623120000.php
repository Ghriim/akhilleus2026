<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds player-level sleep & weight goals: Player.dailySleepTargetMinutes (default 480 = 8h) and
 * Player.targetWeightGrams (nullable, optional goal). Surfaced on the player profile so the
 * sleep/weight tracking widgets can render an objective.
 */
final class Version20260623120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Player sleep target (minutes, default 480) + optional weight target (grams).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD daily_sleep_target_minutes INT DEFAULT 480 NOT NULL, ADD target_weight_grams INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP daily_sleep_target_minutes, DROP target_weight_grams');
    }
}
