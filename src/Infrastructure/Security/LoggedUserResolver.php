<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\UnauthorizedException;
use App\Domain\Security\LoggedUserResolverInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class LoggedUserResolver implements LoggedUserResolverInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function getLoggedUser(): UserDataModel
    {
        $user = $this->security->getUser();

        if (false === $user instanceof UserDataModel) {
            throw new UnauthorizedException('No authenticated user for the current request.');
        }

        return $user;
    }
}
