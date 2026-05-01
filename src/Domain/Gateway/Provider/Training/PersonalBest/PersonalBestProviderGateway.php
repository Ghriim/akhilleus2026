<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training\PersonalBest;

use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Workout\PersonalBestDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;

interface PersonalBestProviderGateway
{
    public function findOneForPlayerMovementType(PlayerDataModel $player, MovementDataModel $movement, string $type): ?PersonalBestDataModel;
}
