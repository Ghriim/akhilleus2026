<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdateHydrationEntryDataInput;
use App\Domain\DTO\DataModel\Tracking\Hydration\HydrationDailySummaryDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Hydration\HydrationDayDataOutput;
use App\Domain\DTO\DataOutput\Player\Tracking\Hydration\HydrationEntryDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationEntryProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Validator\Player\Tracking\Hydration\UpdateHydrationEntryValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateHydrationEntryUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly UpdateHydrationEntryValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly HydrationEntryProviderGateway $entryProvider,
        private readonly HydrationEntryPersisterGateway $entryPersister,
        private readonly QuestProgressionEvaluator $questProgressionEvaluator,
        private readonly ClockInterface $clock,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param UpdateHydrationEntryDataInput $input
     */
    public function execute(DataInputInterface $input): HydrationDayDataOutput
    {
        $this->validator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();

        $entry = $this->entryProvider->findOneByIdForPlayerAction($input->id, $player);
        if (null === $entry) {
            throw new EntityNotFoundException(sprintf('No hydration entry "%s" for this player.', $input->id));
        }

        $entry->valueMl = $input->valueMl;
        $this->entryPersister->update($entry);

        // The entry persister recomputes `amountConsumedMl` on the parent summary in place
        // (and initialises its entries collection while doing so), so the in-memory summary
        // already reflects the write.
        $this->questProgressionEvaluator->refreshFor($player, QuestMetricRegistry::HYDRATION_ML_DAILY, $this->clock->now());

        return $this->buildDayOutput($entry->summary);
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
