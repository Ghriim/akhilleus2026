<?php

declare(strict_types=1);

namespace App\UseCase\Player\Questing;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Questing\ListQuestsDataInput;
use App\Domain\DTO\DataModel\Questing\QuestProgression\QuestProgressionDataModel;
use App\Domain\DTO\DataOutput\Player\Questing\QuestProgressionDataOutput;
use App\Domain\Gateway\Provider\Questing\Quest\QuestProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionFactory;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;

/**
 * Shared body for the four periodicity-scoped quest listings. Loads the active quests of the
 * concrete subclass's periodicity, find-or-creates each one's current-period progression, and maps
 * them to `QuestProgressionDataOutput`. Automatic-quest `currentValue` reflects the last value
 * computed by `QuestProgressionEvaluator` on a tracking write (no recompute on read).
 */
abstract class AbstractListQuestsUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly QuestProviderGateway $questProvider,
        private readonly QuestProgressionFactory $progressionFactory,
        private readonly ClockInterface $clock,
    ) {
    }

    abstract protected function periodicity(): string;

    /**
     * @param ListQuestsDataInput $input
     *
     * @return list<QuestProgressionDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $now = $this->clock->now();

        $items = [];
        foreach ($this->questProvider->findActiveByPeriodicityForPlayer($this->periodicity(), $now) as $quest) {
            $items[] = self::toOutput($this->progressionFactory->findOrCreate($quest, $player, $now));
        }

        return $items;
    }

    private static function toOutput(QuestProgressionDataModel $progression): QuestProgressionDataOutput
    {
        $quest = $progression->quest;

        return new QuestProgressionDataOutput(
            $progression->id,
            $quest->id,
            $quest->label,
            $quest->kind,
            $quest->metric,
            $quest->periodicity,
            $progression->currentValue,
            $quest->targetValue,
            $quest->rewardedXp,
            $progression->status,
            $progression->startDate?->format(\DateTimeInterface::ATOM),
            $progression->endDate?->format(\DateTimeInterface::ATOM),
        );
    }
}
