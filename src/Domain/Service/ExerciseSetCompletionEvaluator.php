<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;

/**
 * Computes whether an exercise set is complete based on the movement's tracking flags:
 * a set is complete iff every achieved* field corresponding to a tracked dimension is non-null.
 *
 * Stateless and pure — no side effects on the set.
 */
final readonly class ExerciseSetCompletionEvaluator
{
    public static function isComplete(ExerciseSetDataModel $set, MovementDataModel $movement): bool
    {
        if (true === $movement->tracksRepetitions && null === $set->achievedReps) {
            return false;
        }
        if (true === $movement->tracksWeight && null === $set->achievedWeight) {
            return false;
        }
        if (true === $movement->tracksDuration && null === $set->achievedDurationSeconds) {
            return false;
        }
        if (true === $movement->tracksDistance && null === $set->achievedDistanceMeters) {
            return false;
        }
        if (true === $movement->tracksInclinePercent && null === $set->achievedInclinePercent) {
            return false;
        }
        if (true === $movement->tracksInclineMeters && null === $set->achievedInclineMeters) {
            return false;
        }

        return true;
    }
}
