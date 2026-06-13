<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Seeds the v1 baseline leveling curve (Phase 3.3, pulled forward from 3.10): the three contiguous
 * LevelBracket rows from specifications/v1/initial-requirements.md §Migration & seeding. Seeding via
 * migration (rather than fixtures only) makes the curve part of the committed schema state, so it is
 * present in the dev DB, the migrated test DB, and production alike — registration's
 * `xpToNextLevel = marginalCostFor(2)` resolves everywhere. Fixed ULIDs keep the seed deterministic.
 *
 * The LevelingConfig singleton seed stays in Phase 3.10 (not needed for the registration baseline).
 */
final class Version20260613120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed the v1 baseline leveling curve: 3 contiguous LevelBracket rows (1-10: 1000n²; 11-20: 3000n²+50000; 21-∞: 500n³+1000000).';
    }

    public function up(Schema $schema): void
    {
        $now = '2026-06-13 00:00:00';
        $this->addSql(
            'INSERT INTO level_bracket (id, from_level, to_level, coefficient_a, exponent_k, offset_b, created_at, updated_at) VALUES'
            ." ('01JBRACKET0000000000000001', 1, 10, 1000, 2, 0, :now, :now),"
            ." ('01JBRACKET0000000000000002', 11, 20, 3000, 2, 50000, :now, :now),"
            ." ('01JBRACKET0000000000000003', 21, NULL, 500, 3, 1000000, :now, :now)",
            ['now' => $now],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "DELETE FROM level_bracket WHERE id IN ('01JBRACKET0000000000000001', '01JBRACKET0000000000000002', '01JBRACKET0000000000000003')"
        );
    }
}
