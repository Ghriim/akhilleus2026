<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260502100506 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename exercise_set.completed → is_complete to match the boolean is/has naming convention.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exercise_set CHANGE completed is_complete TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exercise_set CHANGE is_complete completed TINYINT DEFAULT 0 NOT NULL');
    }
}
