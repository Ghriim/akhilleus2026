<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Sleep;

use App\Domain\DTO\DataInput\Player\Tracking\Sleep\ListSleepForRangeDataInput;
use App\Domain\Exception\ValidationException;

final readonly class ListSleepForRangeValidator
{
    public const string ERROR_CODE = 'LIST_SLEEP_RANGE_VALIDATION_FAILED';

    public function validate(ListSleepForRangeDataInput $input): void
    {
        if ($input->from > $input->to) {
            throw new ValidationException('Sleep range is invalid.', ['from' => ['"from" must be on or before "to".']], self::ERROR_CODE);
        }
    }
}
