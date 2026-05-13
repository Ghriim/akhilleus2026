<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Tracking\Steps;

use App\Domain\DTO\DataModel\Tracking\Steps\StepsDailyEntryDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;

interface StepsDailyEntryProviderGateway
{
    public function findOneByPlayerAndDate(PlayerDataModel $player, \DateTimeImmutable $date): ?StepsDailyEntryDataModel;

    /**
     * Both bounds inclusive. Ordered by date ascending.
     *
     * @return list<StepsDailyEntryDataModel>
     */
    public function findAllByPlayerForRange(PlayerDataModel $player, \DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
