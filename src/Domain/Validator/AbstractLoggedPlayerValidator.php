<?php

declare(strict_types=1);

namespace App\Domain\Validator;

use App\Domain\DTO\DataModel\OwnedByPlayerInterface;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\UnauthorizedException;
use App\Domain\Security\LoggedPlayerResolverInterface;

abstract readonly class AbstractLoggedPlayerValidator
{
    public function __construct(
        private LoggedPlayerResolverInterface $loggedPlayerResolver,
    ) {
    }

    final protected function assertPlayerOwns(PlayerDataModel $player, OwnedByPlayerInterface $model): void
    {
        if ($model->player !== $player) {
            throw new UnauthorizedException(sprintf('Player %s #%s is not the owner of this data.', $player->displayName, $player->id));
        }
    }

    final protected function getLoggedPlayer(): PlayerDataModel
    {
        return $this->loggedPlayerResolver->getLoggedPlayer();
    }
}
