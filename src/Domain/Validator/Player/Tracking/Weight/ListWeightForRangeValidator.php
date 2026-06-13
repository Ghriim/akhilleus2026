<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Weight;

use App\Domain\DTO\DataInput\Player\Tracking\Weight\ListWeightForRangeDataInput;
use App\Domain\Exception\ValidationException;

final readonly class ListWeightForRangeValidator
{
    public const string ERROR_CODE = 'LIST_WEIGHT_RANGE_VALIDATION_FAILED';

    public function validate(ListWeightForRangeDataInput $input): void
    {
        if ($input->from > $input->to) {
            throw new ValidationException('Weight range is invalid.', ['from' => ['"from" must be on or before "to".']], self::ERROR_CODE);
        }
    }
}
