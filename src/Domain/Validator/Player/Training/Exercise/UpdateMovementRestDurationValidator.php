<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Exercise;

use App\Domain\DTO\DataInput\Player\Training\Exercise\UpdateMovementRestDurationDataInput;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class UpdateMovementRestDurationValidator extends AbstractLoggedPlayerValidator
{
    public const string ILLEGAL_STATUS_CODE = 'UPDATE_MOVEMENT_REST_DURATION_ILLEGAL_STATE';
    public const string FAILED_ERROR_CODE = 'UPDATE_MOVEMENT_REST_DURATION_VALIDATION_FAILED';

    public function validate(PlayerDataModel $player, UpdateMovementRestDurationDataInput $input, ExerciseDataModel $exercise): void
    {
        $this->assertPlayerOwns($player, $exercise);

        if (false === in_array($exercise->workout->status, WorkoutStatusRegistry::EDITABLE_STATUSES, true)) {
            throw new ValidationException('Rest duration can only be edited on a planned or in-progress workout.', ['status' => [sprintf('Workout is in status "%s", expected one of: %s.', $exercise->workout->status, implode(', ', WorkoutStatusRegistry::EDITABLE_STATUSES))]], self::ILLEGAL_STATUS_CODE);
        }

        $violations = [];
        if (0 > $input->restDurationSeconds) {
            $violations['restDurationSeconds'][] = 'Rest duration must be zero or positive.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Update rest duration data is invalid.', $violations, self::FAILED_ERROR_CODE);
        }
    }
}
