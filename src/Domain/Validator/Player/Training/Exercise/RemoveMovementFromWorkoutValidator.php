<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Exercise;

use App\Domain\DTO\DataInput\Player\Training\Exercise\RemoveMovementFromWorkoutDataInput;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class RemoveMovementFromWorkoutValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'REMOVE_MOVEMENT_FROM_WORKOUT_ILLEGAL_STATE';

    public function validate(PlayerDataModel $player, RemoveMovementFromWorkoutDataInput $input, ExerciseDataModel $exercise): void
    {
        $this->assertPlayerOwns($player, $exercise);

        if (false === in_array($exercise->workout->status, WorkoutStatusRegistry::EDITABLE_STATUSES, true)) {
            throw new ValidationException('Movements can only be removed from a planned or in-progress workout.', ['status' => [sprintf('Workout is in status "%s", expected one of: %s.', $exercise->workout->status, implode(', ', WorkoutStatusRegistry::EDITABLE_STATUSES))]], self::ERROR_CODE);
        }
    }
}
