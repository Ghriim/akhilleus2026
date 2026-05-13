<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpsertStepsForDayDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class UpsertStepsForDayValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'UPSERT_STEPS_VALIDATION_FAILED';

    public function validate(UpsertStepsForDayDataInput $input): void
    {
        $violations = [];
        if (0 > $input->count) {
            $violations['count'][] = 'Step count must be zero or positive.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Steps data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
