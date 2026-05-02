<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\FinishWorkoutDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class FinishWorkoutValidator extends AbstractLoggedPlayerValidator
{
    public const string ILLEGAL_STATUS_CODE = 'FINISH_WORKOUT_ILLEGAL_STATE';
    public const string INCOMPLETE_SETS_ERROR_CODE = 'WORKOUT_HAS_INCOMPLETE_SETS';

    public function validate(PlayerDataModel $player, FinishWorkoutDataInput $input, WorkoutDataModel $workout): void
    {
        $this->assertPlayerOwns($player, $workout);

        if (WorkoutStatusRegistry::IN_PROGRESS !== $workout->status) {
            throw new ValidationException('Only an in-progress workout can be finished.', ['status' => [sprintf('Workout is in status "%s", expected "%s".', $workout->status, WorkoutStatusRegistry::IN_PROGRESS)]], self::ILLEGAL_STATUS_CODE);
        }

        $incompleteSetIds = [];
        foreach ($workout->exercises as $exercise) {
            foreach ($exercise->exerciseSets as $set) {
                if (false === $set->isComplete) {
                    $incompleteSetIds[] = $set->id;
                }
            }
        }

        if ([] !== $incompleteSetIds) {
            throw new ValidationException('Workout cannot be finished while some exercise sets are not yet completed.', ['exerciseSets' => $incompleteSetIds], self::INCOMPLETE_SETS_ERROR_CODE);
        }
    }
}
