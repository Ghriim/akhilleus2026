<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Workout same-day hard delete cascade (Phase 5.3). Re-creates the workout-aggregate foreign keys
 * with delete behaviour so a hard `DELETE FROM workout` propagates cleanly:
 * - exercise.workout_id and exercise_set.exercise_id gain ON DELETE CASCADE (children removed with the workout);
 * - personal_best.workout_id and personal_best.exercise_set_id gain ON DELETE SET NULL (the PB value is
 *   preserved, only the dangling link is cleared).
 */
final class Version20260617120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Workout hard-delete cascade FKs (Phase 5.3): CASCADE on exercise/exercise_set children, SET NULL on personal_best workout/exercise_set references.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE exercise DROP FOREIGN KEY FK_AEDAD51CA6CCCFC9');
        $this->addSql('ALTER TABLE exercise ADD CONSTRAINT FK_AEDAD51CA6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE exercise_set DROP FOREIGN KEY FK_704B80A0E934951A');
        $this->addSql('ALTER TABLE exercise_set ADD CONSTRAINT FK_704B80A0E934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE personal_best DROP FOREIGN KEY FK_9C077172A6CCCFC9');
        $this->addSql('ALTER TABLE personal_best ADD CONSTRAINT FK_9C077172A6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE personal_best DROP FOREIGN KEY FK_9C07717265B49873');
        $this->addSql('ALTER TABLE personal_best ADD CONSTRAINT FK_9C07717265B49873 FOREIGN KEY (exercise_set_id) REFERENCES exercise_set (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE exercise DROP FOREIGN KEY FK_AEDAD51CA6CCCFC9');
        $this->addSql('ALTER TABLE exercise ADD CONSTRAINT FK_AEDAD51CA6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id)');

        $this->addSql('ALTER TABLE exercise_set DROP FOREIGN KEY FK_704B80A0E934951A');
        $this->addSql('ALTER TABLE exercise_set ADD CONSTRAINT FK_704B80A0E934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id)');

        $this->addSql('ALTER TABLE personal_best DROP FOREIGN KEY FK_9C077172A6CCCFC9');
        $this->addSql('ALTER TABLE personal_best ADD CONSTRAINT FK_9C077172A6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id)');

        $this->addSql('ALTER TABLE personal_best DROP FOREIGN KEY FK_9C07717265B49873');
        $this->addSql('ALTER TABLE personal_best ADD CONSTRAINT FK_9C07717265B49873 FOREIGN KEY (exercise_set_id) REFERENCES exercise_set (id)');
    }
}
