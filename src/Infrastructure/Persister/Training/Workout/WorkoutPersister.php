<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Training\Workout;

use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\Gateway\Persister\Training\Workout\WorkoutPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Service\WorkoutAggregateEvaluator;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<WorkoutDataModel>
 */
final readonly class WorkoutPersister extends AbstractBaseMysqlPersister implements WorkoutPersisterGateway
{
    public function create(WorkoutDataModel $model): WorkoutDataModel
    {
        if ('' === $model->name) {
            $model->name = self::deriveName($model);
        }
        $this->doCreate($model);

        return $model;
    }

    public function update(WorkoutDataModel $model): WorkoutDataModel
    {
        if (WorkoutStatusRegistry::COMPLETED === $model->status) {
            WorkoutAggregateEvaluator::evaluate($model);
        }
        $this->doUpdate($model);

        return $model;
    }

    public function delete(WorkoutDataModel $model): void
    {
        $this->doDelete($model);
    }

    /**
     * Builds the default workout label "Day Morning|Afternoon" (e.g. "Monday Morning") from the
     * most representative date the model carries: plannedAt for a planned workout, dateStart
     * otherwise, and clock->now() as a last-resort fallback (rare — IN_PROGRESS workouts must
     * always carry a dateStart by construction).
     */
    private function deriveName(WorkoutDataModel $model): string
    {
        $reference = $model->plannedAt ?? $model->dateStart ?? $this->clock->now();
        $period = 12 > (int) $reference->format('G') ? 'Morning' : 'Afternoon';

        return sprintf('%s %s', $reference->format('l'), $period);
    }
}
