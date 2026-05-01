<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\Gateway\Persister\Training\Workout\ExercisePersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<ExerciseDataModel>
 */
final readonly class ExercisePersister extends AbstractBaseMysqlPersister implements ExercisePersisterGateway
{
    public function create(ExerciseDataModel $model): ExerciseDataModel
    {
        $this->doCreate($model);

        return $model;
    }

    public function update(ExerciseDataModel $model): ExerciseDataModel
    {
        $this->doUpdate($model);

        return $model;
    }

    public function delete(ExerciseDataModel $model): void
    {
        $this->doDelete($model);
    }
}
