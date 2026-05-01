<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;

interface ExerciseSetProviderGateway
{
    public function findOneByIdForPlayerAction(string $id, PlayerDataModel $player): ?ExerciseSetDataModel;

    /**
     * @return list<ExerciseSetDataModel>
     */
    public function findAllByExerciseIdForPlayerAction(string $exerciseId, PlayerDataModel $player): array;
}
