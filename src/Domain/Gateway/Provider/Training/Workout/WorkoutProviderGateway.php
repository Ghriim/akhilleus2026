<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;

interface WorkoutProviderGateway
{
    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?WorkoutDataModel;

    public function findOneByIdForFinishWorkout(string $id, PlayerDataModel $player): ?WorkoutDataModel;
}
