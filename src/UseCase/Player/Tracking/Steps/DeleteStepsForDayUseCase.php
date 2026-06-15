<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\DeleteStepsForDayDataInput;
use App\Domain\DTO\DataOutput\Player\Tracking\Steps\DeleteStepsForDayDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Tracking\Steps\StepsDailyEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Steps\StepsDailyEntryProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;

final class DeleteStepsForDayUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly StepsDailyEntryProviderGateway $stepsProvider,
        private readonly StepsDailyEntryPersisterGateway $stepsPersister,
        private readonly QuestProgressionEvaluator $questProgressionEvaluator,
        private readonly ClockInterface $clock,
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

        $this->questProgressionEvaluator->refreshFor($player, QuestMetricRegistry::STEPS_DAILY, $this->clock->now());

        return new DeleteStepsForDayDataOutput($date->format(\DateTimeInterface::ATOM));
    }
}
