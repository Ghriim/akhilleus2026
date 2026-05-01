<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Exercise;

use App\Domain\DTO\DataInput\Player\Training\Exercise\AddMovementToWorkoutDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class AddMovementToWorkoutValidator extends AbstractLoggedPlayerValidator
{
    public const string ILLEGAL_STATUS_CODE = 'ADD_MOVEMENT_TO_WORKOUT_ILLEGAL_STATE';
    public const string FAILED_ERROR_CODE = 'ADD_MOVEMENT_TO_WORKOUT_VALIDATION_FAILED';

    public function validate(PlayerDataModel $player, AddMovementToWorkoutDataInput $input, WorkoutDataModel $workout): void
    {
        $this->assertPlayerOwns($player, $workout);

        if (false === in_array($workout->status, WorkoutStatusRegistry::EDITABLE_STATUSES, true)) {
            throw new ValidationException('Movements can only be added to a planned or in-progress workout.', ['status' => [sprintf('Workout is in status "%s", expected one of: %s.', $workout->status, implode(', ', WorkoutStatusRegistry::EDITABLE_STATUSES))]], self::ILLEGAL_STATUS_CODE);
        }

        $violations = [];
        if ('' === trim($input->workoutId)) {
            $violations['workoutId'][] = 'Workout id must not be empty.';
        }
        if ('' === trim($input->movementId)) {
            $violations['movementId'][] = 'Movement id must not be empty.';
        }
        if (0 > $input->restDurationSeconds) {
            $violations['restDurationSeconds'][] = 'Rest duration must be zero or positive.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Add movement to workout data is invalid.', $violations, self::FAILED_ERROR_CODE);
        }
    }
}
