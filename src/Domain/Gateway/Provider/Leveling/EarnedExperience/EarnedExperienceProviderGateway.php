<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Leveling\EarnedExperience;

use App\Domain\DTO\DataModel\Leveling\EarnedExperience\EarnedExperienceDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;

interface EarnedExperienceProviderGateway
{
    /**
     * All still-unlocked entries earned strictly before `$cutoff`, ordered by player then earnedAt
     * ASC. Feeds the nightly leveling cron (Phase 5/6), which folds them into the players' totals.
     *
     * @return list<EarnedExperienceDataModel>
     */
    public function findUnlockedBefore(\DateTimeImmutable $cutoff): array;

    /**
     * One page of the player's XP journal, most recent first.
     *
     * @return list<EarnedExperienceDataModel>
     */
    public function findAllByPlayerForJournal(PlayerDataModel $player, int $page, int $perPage): array;

    public function countByPlayerForJournal(PlayerDataModel $player): int;

    /**
     * The entry originating from a given source (e.g. a workout), used by Phase 5's same-day
     * workout-edit propagation to recompute or remove the matching grant. Null when none exists.
     */
    public function findOneBySourceTypeAndId(string $sourceType, string $sourceId): ?EarnedExperienceDataModel;
}
