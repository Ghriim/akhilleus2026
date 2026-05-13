<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\DeleteStepsForDayDataInput;
use App\Domain\DTO\DataOutput\Player\Tracking\Steps\DeleteStepsForDayDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Tracking\Steps\StepsDailyEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Steps\StepsDailyEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class DeleteStepsForDayUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly StepsDailyEntryProviderGateway $stepsProvider,
        private readonly StepsDailyEntryPersisterGateway $stepsPersister,
    ) {
    }

    /**
     * @param DeleteStepsForDayDataInput $input
     */
    public function execute(DataInputInterface $input): DeleteStepsForDayDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $date = $input->date->setTime(0, 0, 0);

        $entry = $this->stepsProvider->findOneByPlayerAndDate($player, $date);
        if (null === $entry) {
            throw new EntityNotFoundException(sprintf('No steps entry for date "%s".', $date->format('Y-m-d')));
        }

        $this->stepsPersister->delete($entry);

        return new DeleteStepsForDayDataOutput($date->format(\DateTimeInterface::ATOM));
    }
}
