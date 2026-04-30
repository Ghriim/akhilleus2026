<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;

interface WorkoutPersisterGateway
{
    public function create(WorkoutDataModel $model): WorkoutDataModel;

    public function update(WorkoutDataModel $model): WorkoutDataModel;

    public function delete(WorkoutDataModel $model): void;
}
