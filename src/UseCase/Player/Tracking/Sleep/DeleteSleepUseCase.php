<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Sleep\DeleteSleepDataInput;
use App\Domain\DTO\DataOutput\Player\Tracking\Sleep\DeleteSleepDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Tracking\Sleep\SleepDailyEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Sleep\SleepDailyEntryProviderGateway;
use App\Domain\Registry\Questing\Quest\QuestMetricRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Service\Questing\QuestProgressionEvaluator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;

final class DeleteSleepUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly SleepDailyEntryProviderGateway $sleepProvider,
        private readonly SleepDailyEntryPersisterGateway $sleepPersister,
        private readonly QuestProgressionEvaluator $questProgressionEvaluator,
        private readonly ClockInterface $clock,
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

        $this->questProgressionEvaluator->refreshFor($player, QuestMetricRegistry::SLEEP_DURATION_MINUTES, $this->clock->now());

        return new DeleteSleepDataOutput($input->id);
    }
}
