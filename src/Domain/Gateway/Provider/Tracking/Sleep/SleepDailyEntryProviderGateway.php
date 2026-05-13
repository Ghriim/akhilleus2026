<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Tracking\Sleep;

use App\Domain\DTO\DataModel\Tracking\Sleep\SleepDailyEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;

interface SleepDailyEntryProviderGateway
{
    /** Used by the Create-style validator for the (player, date) uniqueness rule. */
    public function findOneByPlayerAndDate(PlayerDataModel $player, \DateTimeImmutable $date): ?SleepDailyEntryDataModel;

    /** Player-scoped 404 for Update / Delete use cases. */
    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?SleepDailyEntryDataModel;

    /**
     * Both bounds inclusive. Ordered by date ascending.
     *
     * @return list<SleepDailyEntryDataModel>
     */
    public function findAllByPlayerForRange(PlayerDataModel $player, \DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
