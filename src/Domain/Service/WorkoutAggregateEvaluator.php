<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;

/**
 * Computes workout-level aggregates (duration / volume / distance / inclineMeters) from a
 * fully-loaded workout (exercises + sets eager-fetched), assigns them onto the workout, and
 * returns the same instance. Mutating in-place keeps the call site terse — `update($workout)`
 * in `WorkoutPersister` invokes this whenever the workout reaches the COMPLETED state.
 *
 * Each numeric aggregate stays `null` when no set in the workout carries a non-null value for
 * the underlying achieved* field, so "no data" is preserved instead of being collapsed to zero.
 */
final readonly class WorkoutAggregateEvaluator
{
    public static function evaluate(WorkoutDataModel $workout): WorkoutDataModel
    {
        $workout->duration = self::computeDuration($workout);
        $workout->volume = self::sumOver($workout, static fn (ExerciseSetDataModel $set): ?string => $set->achievedWeight);
        $workout->distance = self::sumOver($workout, static fn (ExerciseSetDataModel $set): ?string => $set->achievedDistanceMeters);
        $workout->inclineMeters = self::sumOver($workout, static fn (ExerciseSetDataModel $set): ?string => $set->achievedInclineMeters);

        return $workout;
    }

    private static function computeDuration(WorkoutDataModel $workout): ?int
    {
        if (null === $workout->dateStart || null === $workout->dateEnd) {
            return null;
        }

        return $workout->dateEnd->getTimestamp() - $workout->dateStart->getTimestamp();
    }

    /**
     * @param callable(ExerciseSetDataModel): ?string $extractor
     *
     * @return numeric-string|null
     */
    private static function sumOver(WorkoutDataModel $workout, callable $extractor): ?string
    {
        $found = false;
        $sum = 0.0;
        foreach ($workout->exercises as $exercise) {
            foreach ($exercise->exerciseSets as $set) {
                $value = $extractor($set);
                if (null !== $value) {
                    $found = true;
                    $sum += (float) $value;
                }
            }
        }

        return true === $found ? number_format($sum, 2, '.', '') : null;
    }
}
