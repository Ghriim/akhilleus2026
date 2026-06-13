<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdateHydrationDailyTargetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class UpdateHydrationDailyTargetValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'UPDATE_HYDRATION_DAILY_TARGET_VALIDATION_FAILED';

    public function validate(UpdateHydrationDailyTargetDataInput $input): void
    {
        $violations = [];
        if (0 >= $input->targetMl) {
            $violations['targetMl'][] = 'Daily hydration target must be a positive number of milliliters.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Hydration daily target is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
