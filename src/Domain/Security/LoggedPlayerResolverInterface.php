<?php

declare(strict_types=1);

namespace App\Domain\Security;

use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\UnauthorizedException;

interface LoggedPlayerResolverInterface
{
    /**
     * @throws UnauthorizedException when no user is authenticated, or the authenticated user has no player profile
     */
    public function getLoggedPlayer(): PlayerDataModel;
}
