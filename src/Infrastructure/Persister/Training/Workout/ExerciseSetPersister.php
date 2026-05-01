<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\Gateway\Persister\Training\Workout\ExerciseSetPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<ExerciseSetDataModel>
 */
final readonly class ExerciseSetPersister extends AbstractBaseMysqlPersister implements ExerciseSetPersisterGateway
{
    public function create(ExerciseSetDataModel $model): ExerciseSetDataModel
    {
        $this->doCreate($model);

        return $model;
    }

    public function update(ExerciseSetDataModel $model): ExerciseSetDataModel
    {
        $this->doUpdate($model);

        return $model;
    }

    public function delete(ExerciseSetDataModel $model): void
    {
        $this->doDelete($model);
    }
}
