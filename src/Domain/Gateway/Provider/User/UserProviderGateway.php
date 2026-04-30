<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\User;

use App\Domain\DTO\DataModel\User\UserDataModel;

interface UserProviderGateway
{
    public function findOneByEmailForAuthentication(string $email): ?UserDataModel;

    public function findOneByEmailForUniquenessCheck(string $email): ?UserDataModel;
}
