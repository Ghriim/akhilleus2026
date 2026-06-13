<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Hydration\GetTodayHydrationDataInput;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Hydration\HydrationDayDataOutput;
use App\Domain\DTO\DataOutput\Player\Tracking\Hydration\HydrationEntryDataOutput;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationDailySummaryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDailySummaryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;

final class GetTodayHydrationUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly HydrationDailySummaryProviderGateway $summaryProvider,
        private readonly HydrationDailySummaryPersisterGateway $summaryPersister,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @param GetTodayHydrationDataInput $input
     */
    public function execute(DataInputInterface $input): HydrationDayDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $today = $this->clock->now()->setTime(0, 0, 0);

        $summary = $this->summaryProvider->findOneByPlayerAndDateWithEntries($player, $today);
        if (null === $summary) {
            $summary = new HydrationDailySummaryDataModel($player, $today, $player->dailyHydrationTargetMl);
            $this->summaryPersister->create($summary);
        }

        return self::buildDayOutput($summary);
    }

    private static function buildDayOutput(HydrationDailySummaryDataModel $summary): HydrationDayDataOutput
    {
        $entries = [];
        foreach ($summary->entries as $entry) {
            $entries[] = new HydrationEntryDataOutput(
                $entry->id,
                $entry->loggedAt->format(\DateTimeInterface::ATOM),
                $entry->valueMl,
            );
        }

        return new HydrationDayDataOutput(
            $summary->date->format(\DateTimeInterface::ATOM),
            $summary->targetMl,
            $summary->amountConsumedMl,
            $entries,
        );
    }
}
