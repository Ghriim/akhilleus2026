<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpsertStepsForDayDataInput;
use App\Domain\DTO\DataModel\Tracking\Steps\StepsDailyEntryDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Steps\StepsDailyEntryDataOutput;
use App\Domain\Gateway\Persister\Tracking\Steps\StepsDailyEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Steps\StepsDailyEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Steps\UpsertStepsForDayValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class UpsertStepsForDayUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly UpsertStepsForDayValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly StepsDailyEntryProviderGateway $stepsProvider,
        private readonly StepsDailyEntryPersisterGateway $stepsPersister,
    ) {
    }

    /**
     * @param UpsertStepsForDayDataInput $input
     */
    public function execute(DataInputInterface $input): StepsDailyEntryDataOutput
    {
        $this->validator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $date = $input->date->setTime(0, 0, 0);

        $entry = $this->stepsProvider->findOneByPlayerAndDate($player, $date);
        if (null === $entry) {
            $entry = new StepsDailyEntryDataModel($player, $date, $input->count);
            $this->stepsPersister->create($entry);
        } else {
            $entry->count = $input->count;
            $this->stepsPersister->update($entry);
        }

        return new StepsDailyEntryDataOutput(
            $entry->id,
            $entry->date->format(\DateTimeInterface::ATOM),
            $entry->count,
        );
    }
}
