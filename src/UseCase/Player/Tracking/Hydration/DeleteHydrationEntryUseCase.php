<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Hydration\DeleteHydrationEntryDataInput;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Hydration\HydrationDayDataOutput;
use App\Domain\DTO\DataOutput\Player\Tracking\Hydration\HydrationEntryDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationEntryProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;

final class DeleteHydrationEntryUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly HydrationEntryProviderGateway $entryProvider,
        private readonly HydrationEntryPersisterGateway $entryPersister,
        private readonly QuestProgressionEvaluator $questProgressionEvaluator,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @param DeleteHydrationEntryDataInput $input
     */
    public function execute(DataInputInterface $input): HydrationDayDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();

        $entry = $this->entryProvider->findOneByIdForPlayerAction($input->id, $player);
        if (null === $entry) {
            throw new EntityNotFoundException(sprintf('No hydration entry "%s" for this player.', $input->id));
        }

        $summary = $entry->summary;
        $this->entryPersister->delete($entry);

        // The entry persister removes the entry from the summary's collection and recomputes
        // `amountConsumedMl` in place; the summary row itself survives (empty days read as 0).
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
