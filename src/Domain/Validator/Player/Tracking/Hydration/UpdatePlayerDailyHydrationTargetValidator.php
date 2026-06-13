<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Hydration;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdatePlayerDailyHydrationTargetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class UpdatePlayerDailyHydrationTargetValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'UPDATE_PLAYER_HYDRATION_TARGET_VALIDATION_FAILED';

    public function validate(UpdatePlayerDailyHydrationTargetDataInput $input): void
    {
        $violations = [];
        if (0 >= $input->targetMl) {
            $violations['targetMl'][] = 'Daily hydration target must be a positive number of milliliters.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Player hydration target is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
