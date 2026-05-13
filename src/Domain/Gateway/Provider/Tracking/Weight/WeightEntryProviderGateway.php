<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Tracking\Weight;

use App\Domain\DTO\DataModel\Tracking\Weight\WeightEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;

interface WeightEntryProviderGateway
{
    /** Used by the Create-style validator for the (player, date) uniqueness rule. */
    public function findOneByPlayerAndDate(PlayerDataModel $player, \DateTimeImmutable $date): ?WeightEntryDataModel;

    /** Player-scoped 404 for Update / Delete use cases. */
    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?WeightEntryDataModel;

    /**
     * Both bounds inclusive. Ordered by `loggedAt` ascending — feeds the future progression chart.
     *
     * @return list<WeightEntryDataModel>
     */
    public function findAllByPlayerForRange(PlayerDataModel $player, \DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
