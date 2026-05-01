<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\UpdateExerciseSetPlannedDataInput;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class UpdateExerciseSetPlannedValidator extends AbstractLoggedPlayerValidator
{
    public const string ILLEGAL_STATUS_CODE = 'UPDATE_EXERCISE_SET_PLANNED_ILLEGAL_STATE';
    public const string TRACKING_MISMATCH_ERROR_CODE = 'UPDATE_EXERCISE_SET_PLANNED_TRACKING_MISMATCH';
    public const string FAILED_ERROR_CODE = 'UPDATE_EXERCISE_SET_PLANNED_VALIDATION_FAILED';

    public function validate(PlayerDataModel $player, UpdateExerciseSetPlannedDataInput $input, ExerciseSetDataModel $set): void
    {
        $this->assertPlayerOwns($player, $set);

        if (false === in_array($set->exercise->workout->status, WorkoutStatusRegistry::EDITABLE_STATUSES, true)) {
            throw new ValidationException('Planned values can only be edited on a planned or in-progress workout.', ['status' => [sprintf('Workout is in status "%s", expected one of: %s.', $set->exercise->workout->status, implode(', ', WorkoutStatusRegistry::EDITABLE_STATUSES))]], self::ILLEGAL_STATUS_CODE);
        }

        $movement = $set->exercise->movement;
        $trackingMap = [
            'plannedReps' => $movement->tracksRepetitions,
            'plannedWeight' => $movement->tracksWeight,
            'plannedDurationSeconds' => $movement->tracksDuration,
            'plannedDistanceMeters' => $movement->tracksDistance,
            'plannedInclinePercent' => $movement->tracksInclinePercent,
            'plannedInclineMeters' => $movement->tracksInclineMeters,
        ];
        $mismatchViolations = [];
        foreach ($trackingMap as $field => $tracks) {
            if (false === $tracks && null !== $input->{$field}) {
                $mismatchViolations[$field][] = sprintf('Movement "%s" does not track this field.', $movement->slug);
            }
        }
        if ([] !== $mismatchViolations) {
            throw new ValidationException('Planned values do not match the movement tracking flags.', $mismatchViolations, self::TRACKING_MISMATCH_ERROR_CODE);
        }

        $violations = [];
        if (null !== $input->plannedReps && 0 > $input->plannedReps) {
            $violations['plannedReps'][] = 'Planned reps must be zero or positive.';
        }
        if (null !== $input->plannedDurationSeconds && 0 > $input->plannedDurationSeconds) {
            $violations['plannedDurationSeconds'][] = 'Planned duration must be zero or positive.';
        }
        foreach (['plannedWeight', 'plannedDistanceMeters', 'plannedInclinePercent', 'plannedInclineMeters'] as $field) {
            $value = $input->{$field};
            if (null !== $value && false === self::isNonNegativeNumericString($value)) {
                $violations[$field][] = sprintf('%s must be a non-negative numeric value.', $field);
            }
        }

        if ([] !== $violations) {
            throw new ValidationException('Update exercise set planned data is invalid.', $violations, self::FAILED_ERROR_CODE);
        }
    }

    private static function isNonNegativeNumericString(string $value): bool
    {
        return 1 === preg_match('/^\d+(\.\d+)?$/', $value);
    }
}
