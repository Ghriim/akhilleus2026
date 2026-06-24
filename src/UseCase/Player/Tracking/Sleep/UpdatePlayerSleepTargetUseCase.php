<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Sleep\UpdatePlayerSleepTargetDataInput;
use App\Domain\DTO\DataOutput\Player\Tracking\Sleep\PlayerSleepTargetDataOutput;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Sleep\UpdatePlayerSleepTargetValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdatePlayerSleepTargetUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly UpdatePlayerSleepTargetValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly PlayerPersisterGateway $playerPersister,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param UpdatePlayerSleepTargetDataInput $input
     */
    public function execute(DataInputInterface $input): PlayerSleepTargetDataOutput
    {
        $this->validator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $player->dailySleepTargetMinutes = $input->targetMinutes;
        $this->playerPersister->update($player);

        return $this->mapper->map($player, PlayerSleepTargetDataOutput::class);
    }
}
