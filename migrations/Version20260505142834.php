<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505142834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable videoLink + gifLink columns to movement (v1 Phase 1.1).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE movement ADD video_link VARCHAR(2048) DEFAULT NULL, ADD gif_link VARCHAR(2048) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE movement DROP video_link, DROP gif_link');
    }
}
