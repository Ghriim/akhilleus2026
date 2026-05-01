<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\UpdateExerciseSetAchievedDataInput;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class UpdateExerciseSetAchievedValidator extends AbstractLoggedPlayerValidator
{
    public const string ILLEGAL_STATUS_CODE = 'UPDATE_EXERCISE_SET_ACHIEVED_ILLEGAL_STATE';
    public const string TRACKING_MISMATCH_ERROR_CODE = 'UPDATE_EXERCISE_SET_ACHIEVED_TRACKING_MISMATCH';
    public const string FAILED_ERROR_CODE = 'UPDATE_EXERCISE_SET_ACHIEVED_VALIDATION_FAILED';

    public function validate(PlayerDataModel $player, UpdateExerciseSetAchievedDataInput $input, ExerciseSetDataModel $set): void
    {
        $this->assertPlayerOwns($player, $set);

        if (WorkoutStatusRegistry::IN_PROGRESS !== $set->exercise->workout->status) {
            throw new ValidationException('Achieved values can only be recorded on an in-progress workout.', ['status' => [sprintf('Workout is in status "%s", expected "%s".', $set->exercise->workout->status, WorkoutStatusRegistry::IN_PROGRESS)]], self::ILLEGAL_STATUS_CODE);
        }

        $movement = $set->exercise->movement;
        $trackingMap = [
            'achievedReps' => $movement->tracksRepetitions,
            'achievedWeight' => $movement->tracksWeight,
            'achievedDurationSeconds' => $movement->tracksDuration,
            'achievedDistanceMeters' => $movement->tracksDistance,
            'achievedInclinePercent' => $movement->tracksInclinePercent,
            'achievedInclineMeters' => $movement->tracksInclineMeters,
        ];
        $mismatchViolations = [];
        foreach ($trackingMap as $field => $tracks) {
            if (false === $tracks && null !== $input->{$field}) {
                $mismatchViolations[$field][] = sprintf('Movement "%s" does not track this field.', $movement->slug);
            }
        }
        if ([] !== $mismatchViolations) {
            throw new ValidationException('Achieved values do not match the movement tracking flags.', $mismatchViolations, self::TRACKING_MISMATCH_ERROR_CODE);
        }

        $violations = [];
        if (null !== $input->achievedReps && 0 > $input->achievedReps) {
            $violations['achievedReps'][] = 'Achieved reps must be zero or positive.';
        }
        if (null !== $input->achievedDurationSeconds && 0 > $input->achievedDurationSeconds) {
            $violations['achievedDurationSeconds'][] = 'Achieved duration must be zero or positive.';
        }
        foreach (['achievedWeight', 'achievedDistanceMeters', 'achievedInclinePercent', 'achievedInclineMeters'] as $field) {
            $value = $input->{$field};
            if (null !== $value && false === self::isNonNegativeNumericString($value)) {
                $violations[$field][] = sprintf('%s must be a non-negative numeric value.', $field);
            }
        }

        if ([] !== $violations) {
            throw new ValidationException('Update exercise set achieved data is invalid.', $violations, self::FAILED_ERROR_CODE);
        }
    }

    private static function isNonNegativeNumericString(string $value): bool
    {
        return 1 === preg_match('/^\d+(\.\d+)?$/', $value);
    }
}
