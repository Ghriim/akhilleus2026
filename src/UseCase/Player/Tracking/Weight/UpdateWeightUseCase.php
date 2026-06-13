<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Weight\UpdateWeightDataInput;
use App\Domain\DTO\DataOutput\Player\Tracking\Weight\WeightEntryDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Tracking\Weight\WeightEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Weight\WeightEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Weight\UpdateWeightValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class UpdateWeightUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly UpdateWeightValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WeightEntryProviderGateway $weightProvider,
        private readonly WeightEntryPersisterGateway $weightPersister,
    ) {
    }

    /**
     * @param UpdateWeightDataInput $input
     */
    public function execute(DataInputInterface $input): WeightEntryDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $this->validator->validate($player, $input);

        $entry = $this->weightProvider->findOneByIdForPlayerAction($input->id, $player);
        if (null === $entry) {
            throw new EntityNotFoundException(sprintf('No weight entry "%s" for this player.', $input->id));
        }

        $entry->loggedAt = $input->loggedAt;
        $entry->valueGrams = $input->valueGrams;
        // `date` is re-derived from `loggedAt` by the persister to keep the (player, date) constraint in sync.

        $this->weightPersister->update($entry);

        return new WeightEntryDataOutput(
            $entry->id,
            $entry->date->format(\DateTimeInterface::ATOM),
            $entry->loggedAt->format(\DateTimeInterface::ATOM),
            $entry->valueGrams,
        );
    }
}
