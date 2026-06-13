<?php

declare(strict_types=1);

namespace App\UseCase\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpdatePlayerDailyStepsTargetDataInput;
use App\Domain\DTO\DataOutput\Player\Tracking\Steps\PlayerStepsTargetDataOutput;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Tracking\Steps\UpdatePlayerDailyStepsTargetValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class UpdatePlayerDailyStepsTargetUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly UpdatePlayerDailyStepsTargetValidator $validator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly PlayerPersisterGateway $playerPersister,
    ) {
    }

    /**
     * @param UpdatePlayerDailyStepsTargetDataInput $input
     */
    public function execute(DataInputInterface $input): PlayerStepsTargetDataOutput
    {
        $this->validator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $player->dailyStepsTarget = $input->target;
        $this->playerPersister->update($player);

        return new PlayerStepsTargetDataOutput($player->dailyStepsTarget);
    }
}
