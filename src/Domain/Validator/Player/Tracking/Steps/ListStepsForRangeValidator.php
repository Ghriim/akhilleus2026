<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\ListStepsForRangeDataInput;
use App\Domain\Exception\ValidationException;

final readonly class ListStepsForRangeValidator
{
    public const string ERROR_CODE = 'LIST_STEPS_RANGE_VALIDATION_FAILED';

    public function validate(ListStepsForRangeDataInput $input): void
    {
        if ($input->from > $input->to) {
            throw new ValidationException('Steps range is invalid.', ['from' => ['"from" must be on or before "to".']], self::ERROR_CODE);
        }
    }
}
