<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260614153305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Questing sub-domain schema (Phase 4.1): quest + quest_progression tables (quest_progression N:1 to quest and player, unique on (quest_id, player_id, start_date)). No seed rows — quests are admin-created.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE quest (id VARCHAR(26) NOT NULL, label VARCHAR(255) NOT NULL, kind VARCHAR(20) NOT NULL, metric VARCHAR(40) DEFAULT NULL, periodicity VARCHAR(20) NOT NULL, target_value NUMERIC(12, 4) DEFAULT NULL, date_start DATETIME NOT NULL, date_end DATETIME DEFAULT NULL, rewarded_xp INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quest_progression (id VARCHAR(26) NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, completion_date DATETIME DEFAULT NULL, claimed_date DATETIME DEFAULT NULL, current_value NUMERIC(12, 4) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, quest_id VARCHAR(26) NOT NULL, player_id VARCHAR(26) NOT NULL, INDEX IDX_E2E2C584209E9EF4 (quest_id), INDEX IDX_E2E2C58499E6F5DF (player_id), UNIQUE INDEX uniq_quest_progression_quest_player_start (quest_id, player_id, start_date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE quest_progression ADD CONSTRAINT FK_E2E2C584209E9EF4 FOREIGN KEY (quest_id) REFERENCES quest (id)');
        $this->addSql('ALTER TABLE quest_progression ADD CONSTRAINT FK_E2E2C58499E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quest_progression DROP FOREIGN KEY FK_E2E2C584209E9EF4');
        $this->addSql('ALTER TABLE quest_progression DROP FOREIGN KEY FK_E2E2C58499E6F5DF');
        $this->addSql('DROP TABLE quest');
        $this->addSql('DROP TABLE quest_progression');
    }
}
