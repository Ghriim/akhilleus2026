<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdateHydrationDailyTargetDataInput;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Hydration\HydrationDayDataOutput;
use App\Domain\DTO\DataOutput\Player\Tracking\Hydration\HydrationEntryDataOutput;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationDailySummaryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDailySummaryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Hydration\UpdateHydrationDailyTargetValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateHydrationDailyTargetUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly UpdateHydrationDailyTargetValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly HydrationDailySummaryProviderGateway $summaryProvider,
        private readonly HydrationDailySummaryPersisterGateway $summaryPersister,
        private readonly ClockInterface $clock,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param UpdateHydrationDailyTargetDataInput $input
     */
    public function execute(DataInputInterface $input): HydrationDayDataOutput
    {
        $this->validator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $today = $this->clock->now()->setTime(0, 0, 0);

        $summary = $this->summaryProvider->findOneByPlayerAndDateWithEntries($player, $today);
        if (null === $summary) {
            $summary = new HydrationDailySummaryDataModel($player, $today, $input->targetMl);
            $this->summaryPersister->create($summary);
        } else {
            $summary->targetMl = $input->targetMl;
            $this->summaryPersister->update($summary);
        }

        return $this->buildDayOutput($summary);
    }

    private function buildDayOutput(HydrationDailySummaryDataModel $summary): HydrationDayDataOutput
    {
        $entries = [];
        foreach ($summary->entries as $entry) {
            $entries[] = $this->mapper->map($entry, HydrationEntryDataOutput::class);
        }

        return new HydrationDayDataOutput(
            $summary->date->format(\DateTimeInterface::ATOM),
            $summary->targetMl,
            $summary->amountConsumedMl,
            $entries,
        );
    }
}
