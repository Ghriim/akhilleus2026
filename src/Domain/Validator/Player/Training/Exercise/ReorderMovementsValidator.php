<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Exercise;

use App\Domain\DTO\DataInput\Player\Training\Exercise\ReorderMovementsDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class ReorderMovementsValidator extends AbstractLoggedPlayerValidator
{
    public const string ILLEGAL_STATUS_CODE = 'REORDER_MOVEMENTS_ILLEGAL_STATE';
    public const string FAILED_ERROR_CODE = 'REORDER_MOVEMENTS_VALIDATION_FAILED';

    public function validate(PlayerDataModel $player, ReorderMovementsDataInput $input, WorkoutDataModel $workout): void
    {
        $this->assertPlayerOwns($player, $workout);

        if (false === in_array($workout->status, WorkoutStatusRegistry::EDITABLE_STATUSES, true)) {
            throw new ValidationException('Movements can only be reordered on a planned or in-progress workout.', ['status' => [sprintf('Workout is in status "%s", expected one of: %s.', $workout->status, implode(', ', WorkoutStatusRegistry::EDITABLE_STATUSES))]], self::ILLEGAL_STATUS_CODE);
        }

        $violations = [];
        if ([] === $input->orderedExerciseIds) {
            $violations['orderedExerciseIds'][] = 'Ordered exercise ids must not be empty.';
        } elseif (count($input->orderedExerciseIds) !== count(array_unique($input->orderedExerciseIds))) {
            $violations['orderedExerciseIds'][] = 'Ordered exercise ids must not contain duplicates.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Reorder movements data is invalid.', $violations, self::FAILED_ERROR_CODE);
        }
    }
}
