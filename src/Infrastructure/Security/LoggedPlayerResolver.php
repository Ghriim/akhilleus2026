<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\UnauthorizedException;
use App\Domain\Gateway\Provider\User\PlayerProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Security\LoggedUserResolverInterface;

final readonly class LoggedPlayerResolver implements LoggedPlayerResolverInterface
{
    public function __construct(
        private LoggedUserResolverInterface $loggedUserResolver,
        private PlayerProviderGateway $playerProviderGateway,
    ) {
    }

    public function getLoggedPlayer(): PlayerDataModel
    {
        $user = $this->loggedUserResolver->getLoggedUser();
        $player = $this->playerProviderGateway->findOneByUserForLoggedPlayer($user);

        if (null === $player) {
            throw new UnauthorizedException('Authenticated user has no player profile.');
        }

        return $player;
    }
}
