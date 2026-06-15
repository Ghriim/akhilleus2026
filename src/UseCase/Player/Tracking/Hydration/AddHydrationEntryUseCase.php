<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Hydration\AddHydrationEntryDataInput;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationEntryDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Hydration\HydrationDayDataOutput;
use App\Domain\DTO\DataOutput\Player\Tracking\Hydration\HydrationEntryDataOutput;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationDailySummaryPersisterGateway;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDailySummaryProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Validator\Player\Tracking\Hydration\AddHydrationEntryValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;

final class AddHydrationEntryUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly AddHydrationEntryValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly HydrationDailySummaryProviderGateway $summaryProvider,
        private readonly HydrationDailySummaryPersisterGateway $summaryPersister,
        private readonly HydrationEntryPersisterGateway $entryPersister,
        private readonly QuestProgressionEvaluator $questProgressionEvaluator,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @param AddHydrationEntryDataInput $input
     */
    public function execute(DataInputInterface $input): HydrationDayDataOutput
    {
        $this->validator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $date = $input->loggedAt->setTime(0, 0, 0);

        $summary = $this->summaryProvider->findOneByPlayerAndDateWithEntries($player, $date);
        if (null === $summary) {
            $summary = new HydrationDailySummaryDataModel($player, $date, $player->dailyHydrationTargetMl);
            $this->summaryPersister->create($summary);
        }

        $entry = new HydrationEntryDataModel($summary, $input->loggedAt, $input->valueMl);
        $this->entryPersister->create($entry);

        // The entry persister adds the new entry to the summary's collection and recomputes
        // `amountConsumedMl` in place, so the in-memory summary already reflects the write.
        $this->questProgressionEvaluator->refreshFor($player, QuestMetricRegistry::HYDRATION_ML_DAILY, $this->clock->now());

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
