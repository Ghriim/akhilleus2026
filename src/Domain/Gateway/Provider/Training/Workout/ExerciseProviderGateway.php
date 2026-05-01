<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;

interface ExerciseProviderGateway
{
    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?ExerciseDataModel;

    /**
     * @return list<ExerciseDataModel>
     */
    public function findAllByWorkoutIdForPlayerAction(string $workoutId, PlayerDataModel $player): array;
}
