<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Sleep\DeleteSleepDataInput;
use App\Domain\DTO\DataOutput\Player\Tracking\Sleep\DeleteSleepDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Tracking\Sleep\SleepDailyEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepDailyEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class DeleteSleepUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly SleepDailyEntryProviderGateway $sleepProvider,
        private readonly SleepDailyEntryPersisterGateway $sleepPersister,
    ) {
    }

    /**
     * @param DeleteSleepDataInput $input
     */
    public function execute(DataInputInterface $input): DeleteSleepDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();

        $entry = $this->sleepProvider->findOneByIdForPlayerAction($input->id, $player);
        if (null === $entry) {
            throw new EntityNotFoundException(sprintf('No sleep entry "%s" for this player.', $input->id));
        }

        $this->sleepPersister->delete($entry);

        return new DeleteSleepDataOutput($input->id);
    }
}
