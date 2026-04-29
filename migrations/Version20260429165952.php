<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260429165952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema (Phase 1.4): 11 tables — equipment, muscle, movement (+ M:N movement_secondary_muscle, movement_equipment), user, player, workout, exercise, exercise_set, personal_best.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE equipment (id VARCHAR(26) NOT NULL, slug VARCHAR(50) NOT NULL, label VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D338D583989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE exercise (id VARCHAR(26) NOT NULL, rest_duration_seconds INT NOT NULL, position INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, workout_id VARCHAR(26) NOT NULL, movement_id VARCHAR(26) NOT NULL, INDEX IDX_AEDAD51CA6CCCFC9 (workout_id), INDEX IDX_AEDAD51C229E70A7 (movement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE exercise_set (id VARCHAR(26) NOT NULL, position INT NOT NULL, planned_reps INT DEFAULT NULL, achieved_reps INT DEFAULT NULL, planned_weight NUMERIC(6, 2) DEFAULT NULL, achieved_weight NUMERIC(6, 2) DEFAULT NULL, planned_duration_seconds INT DEFAULT NULL, achieved_duration_seconds INT DEFAULT NULL, planned_distance_meters NUMERIC(10, 2) DEFAULT NULL, achieved_distance_meters NUMERIC(10, 2) DEFAULT NULL, planned_incline_percent NUMERIC(5, 2) DEFAULT NULL, achieved_incline_percent NUMERIC(5, 2) DEFAULT NULL, planned_incline_meters NUMERIC(8, 2) DEFAULT NULL, achieved_incline_meters NUMERIC(8, 2) DEFAULT NULL, completed TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, exercise_id VARCHAR(26) NOT NULL, INDEX IDX_704B80A0E934951A (exercise_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE movement (id VARCHAR(26) NOT NULL, slug VARCHAR(80) NOT NULL, label VARCHAR(150) NOT NULL, tracks_repetitions TINYINT DEFAULT 0 NOT NULL, tracks_weight TINYINT DEFAULT 0 NOT NULL, tracks_duration TINYINT DEFAULT 0 NOT NULL, tracks_distance TINYINT DEFAULT 0 NOT NULL, tracks_incline_percent TINYINT DEFAULT 0 NOT NULL, tracks_incline_meters TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, main_muscle_id VARCHAR(26) NOT NULL, UNIQUE INDEX UNIQ_F4DD95F7989D9B62 (slug), INDEX IDX_F4DD95F715F97C16 (main_muscle_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE movement_secondary_muscle (movement_id VARCHAR(26) NOT NULL, muscle_id VARCHAR(26) NOT NULL, INDEX IDX_8C94D076229E70A7 (movement_id), INDEX IDX_8C94D076354FDBB4 (muscle_id), PRIMARY KEY (movement_id, muscle_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE movement_equipment (movement_id VARCHAR(26) NOT NULL, equipment_id VARCHAR(26) NOT NULL, INDEX IDX_E11F9908229E70A7 (movement_id), INDEX IDX_E11F9908517FE9FE (equipment_id), PRIMARY KEY (movement_id, equipment_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE muscle (id VARCHAR(26) NOT NULL, slug VARCHAR(50) NOT NULL, label VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_F31119EF989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE personal_best (id VARCHAR(26) NOT NULL, type VARCHAR(50) NOT NULL, value NUMERIC(15, 4) NOT NULL, achieved_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, player_id VARCHAR(26) NOT NULL, movement_id VARCHAR(26) NOT NULL, workout_id VARCHAR(26) DEFAULT NULL, exercise_set_id VARCHAR(26) DEFAULT NULL, INDEX IDX_9C07717299E6F5DF (player_id), INDEX IDX_9C077172229E70A7 (movement_id), INDEX IDX_9C077172A6CCCFC9 (workout_id), INDEX IDX_9C07717265B49873 (exercise_set_id), UNIQUE INDEX uniq_personal_best_player_movement_type (player_id, movement_id, type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE player (id VARCHAR(26) NOT NULL, display_name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id VARCHAR(26) NOT NULL, UNIQUE INDEX UNIQ_98197A65A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id VARCHAR(26) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE workout (id VARCHAR(26) NOT NULL, status VARCHAR(20) NOT NULL, date_start DATETIME DEFAULT NULL, date_end DATETIME DEFAULT NULL, planned_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, player_id VARCHAR(26) NOT NULL, INDEX IDX_649FFB7299E6F5DF (player_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE exercise ADD CONSTRAINT FK_AEDAD51CA6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id)');
        $this->addSql('ALTER TABLE exercise ADD CONSTRAINT FK_AEDAD51C229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id)');
        $this->addSql('ALTER TABLE exercise_set ADD CONSTRAINT FK_704B80A0E934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id)');
        $this->addSql('ALTER TABLE movement ADD CONSTRAINT FK_F4DD95F715F97C16 FOREIGN KEY (main_muscle_id) REFERENCES muscle (id)');
        $this->addSql('ALTER TABLE movement_secondary_muscle ADD CONSTRAINT FK_8C94D076229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement_secondary_muscle ADD CONSTRAINT FK_8C94D076354FDBB4 FOREIGN KEY (muscle_id) REFERENCES muscle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement_equipment ADD CONSTRAINT FK_E11F9908229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE movement_equipment ADD CONSTRAINT FK_E11F9908517FE9FE FOREIGN KEY (equipment_id) REFERENCES equipment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE personal_best ADD CONSTRAINT FK_9C07717299E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE personal_best ADD CONSTRAINT FK_9C077172229E70A7 FOREIGN KEY (movement_id) REFERENCES movement (id)');
        $this->addSql('ALTER TABLE personal_best ADD CONSTRAINT FK_9C077172A6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id)');
        $this->addSql('ALTER TABLE personal_best ADD CONSTRAINT FK_9C07717265B49873 FOREIGN KEY (exercise_set_id) REFERENCES exercise_set (id)');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE workout ADD CONSTRAINT FK_649FFB7299E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exercise DROP FOREIGN KEY FK_AEDAD51CA6CCCFC9');
        $this->addSql('ALTER TABLE exercise DROP FOREIGN KEY FK_AEDAD51C229E70A7');
        $this->addSql('ALTER TABLE exercise_set DROP FOREIGN KEY FK_704B80A0E934951A');
        $this->addSql('ALTER TABLE movement DROP FOREIGN KEY FK_F4DD95F715F97C16');
        $this->addSql('ALTER TABLE movement_secondary_muscle DROP FOREIGN KEY FK_8C94D076229E70A7');
        $this->addSql('ALTER TABLE movement_secondary_muscle DROP FOREIGN KEY FK_8C94D076354FDBB4');
        $this->addSql('ALTER TABLE movement_equipment DROP FOREIGN KEY FK_E11F9908229E70A7');
        $this->addSql('ALTER TABLE movement_equipment DROP FOREIGN KEY FK_E11F9908517FE9FE');
        $this->addSql('ALTER TABLE personal_best DROP FOREIGN KEY FK_9C07717299E6F5DF');
        $this->addSql('ALTER TABLE personal_best DROP FOREIGN KEY FK_9C077172229E70A7');
        $this->addSql('ALTER TABLE personal_best DROP FOREIGN KEY FK_9C077172A6CCCFC9');
        $this->addSql('ALTER TABLE personal_best DROP FOREIGN KEY FK_9C07717265B49873');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65A76ED395');
        $this->addSql('ALTER TABLE workout DROP FOREIGN KEY FK_649FFB7299E6F5DF');
        $this->addSql('DROP TABLE equipment');
        $this->addSql('DROP TABLE exercise');
        $this->addSql('DROP TABLE exercise_set');
        $this->addSql('DROP TABLE movement');
        $this->addSql('DROP TABLE movement_secondary_muscle');
        $this->addSql('DROP TABLE movement_equipment');
        $this->addSql('DROP TABLE muscle');
        $this->addSql('DROP TABLE personal_best');
        $this->addSql('DROP TABLE player');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE workout');
    }
}
