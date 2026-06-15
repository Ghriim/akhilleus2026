<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Questing\QuestProgression;

use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\DTO\DataModel\Questing\QuestProgression\QuestProgressionDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;

interface QuestProgressionProviderGateway
{
    /**
     * The player's progression on a quest for one period. `$startDate` is the period start
     * (`null` for a `UNIQUE` quest, matched with `IS NULL`). Drives the find-or-create flow.
     */
    public function findOneByPlayerQuestPeriod(
        PlayerDataModel $player,
        QuestDataModel $quest,
        ?\DateTimeImmutable $startDate,
    ): ?QuestProgressionDataModel;

    /**
     * The player's progressions whose current period contains `$now`, for active `DAILY` quests.
     *
     * @return list<QuestProgressionDataModel>
     */
    public function findAllByPlayerActiveDaily(PlayerDataModel $player, \DateTimeImmutable $now): array;

    /**
     * Same as the daily variant, for active `WEEKLY` quests.
     *
     * @return list<QuestProgressionDataModel>
     */
    public function findAllByPlayerActiveWeekly(PlayerDataModel $player, \DateTimeImmutable $now): array;

    /**
     * Same as the daily variant, for active `MONTHLY` quests.
     *
     * @return list<QuestProgressionDataModel>
     */
    public function findAllByPlayerActiveMonthly(PlayerDataModel $player, \DateTimeImmutable $now): array;

    /**
     * Every progression the player holds on a `UNIQUE`-periodicity quest (one per quest).
     *
     * @return list<QuestProgressionDataModel>
     */
    public function findAllUniqueByPlayer(PlayerDataModel $player): array;

    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?QuestProgressionDataModel;
}
