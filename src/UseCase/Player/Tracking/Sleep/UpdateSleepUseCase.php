<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Sleep\UpdateSleepDataInput;
use App\Domain\DTO\DataOutput\Player\Tracking\Sleep\SleepDailyEntryDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Tracking\Sleep\SleepDailyEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepDailyEntryProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\Domain\Validator\Player\Tracking\Sleep\UpdateSleepValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateSleepUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly UpdateSleepValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly SleepDailyEntryProviderGateway $sleepProvider,
        private readonly SleepDailyEntryPersisterGateway $sleepPersister,
        private readonly QuestProgressionEvaluator $questProgressionEvaluator,
        private readonly ClockInterface $clock,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param UpdateSleepDataInput $input
     */
    public function execute(DataInputInterface $input): SleepDailyEntryDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $this->validator->validate($player, $input);

        $entry = $this->sleepProvider->findOneByIdForPlayerAction($input->id, $player);
        if (null === $entry) {
            throw new EntityNotFoundException(sprintf('No sleep entry "%s" for this player.', $input->id));
        }

        $entry->bedAt = $input->bedAt;
        $entry->wakeAt = $input->wakeAt;
        $entry->date = $input->wakeAt->setTime(0, 0, 0);
        $entry->quality = $input->quality;

        $this->sleepPersister->update($entry);

        $this->questProgressionEvaluator->refreshFor($player, QuestMetricRegistry::SLEEP_DURATION_MINUTES, $this->clock->now());

        return $this->mapper->map($entry, SleepDailyEntryDataOutput::class);
    }
}
