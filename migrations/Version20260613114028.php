<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260613114028 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Leveling sub-domain schema (Phase 3.1): earned_experience, level_bracket, leveling_config tables + player.level/current_xp/xp_to_next_level columns (defaults 1/0/4000, existing rows backfilled). Seed rows (brackets + config singleton) land in Phase 3.10.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE earned_experience (id VARCHAR(26) NOT NULL, label VARCHAR(255) NOT NULL, amount INT NOT NULL, earned_at DATETIME NOT NULL, source_type VARCHAR(20) NOT NULL, source_id VARCHAR(26) NOT NULL, is_locked TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, player_id VARCHAR(26) NOT NULL, INDEX IDX_5C5591099E6F5DF (player_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE level_bracket (id VARCHAR(26) NOT NULL, from_level INT NOT NULL, to_level INT DEFAULT NULL, coefficient_a INT NOT NULL, exponent_k INT NOT NULL, offset_b INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_level_bracket_from_level (from_level), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE leveling_config (id VARCHAR(26) NOT NULL, xp_per_workout_minute INT DEFAULT 50 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE earned_experience ADD CONSTRAINT FK_5C5591099E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE player ADD level INT DEFAULT 1 NOT NULL, ADD current_xp INT DEFAULT 0 NOT NULL, ADD xp_to_next_level INT DEFAULT 4000 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE earned_experience DROP FOREIGN KEY FK_5C5591099E6F5DF');
        $this->addSql('DROP TABLE earned_experience');
        $this->addSql('DROP TABLE level_bracket');
        $this->addSql('DROP TABLE leveling_config');
        $this->addSql('ALTER TABLE player DROP level, DROP current_xp, DROP xp_to_next_level');
    }
}
