<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the optional Quest.display_order column — drives the player-facing quest list order
 * (ascending, NULLs last). Nullable: existing quests keep their date-based ordering until set.
 */
final class Version20260624120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Quest optional display_order (nullable INT) for player-list ordering.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quest ADD display_order INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quest DROP display_order');
    }
}
