<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpdateStepsDailyTargetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class UpdateStepsDailyTargetValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'UPDATE_STEPS_DAILY_TARGET_VALIDATION_FAILED';

    public function validate(UpdateStepsDailyTargetDataInput $input): void
    {
        $violations = [];
        if (0 >= $input->target) {
            $violations['target'][] = 'Daily step goal must be a positive number of steps.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Steps daily target is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
