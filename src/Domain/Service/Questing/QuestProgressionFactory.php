<?php

declare(strict_types=1);

namespace App\Domain\Service\Questing;

use App\Domain\DTO\DataModel\Questing\Quest\QuestDataModel;
use App\Domain\DTO\DataModel\Questing\QuestProgression\QuestProgressionDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\Questing\QuestProgression\QuestProgressionPersisterGateway;
use App\Domain\Gateway\Provider\Questing\QuestProgression\QuestProgressionProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestKindRegistry;
use App\Domain\Registry\Questing\QuestProgression\QuestProgressionStatusRegistry;

/**
 * Lazily materialises the `QuestProgression` row for a given (quest, player, period). Returns the
 * existing row when present, otherwise creates + persists one with the proper default status
 * (`CLAIMABLE` for MANUAL quests, `IN_PROGRESS` + `currentValue = 0` for AUTOMATIC ones).
 */
final readonly class QuestProgressionFactory
{
    public function __construct(
        private QuestPeriodResolver $periodResolver,
        private QuestProgressionProviderGateway $progressionProvider,
        private QuestProgressionPersisterGateway $progressionPersister,
    ) {
    }

    public function findOrCreate(QuestDataModel $quest, PlayerDataModel $player, \DateTimeImmutable $now): QuestProgressionDataModel
    {
        $period = $this->periodResolver->resolve($quest->periodicity, $now);

        $existing = $this->progressionProvider->findOneByPlayerQuestPeriod($player, $quest, $period['startDate']);
        if (null !== $existing) {
            return $existing;
        }

        $isAutomatic = QuestKindRegistry::AUTOMATIC === $quest->kind;

        $progression = new QuestProgressionDataModel(
            $quest,
            $player,
            $isAutomatic ? QuestProgressionStatusRegistry::IN_PROGRESS : QuestProgressionStatusRegistry::CLAIMABLE,
            $period['startDate'],
            $period['endDate'],
            $isAutomatic ? '0' : null,
        );
        $this->progressionPersister->create($progression);

        return $progression;
    }
}
