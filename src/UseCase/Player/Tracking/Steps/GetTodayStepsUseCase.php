<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\GetTodayStepsDataInput;
use App\Domain\DTO\DataModel\Tracking\Steps\StepsDailyEntryDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Steps\StepsDailyEntryDataOutput;
use App\Domain\Gateway\Persister\Tracking\Steps\StepsDailyEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Steps\StepsDailyEntryProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;

final class GetTodayStepsUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly StepsDailyEntryProviderGateway $stepsProvider,
        private readonly StepsDailyEntryPersisterGateway $stepsPersister,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @param GetTodayStepsDataInput $input
     */
    public function execute(DataInputInterface $input): StepsDailyEntryDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $today = $this->clock->now()->setTime(0, 0, 0);

        $entry = $this->stepsProvider->findOneByPlayerAndDate($player, $today);
        if (null === $entry) {
            $entry = new StepsDailyEntryDataModel($player, $today, 0, $player->dailyStepsTarget);
            $this->stepsPersister->create($entry);
        }

        return new StepsDailyEntryDataOutput(
            $entry->id,
            $entry->date->format(\DateTimeInterface::ATOM),
            $entry->count,
            $entry->target,
        );
    }
}
