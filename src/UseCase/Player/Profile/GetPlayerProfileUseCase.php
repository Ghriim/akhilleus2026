<?php

declare(strict_types=1);

namespace App\UseCase\Player\Profile;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Profile\GetPlayerProfileDataInput;
use App\Domain\DTO\DataOutput\Player\Profile\PlayerProfileDataOutput;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class GetPlayerProfileUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
    ) {
    }

    /**
     * @param GetPlayerProfileDataInput $input
     */
    public function execute(DataInputInterface $input): PlayerProfileDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();

        return new PlayerProfileDataOutput(
            $player->id,
            $player->displayName,
            $player->level,
            $player->currentXp,
            $player->xpToNextLevel,
        );
    }
}
