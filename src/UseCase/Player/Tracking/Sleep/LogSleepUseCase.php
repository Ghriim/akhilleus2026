<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Sleep\LogSleepDataInput;
use App\Domain\DTO\DataModel\Tracking\Sleep\SleepDailyEntryDataModel;
use App\Domain\DTO\DataOutput\Player\Tracking\Sleep\SleepDailyEntryDataOutput;
use App\Domain\Gateway\Persister\Tracking\Sleep\SleepDailyEntryPersisterGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Validator\Player\Tracking\Sleep\LogSleepValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;

final class LogSleepUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LogSleepValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly SleepDailyEntryPersisterGateway $sleepPersister,
        private readonly QuestProgressionEvaluator $questProgressionEvaluator,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @param LogSleepDataInput $input
     */
    public function execute(DataInputInterface $input): SleepDailyEntryDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $this->validator->validate($player, $input);

        $entry = new SleepDailyEntryDataModel(
            $player,
            $input->wakeAt->setTime(0, 0, 0),
            $input->bedAt,
            $input->wakeAt,
        );
        $entry->quality = $input->quality;

        $this->sleepPersister->create($entry);

        $this->questProgressionEvaluator->refreshFor($player, QuestMetricRegistry::SLEEP_DURATION_MINUTES, $this->clock->now());

        return new SleepDailyEntryDataOutput(
            $entry->id,
            $entry->date->format(\DateTimeInterface::ATOM),
            $entry->bedAt->format(\DateTimeInterface::ATOM),
            $entry->wakeAt->format(\DateTimeInterface::ATOM),
            $entry->durationMinutes,
            $entry->quality,
        );
    }
}
