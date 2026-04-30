<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\User;

use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

interface PlayerProviderGateway
{
    public function findOneByUserForLoggedPlayer(UserDataModel $user): ?PlayerDataModel;
}
