<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\ListWorkoutsByMonthDataInput;
use App\Domain\Exception\ValidationException;

final readonly class ListWorkoutsByMonthValidator
{
    public const string ERROR_CODE = 'LIST_WORKOUTS_BY_MONTH_VALIDATION_FAILED';

    public function validate(ListWorkoutsByMonthDataInput $input): void
    {
        $violations = [];
        if (ListWorkoutsByMonthDataInput::MIN_YEAR > $input->year || ListWorkoutsByMonthDataInput::MAX_YEAR < $input->year) {
            $violations['year'][] = sprintf(
                'Year must be between %d and %d.',
                ListWorkoutsByMonthDataInput::MIN_YEAR,
                ListWorkoutsByMonthDataInput::MAX_YEAR,
            );
        }
        if (1 > $input->month || 12 < $input->month) {
            $violations['month'][] = 'Month must be between 1 and 12.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Workout calendar query is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
