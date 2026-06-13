<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdatePlayerDailyHydrationTargetDataInput;
use App\Domain\DTO\DataOutput\Player\Tracking\Hydration\PlayerHydrationTargetDataOutput;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Hydration\UpdatePlayerDailyHydrationTargetValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class UpdatePlayerDailyHydrationTargetUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly UpdatePlayerDailyHydrationTargetValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly PlayerPersisterGateway $playerPersister,
    ) {
    }

    /**
     * @param UpdatePlayerDailyHydrationTargetDataInput $input
     */
    public function execute(DataInputInterface $input): PlayerHydrationTargetDataOutput
    {
        $this->validator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $player->dailyHydrationTargetMl = $input->targetMl;
        $this->playerPersister->update($player);

        return new PlayerHydrationTargetDataOutput($player->dailyHydrationTargetMl);
    }
}
