<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;

interface ExercisePersisterGateway
{
    public function create(ExerciseDataModel $model): ExerciseDataModel;

    public function update(ExerciseDataModel $model): ExerciseDataModel;

    public function delete(ExerciseDataModel $model): void;
}
