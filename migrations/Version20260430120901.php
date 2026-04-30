<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260430120901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactor admin: drop display_name; add first_name, last_name, job_title, hired_at, status.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `admin` ADD last_name VARCHAR(100) NOT NULL, ADD job_title VARCHAR(150) NOT NULL, ADD hired_at DATE NOT NULL, ADD status VARCHAR(20) NOT NULL, CHANGE display_name first_name VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `admin` ADD display_name VARCHAR(100) NOT NULL, DROP first_name, DROP last_name, DROP job_title, DROP hired_at, DROP status');
    }
}
