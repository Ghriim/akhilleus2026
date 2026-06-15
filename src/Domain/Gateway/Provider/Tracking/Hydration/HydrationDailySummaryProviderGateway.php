<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;

interface HydrationDailySummaryProviderGateway
{
    /**
     * Eager-fetches the linked `entries` collection so the day-tracking widget renders the
     * day's hydration entries without a follow-up query. Returns `null` when the player has
     * not yet logged anything for this date — the matching use case lazy-creates a new
     * Summary on first read in that case.
     */
    public function findOneByPlayerAndDateWithEntries(PlayerDataModel $player, \DateTimeImmutable $date): ?HydrationDailySummaryDataModel;

    /**
     * Daily summaries whose `date` falls in `[from, to]` (inclusive), ordered by `date` ASC.
     * Feeds the `HYDRATION_ML_DAILY` quest metric resolver (sum of `amountConsumedMl`).
     *
     * @return list<HydrationDailySummaryDataModel>
     */
    public function findAllByPlayerForRange(PlayerDataModel $player, \DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
