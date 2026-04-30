<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\Gateway\Persister\Training\Workout\WorkoutPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<WorkoutDataModel>
 */
final readonly class WorkoutPersister extends AbstractBaseMysqlPersister implements WorkoutPersisterGateway
{
    public function create(WorkoutDataModel $model): WorkoutDataModel
    {
        $this->doCreate($model);

        return $model;
    }

    public function update(WorkoutDataModel $model): WorkoutDataModel
    {
        $this->doUpdate($model);

        return $model;
    }

    public function delete(WorkoutDataModel $model): void
    {
        $this->doDelete($model);
    }
}
