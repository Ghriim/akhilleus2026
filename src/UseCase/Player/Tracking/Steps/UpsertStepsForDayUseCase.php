<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpsertStepsForDayDataInput;
use App\Domain\DTO\DataModel\Tracking\Steps\StepsDailyEntryDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Steps\StepsDailyEntryDataOutput;
use App\Domain\Gateway\Persister\Tracking\Steps\StepsDailyEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Steps\StepsDailyEntryProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Validator\Player\Tracking\Steps\UpsertStepsForDayValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpsertStepsForDayUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly UpsertStepsForDayValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly StepsDailyEntryProviderGateway $stepsProvider,
        private readonly StepsDailyEntryPersisterGateway $stepsPersister,
        private readonly QuestProgressionEvaluator $questProgressionEvaluator,
        private readonly ClockInterface $clock,
        private readonly ObjectMapperInterface $mapper,
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
            $entry = new StepsDailyEntryDataModel($player, $date, $input->count, $player->dailyStepsTarget);
            $this->stepsPersister->create($entry);
        } else {
            $entry->count = $input->count;
            $this->stepsPersister->update($entry);
        }

        $this->questProgressionEvaluator->refreshFor($player, QuestMetricRegistry::STEPS_DAILY, $this->clock->now());

        return $this->mapper->map($entry, StepsDailyEntryDataOutput::class);
    }
}
