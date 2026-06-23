<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Weight\DeleteWeightDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Tracking\Weight\WeightEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Weight\WeightEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class DeleteWeightUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WeightEntryProviderGateway $weightProvider,
        private readonly WeightEntryPersisterGateway $weightPersister,
    ) {
    }

    /**
     * @param DeleteWeightDataInput $input
     */
    public function execute(DataInputInterface $input): null
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();

        $entry = $this->weightProvider->findOneByIdForPlayerAction($input->id, $player);
        if (null === $entry) {
            throw new EntityNotFoundException(sprintf('No weight entry "%s" for this player.', $input->id));
        }

        $this->weightPersister->delete($entry);

        return null;
    }
}
