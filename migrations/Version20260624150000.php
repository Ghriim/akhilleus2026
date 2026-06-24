<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the front_theme catalog (admin-managed display themes): name (unique), optional description,
 * optional stored preview-image filename.
 */
final class Version20260624150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create front_theme table (name unique, nullable description + image filename).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE front_theme (
            id VARCHAR(26) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            image_filename VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_front_theme_name (name),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE front_theme');
    }
}
