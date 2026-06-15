<?php

declare(strict_types=1);

namespace App\UseCase\Player\Questing;

use App\Domain\Registry\Questing\Quest\QuestPeriodicityRegistry;

final class ListDailyQuestsUseCase extends AbstractListQuestsUseCase
{
    protected function periodicity(): string
    {
        return QuestPeriodicityRegistry::DAILY;
    }
}
