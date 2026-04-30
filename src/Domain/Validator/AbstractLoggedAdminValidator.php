<?php

declare(strict_types=1);

namespace App\Domain\Validator;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Security\LoggedUserResolverInterface;

abstract readonly class AbstractLoggedAdminValidator implements DomainValidatorInterface
{
    public function __construct(
        private LoggedUserResolverInterface $loggedUserResolver,
    ) {
    }

    final protected function getLoggedAdmin(): UserDataModel
    {
        return $this->loggedUserResolver->getLoggedUser();
    }
}
