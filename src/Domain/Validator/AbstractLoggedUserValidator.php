<?php

declare(strict_types=1);

namespace App\Domain\Validator;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Security\LoggedUserResolverInterface;

abstract readonly class AbstractLoggedUserValidator implements DomainValidatorInterface
{
    public function __construct(
        private LoggedUserResolverInterface $loggedUserResolver,
    ) {
    }

    final protected function getLoggedUser(): UserDataModel
    {
        return $this->loggedUserResolver->getLoggedUser();
    }
}
