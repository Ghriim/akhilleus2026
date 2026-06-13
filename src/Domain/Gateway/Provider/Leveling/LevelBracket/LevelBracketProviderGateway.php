<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Leveling\LevelBracket;

use App\Domain\DTO\DataModel\Leveling\LevelBracket\LevelBracketDataModel;

interface LevelBracketProviderGateway
{
    /**
     * The whole curve, ordered by `fromLevel` ascending. `LevelingCalculator` reloads it on
     * demand and resolves the bracket containing a given level in memory.
     *
     * @return list<LevelBracketDataModel>
     */
    public function findAllOrderedAsc(): array;

    /**
     * The bracket covering `$level` (`fromLevel <= level <= toLevel`, `toLevel = null` open-ended),
     * or null when the curve has a gap. Used by the admin contiguity/overlap validators (Phase 3.6).
     */
    public function findContainingLevel(int $level): ?LevelBracketDataModel;

    public function findOneByIdForAdminAction(string $id): ?LevelBracketDataModel;
}
