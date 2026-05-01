<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;

interface ExerciseSetPersisterGateway
{
    public function create(ExerciseSetDataModel $model): ExerciseSetDataModel;

    public function update(ExerciseSetDataModel $model): ExerciseSetDataModel;

    public function delete(ExerciseSetDataModel $model): void;
}
