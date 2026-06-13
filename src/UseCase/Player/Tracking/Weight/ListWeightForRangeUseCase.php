<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Weight\ListWeightForRangeDataInput;
use App\Domain\DTO\DataModel\Tracking\Weight\WeightEntryDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Weight\WeightEntryDataOutput;
use App\Domain\Gateway\Provider\Tracking\Weight\WeightEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Weight\ListWeightForRangeValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class ListWeightForRangeUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly ListWeightForRangeValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WeightEntryProviderGateway $weightProvider,
    ) {
    }

    /**
     * @param ListWeightForRangeDataInput $input
     *
     * @return list<WeightEntryDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $this->validator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();

        // The gateway filters on `loggedAt` (a datetime), so widen `to` to the end of its day
        // to keep the range inclusive of entries logged at any time on the closing date.
        $entries = $this->weightProvider->findAllByPlayerForRange(
            $player,
            $input->from->setTime(0, 0, 0),
            $input->to->setTime(23, 59, 59, 999999),
        );

        return array_map(
            static fn (WeightEntryDataModel $entry) => new WeightEntryDataOutput(
                $entry->id,
                $entry->date->format(\DateTimeInterface::ATOM),
                $entry->loggedAt->format(\DateTimeInterface::ATOM),
                $entry->valueGrams,
            ),
            $entries,
        );
    }
}
