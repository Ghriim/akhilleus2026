<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\CancelWorkoutDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class CancelWorkoutValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'CANCEL_WORKOUT_ILLEGAL_STATE';

    public function validate(PlayerDataModel $player, CancelWorkoutDataInput $input, WorkoutDataModel $workout): void
    {
        $this->assertPlayerOwns($player, $workout);

        if (false === in_array($workout->status, WorkoutStatusRegistry::CANCELLABLE_STATUSES, true)) {
            throw new ValidationException('Only a planned or in-progress workout can be canceled.', ['status' => [sprintf('Workout is in status "%s", expected one of: %s.', $workout->status, implode(', ', WorkoutStatusRegistry::CANCELLABLE_STATUSES))]], self::ERROR_CODE);
        }
    }
}
