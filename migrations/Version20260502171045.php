<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260502171045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add workout-level aggregates (duration, volume, distance, inclineMeters) computed at finish time.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workout ADD duration INT DEFAULT NULL, ADD volume NUMERIC(15, 2) DEFAULT NULL, ADD distance NUMERIC(15, 2) DEFAULT NULL, ADD incline_meters NUMERIC(15, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workout DROP duration, DROP volume, DROP distance, DROP incline_meters');
    }
}
