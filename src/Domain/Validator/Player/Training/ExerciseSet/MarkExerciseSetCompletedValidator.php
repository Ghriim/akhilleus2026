<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\MarkExerciseSetCompletedDataInput;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class MarkExerciseSetCompletedValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'MARK_EXERCISE_SET_COMPLETED_ILLEGAL_STATE';

    public function validate(PlayerDataModel $player, MarkExerciseSetCompletedDataInput $input, ExerciseSetDataModel $set): void
    {
        $this->assertPlayerOwns($player, $set);

        if (WorkoutStatusRegistry::IN_PROGRESS !== $set->exercise->workout->status) {
            throw new ValidationException('Sets can only be marked completed on an in-progress workout.', ['status' => [sprintf('Workout is in status "%s", expected "%s".', $set->exercise->workout->status, WorkoutStatusRegistry::IN_PROGRESS)]], self::ERROR_CODE);
        }
    }
}
