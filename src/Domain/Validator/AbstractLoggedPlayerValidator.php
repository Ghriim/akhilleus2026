<?php

declare(strict_types=1);

namespace App\Domain\Validator;

use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Security\LoggedPlayerResolverInterface;

abstract readonly class AbstractLoggedPlayerValidator implements DomainValidatorInterface
{
    public function __construct(
        private LoggedPlayerResolverInterface $loggedPlayerResolver,
    ) {
    }

    final protected function getLoggedPlayer(): PlayerDataModel
    {
        return $this->loggedPlayerResolver->getLoggedPlayer();
    }
}
