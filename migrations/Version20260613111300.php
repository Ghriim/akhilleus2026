<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260613111300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add daily step goal: player.daily_steps_target (global default 5000) + steps_daily_entry.target (per-day snapshot). Existing rows backfilled to 5000 via the column default.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player ADD daily_steps_target INT DEFAULT 5000 NOT NULL');
        $this->addSql('ALTER TABLE steps_daily_entry ADD target INT DEFAULT 5000 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player DROP daily_steps_target');
        $this->addSql('ALTER TABLE steps_daily_entry DROP target');
    }
}
