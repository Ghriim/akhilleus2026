<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpdateStepsDailyTargetDataInput;
use App\Domain\DTO\DataModel\Tracking\Steps\StepsDailyEntryDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Steps\StepsDailyEntryDataOutput;
use App\Domain\Gateway\Persister\Tracking\Steps\StepsDailyEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Steps\StepsDailyEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Steps\UpdateStepsDailyTargetValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateStepsDailyTargetUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly UpdateStepsDailyTargetValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly StepsDailyEntryProviderGateway $stepsProvider,
        private readonly StepsDailyEntryPersisterGateway $stepsPersister,
        private readonly ClockInterface $clock,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param UpdateStepsDailyTargetDataInput $input
     */
    public function execute(DataInputInterface $input): StepsDailyEntryDataOutput
    {
        $this->validator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $today = $this->clock->now()->setTime(0, 0, 0);

        $entry = $this->stepsProvider->findOneByPlayerAndDate($player, $today);
        if (null === $entry) {
            $entry = new StepsDailyEntryDataModel($player, $today, 0, $input->target);
            $this->stepsPersister->create($entry);
        } else {
            $entry->target = $input->target;
            $this->stepsPersister->update($entry);
        }

        return $this->mapper->map($entry, StepsDailyEntryDataOutput::class);
    }
}
