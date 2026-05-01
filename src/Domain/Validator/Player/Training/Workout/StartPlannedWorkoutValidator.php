<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\StartPlannedWorkoutDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class StartPlannedWorkoutValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'START_PLANNED_WORKOUT_ILLEGAL_STATE';

    public function validate(PlayerDataModel $player, StartPlannedWorkoutDataInput $input, WorkoutDataModel $workout): void
    {
        $this->assertPlayerOwns($player, $workout);

        if (WorkoutStatusRegistry::PLANNED !== $workout->status) {
            throw new ValidationException('Only a planned workout can be started.', ['status' => [sprintf('Workout is in status "%s", expected "%s".', $workout->status, WorkoutStatusRegistry::PLANNED)]], self::ERROR_CODE);
        }
    }
}
