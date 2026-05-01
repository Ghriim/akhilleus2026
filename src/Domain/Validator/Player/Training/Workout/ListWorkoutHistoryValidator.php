<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\ListWorkoutHistoryDataInput;
use App\Domain\Exception\ValidationException;

final readonly class ListWorkoutHistoryValidator
{
    public const string ERROR_CODE = 'LIST_WORKOUT_HISTORY_VALIDATION_FAILED';

    public function validate(ListWorkoutHistoryDataInput $input): void
    {
        $violations = [];
        if (1 > $input->page) {
            $violations['page'][] = 'Page must be greater than or equal to 1.';
        }
        if (1 > $input->perPage) {
            $violations['perPage'][] = 'PerPage must be greater than or equal to 1.';
        }
        if (ListWorkoutHistoryDataInput::MAX_PER_PAGE < $input->perPage) {
            $violations['perPage'][] = sprintf('PerPage must not exceed %d.', ListWorkoutHistoryDataInput::MAX_PER_PAGE);
        }

        if ([] !== $violations) {
            throw new ValidationException('Workout history query is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
