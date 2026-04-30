<?php

declare(strict_types=1);

namespace App\Domain\Security;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\UnauthorizedException;

interface LoggedUserResolverInterface
{
    /**
     * @throws UnauthorizedException when no user is authenticated for the current request
     */
    public function getLoggedUser(): UserDataModel;
}
