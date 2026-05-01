<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\RemoveExerciseSetDataInput;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class RemoveExerciseSetValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'REMOVE_EXERCISE_SET_ILLEGAL_STATE';

    public function validate(PlayerDataModel $player, RemoveExerciseSetDataInput $input, ExerciseSetDataModel $set): void
    {
        $this->assertPlayerOwns($player, $set);

        if (false === in_array($set->exercise->workout->status, WorkoutStatusRegistry::EDITABLE_STATUSES, true)) {
            throw new ValidationException('Sets can only be removed from a planned or in-progress workout.', ['status' => [sprintf('Workout is in status "%s", expected one of: %s.', $set->exercise->workout->status, implode(', ', WorkoutStatusRegistry::EDITABLE_STATUSES))]], self::ERROR_CODE);
        }
    }
}
