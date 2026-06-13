<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Tracking\Steps;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpdatePlayerDailyStepsTargetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class UpdatePlayerDailyStepsTargetValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'UPDATE_PLAYER_STEPS_TARGET_VALIDATION_FAILED';

    public function validate(UpdatePlayerDailyStepsTargetDataInput $input): void
    {
        $violations = [];
        if (0 >= $input->target) {
            $violations['target'][] = 'Daily step goal must be a positive number of steps.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Player step target is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
