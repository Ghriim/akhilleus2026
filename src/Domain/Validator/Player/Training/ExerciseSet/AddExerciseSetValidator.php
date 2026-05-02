<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\AddExerciseSetDataInput;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class AddExerciseSetValidator extends AbstractLoggedPlayerValidator
{
    public const string ILLEGAL_STATUS_CODE = 'ADD_EXERCISE_SET_ILLEGAL_STATE';
    public const string STATUS_FIELD_MISMATCH_ERROR_CODE = 'ADD_EXERCISE_SET_STATUS_FIELD_MISMATCH';
    public const string TRACKING_MISMATCH_ERROR_CODE = 'ADD_EXERCISE_SET_TRACKING_MISMATCH';
    public const string FAILED_ERROR_CODE = 'ADD_EXERCISE_SET_VALIDATION_FAILED';

    private const array PLANNED_FIELDS = [
        'plannedReps',
        'plannedWeight',
        'plannedDurationSeconds',
        'plannedDistanceMeters',
        'plannedInclinePercent',
        'plannedInclineMeters',
    ];

    private const array ACHIEVED_FIELDS = [
        'achievedReps',
        'achievedWeight',
        'achievedDurationSeconds',
        'achievedDistanceMeters',
        'achievedInclinePercent',
        'achievedInclineMeters',
    ];

    public function validate(PlayerDataModel $player, AddExerciseSetDataInput $input, ExerciseDataModel $exercise): void
    {
        $this->assertPlayerOwns($player, $exercise);

        $status = $exercise->workout->status;
        if (false === in_array($status, WorkoutStatusRegistry::EDITABLE_STATUSES, true)) {
            throw new ValidationException('Sets can only be added to a planned or in-progress workout.', ['status' => [sprintf('Workout is in status "%s", expected one of: %s.', $status, implode(', ', WorkoutStatusRegistry::EDITABLE_STATUSES))]], self::ILLEGAL_STATUS_CODE);
        }

        // PLANNED workouts only accept planned* fields; IN_PROGRESS workouts only accept achieved* fields.
        $forbiddenFields = WorkoutStatusRegistry::PLANNED === $status ? self::ACHIEVED_FIELDS : self::PLANNED_FIELDS;
        $forbiddenMessage = WorkoutStatusRegistry::PLANNED === $status
            ? 'Achieved values cannot be set on a planned workout.'
            : 'Planned values cannot be set on an in-progress workout.';
        $statusViolations = [];
        foreach ($forbiddenFields as $field) {
            if (null !== $input->{$field}) {
                $statusViolations[$field][] = $forbiddenMessage;
            }
        }
        if ([] !== $statusViolations) {
            throw new ValidationException('Provided fields do not match the workout status.', $statusViolations, self::STATUS_FIELD_MISMATCH_ERROR_CODE);
        }

        $movement = $exercise->movement;
        $activeFields = WorkoutStatusRegistry::PLANNED === $status ? self::PLANNED_FIELDS : self::ACHIEVED_FIELDS;
        $trackingMap = [
            $activeFields[0] => $movement->tracksRepetitions,
            $activeFields[1] => $movement->tracksWeight,
            $activeFields[2] => $movement->tracksDuration,
            $activeFields[3] => $movement->tracksDistance,
            $activeFields[4] => $movement->tracksInclinePercent,
            $activeFields[5] => $movement->tracksInclineMeters,
        ];
        $mismatchViolations = [];
        foreach ($trackingMap as $field => $tracks) {
            if (false === $tracks && null !== $input->{$field}) {
                $mismatchViolations[$field][] = sprintf('Movement "%s" does not track this field.', $movement->slug);
            }
        }
        if ([] !== $mismatchViolations) {
            throw new ValidationException('Values do not match the movement tracking flags.', $mismatchViolations, self::TRACKING_MISMATCH_ERROR_CODE);
        }

        // $activeFields[0] = reps, $activeFields[2] = duration, others = numeric strings.
        $violations = [];
        $repsField = $activeFields[0];
        $durationField = $activeFields[2];
        if (null !== $input->{$repsField} && 0 > $input->{$repsField}) {
            $violations[$repsField][] = sprintf('%s must be zero or positive.', $repsField);
        }
        if (null !== $input->{$durationField} && 0 > $input->{$durationField}) {
            $violations[$durationField][] = sprintf('%s must be zero or positive.', $durationField);
        }
        foreach ([$activeFields[1], $activeFields[3], $activeFields[4], $activeFields[5]] as $field) {
            $value = $input->{$field};
            if (null !== $value && false === self::isNonNegativeNumericString($value)) {
                $violations[$field][] = sprintf('%s must be a non-negative numeric value.', $field);
            }
        }

        if ([] !== $violations) {
            throw new ValidationException('Add exercise set data is invalid.', $violations, self::FAILED_ERROR_CODE);
        }
    }

    private static function isNonNegativeNumericString(string $value): bool
    {
        return 1 === preg_match('/^\d+(\.\d+)?$/', $value);
    }
}
