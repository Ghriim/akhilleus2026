<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505153556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tracking sub-domain entities (Steps, Hydration summary + entry, Sleep, Weight) + Player.dailyHydrationTargetMl (v1 Phase 2.1).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE hydration_daily_summary (id VARCHAR(26) NOT NULL, date DATE NOT NULL, target_ml INT NOT NULL, amount_consumed_ml INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, player_id VARCHAR(26) NOT NULL, INDEX IDX_5F9E39C599E6F5DF (player_id), UNIQUE INDEX uniq_hydration_daily_summary_player_date (player_id, date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE hydration_entry (id VARCHAR(26) NOT NULL, logged_at DATETIME NOT NULL, value_ml INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, summary_id VARCHAR(26) NOT NULL, INDEX IDX_44C767202AC2D45C (summary_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sleep_daily_entry (id VARCHAR(26) NOT NULL, date DATE NOT NULL, bed_at DATETIME NOT NULL, wake_at DATETIME NOT NULL, duration_minutes INT NOT NULL, quality SMALLINT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, player_id VARCHAR(26) NOT NULL, INDEX IDX_2DF9D49899E6F5DF (player_id), UNIQUE INDEX uniq_sleep_daily_entry_player_date (player_id, date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE steps_daily_entry (id VARCHAR(26) NOT NULL, date DATE NOT NULL, count INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, player_id VARCHAR(26) NOT NULL, INDEX IDX_E7164B8699E6F5DF (player_id), UNIQUE INDEX uniq_steps_daily_entry_player_date (player_id, date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE weight_entry (id VARCHAR(26) NOT NULL, logged_at DATETIME NOT NULL, date DATE NOT NULL, value_grams INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, player_id VARCHAR(26) NOT NULL, INDEX IDX_1486C8C099E6F5DF (player_id), UNIQUE INDEX uniq_weight_entry_player_date (player_id, date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE hydration_daily_summary ADD CONSTRAINT FK_5F9E39C599E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE hydration_entry ADD CONSTRAINT FK_44C767202AC2D45C FOREIGN KEY (summary_id) REFERENCES hydration_daily_summary (id)');
        $this->addSql('ALTER TABLE sleep_daily_entry ADD CONSTRAINT FK_2DF9D49899E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE steps_daily_entry ADD CONSTRAINT FK_E7164B8699E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE weight_entry ADD CONSTRAINT FK_1486C8C099E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE player ADD daily_hydration_target_ml INT DEFAULT 1000 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE hydration_daily_summary DROP FOREIGN KEY FK_5F9E39C599E6F5DF');
        $this->addSql('ALTER TABLE hydration_entry DROP FOREIGN KEY FK_44C767202AC2D45C');
        $this->addSql('ALTER TABLE sleep_daily_entry DROP FOREIGN KEY FK_2DF9D49899E6F5DF');
        $this->addSql('ALTER TABLE steps_daily_entry DROP FOREIGN KEY FK_E7164B8699E6F5DF');
        $this->addSql('ALTER TABLE weight_entry DROP FOREIGN KEY FK_1486C8C099E6F5DF');
        $this->addSql('DROP TABLE hydration_daily_summary');
        $this->addSql('DROP TABLE hydration_entry');
        $this->addSql('DROP TABLE sleep_daily_entry');
        $this->addSql('DROP TABLE steps_daily_entry');
        $this->addSql('DROP TABLE weight_entry');
        $this->addSql('ALTER TABLE player DROP daily_hydration_target_ml');
    }
}
